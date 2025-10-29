<?php

namespace App\Reportes\Empresa\Comprobantes;

use App\Service\Comprobantes\QRGenerator;
use App\Utils\StringUtil;
use Fpdf\Fpdf;

class ComprobanteTicketPdf extends Fpdf
{
	protected array $comprobante = [];
	protected array $empresa = [];
	protected array $movimIVA = [];
	protected bool $discrimino = false;
	protected bool $esFE = false;

	/**
	 * variable para setear si el PDF sale por navegador o se adjunta a un email
	 * @var string
	 */
	private $Salida = 'I';

	function Header(): void
	{
		$this->SetFont('Arial', '', 7);
		if ($this->esFE){
			$empresa = $this->empresa['empresa'];
			$factura = $this->comprobante['cabecera'];
			$this->SetFont('Arial', 'B', 10);
			$nombreEmpresa = substr(StringUtil::utf8($empresa['nombre']), 0, 40);
			$this->Cell(70, 5, $nombreEmpresa, 0, 1,'C');

			$cuit = substr($empresa['cuit'], 0,2) . '-' . substr($empresa['cuit'], 2,8) . '-' . substr($empresa['cuit'], 10,1);
			$cuit = 'C.U.I.T.: ' . $cuit;
			$this->SetFont('Arial', '', 8);
			$this->Cell(70, 4, $cuit, 0, 1,'C');

			if (strlen($empresa['iibb']) > 2){
				$iibb = 'Ingresos Brutos: ' . $empresa['iibb'];
				$this->Cell(70, 4, $iibb, 0, 1,'C');
			}

			$domicilio = StringUtil::utf8("Domicilio: " . $empresa['direccion']) ;
			$this->Cell(70, 4, substr($domicilio,0,60), 0, 1,'C');
			$this->SetFont('Arial', '', 7);
			$ciudad = StringUtil::utf8('Ciudad.: ' . $empresa['localidad'] . ' - ' . $empresa['provincia']);
			$this->Cell(70, 4, substr($ciudad,0,60), 0, 1,'C');

			if ($empresa['fecha_inicio']){
				$fechaInicio = 'Fecha de Inicio de Actividades: ';
				$fechaInicio .= \DateTime::createFromFormat('Y-m-d', $empresa['fecha_inicio'])->format('d/m/Y');
				$this->Cell(70, 4, StringUtil::utf8($fechaInicio), 0, 1,'C');
			}

			$this->SetFont('Arial', '', 8);
			$clienteCategoriaIva = 'IVA: ' . $empresa['categoriaIVA'];
			$this->Cell(70, 4, StringUtil::utf8($clienteCategoriaIva), 0, 1,'C');

			$this->SetFont('Arial', '', 10);
			$this->Cell(70, 5, StringUtil::utf8($factura['tipoComprobanteNombre']), 0, 1,'C');

			$this->SetFont('Arial', '', 7);
			$texto = 'Original ' . StringUtil::utf8('COD. '.sprintf('%02d',$factura['tipo_comprobante_afip']));
			$this->Cell(70, 5, $texto, 0, 1,'C');


		}
		date_default_timezone_set('America/Argentina/Cordoba');
		$this->Cell(70, 4, $this->delimitador(1), 0, 1,'L');
		$texto = 'Fecha: ' . date('d/m/Y') . '   Hora: ' . date('H:i:s');
		$this->Cell(70, 4, $texto, 0, 1,'C');
		$this->Cell(70, 4, $this->delimitador(1), 0, 0,'L');

	}

	/**
	 * Genera un Ticket en PDF
	 * @return string
	 */
	public function GenerarTicketPdf(): string
	{
		$totalTicket = 0;
		$totalNeto = 0;
		$factura = $this->comprobante['cabecera'];
		$largoTicket = $this->determinoLargoTicket();
		$this->SetMargins(0,10,5);
		$this->AddPage('P',[80, $largoTicket], 0);
		$this->SetTitle(StringUtil::utf8('Factura Simple - ' . $this->empresa['usuario']['nombre'] . ' - Impresión de Ticket'));
		$this->SetFont('Arial', '', 7);
		$y = $this->GetY() + 5;
		$this->SetXY(0, $y);
		foreach ($this->comprobante['movimientos'] as $factm) {
			$nombre = substr(StringUtil::utf8($factm['producto_nombre']), 0,30);
			$cantidad = (float)$factm['cantidad'];
			$unidad = StringUtil::utf8($factm['producto_unidad']);
			$precioUnitario = number_format($factm['precio_unitario_civa'], 2, ',', '.');
			$subtotalSiva = floatval($factm['monto_neto']);
			$subtotalCiva = floatval($factm['precio_unitario_civa'] * $factm['cantidad']);
			$ivaPorcentaje = number_format($factm['porcentaje_iva'], 2, ',', '.');

			$this->Cell(45, 4, $nombre, 0, 0,'L');
			if ($this->esFE) {  // si es FE muestro el % de IVA
				$this->Cell(10, 4, $ivaPorcentaje, 0, 0, 'R');
			}else{
				$this->Cell(10, 4, '', 0, 0, 'R');
			}
			$this->Cell(15, 4, number_format($subtotalCiva, 2, ',', '.'), 0, 0,'R');

			if ((int)$cantidad !== 1){
				$y += 3;
				$this->SetXY(10, $y);
				$texto = $cantidad . ' ' . $unidad . ' x ' . $precioUnitario;
				$this->Cell(55, 3, $texto, 0, 0,'L');
			}
			$y += 4;
			$this->SetXY(0, $y);
			$totalNeto += $subtotalSiva;
			$totalTicket += $subtotalCiva;
		}

		$this->SetXY(0, $this->GetY() + 5);
		$this->Cell(70, 4, $this->delimitador(1), 0, 1,'L');

		$this->SetFont('Arial', '', 9);

		if ($this->discrimino){
			$totalIva = 0;
			$this->Cell(52, 6, 'Imp. Neto $', 0, 0,'R');
			$this->Cell(18, 6, number_format($totalNeto, 2, ',', '.'), 0, 1,'R');

			// calculo el monto del iva total
			foreach ($this->movimIVA as $movIva) {
				$totalIva += (float)$movIva['montoIva'];
			}
			$this->Cell(52, 6, 'I.V.A. $', 0, 0,'R');
			$this->Cell(18, 6, number_format($totalIva, 2, ',', '.'), 0, 1,'R');
		}

		$this->SetFont('Arial', 'B', 9);
		$this->Cell(52, 6, 'Total $', 0, 0,'R');
		$this->Cell(18, 6, number_format($totalTicket, 2, ',', '.'), 0, 1,'R');

		$this->SetFont('Arial', '', 7);
		$this->Cell(70, 4, $this->delimitador(1), 0, 1,'L');

		$nombrePdf = 'ticket_' . $factura['codigo'] . '.pdf';
		return $this->Output($this->Salida, $nombrePdf);

	}

	/**
	 * @return void
	 */
	function Footer(): void
	{
		$this->SetFont('Arial', '', 7);
		$texto = 'Cant. artículos: ' . count($this->comprobante['movimientos']);
		$this->Cell(40, 4, StringUtil::utf8($texto), 0, 0,'L');
		$this->Cell(30, 4, StringUtil::utf8('Gracias por su compra!'), 0, 1,'R');

		if ($this->esFE) {
			$factura = $this->comprobante['cabecera'];
			$this->Ln(2);
			$this->Cell(70, 5, $this->delimitador(2), 0, 1, 'L');

			$this->SetFont('Arial', '', 10);
			$nro = str_pad((int) $factura['punto_venta'],4,"0",STR_PAD_LEFT)
				. ' - '
				. str_pad((int) $factura['numero'],8,"0",STR_PAD_LEFT);
			$this->Cell(40, 4, $nro, 0, 1,'L');

			$this->SetFont('Arial', '', 9);
			$this->Cell(70, 5, StringUtil::utf8(substr($factura['nombre'],0,40)), 0, 1, 'L');

			$this->SetFont('Arial', '', 7);
			$this->Cell(70, 3, $this->delimitador(2), 0, 1, 'L');

			$cae = 'CAE: ' . $factura['cae'];
			$fechaCAE   = \DateTime::createFromFormat('Y-m-d H:i:s', $factura['fecha_vencimiento_cae']);
			$this->Cell(35, 3, $cae, 0, 0, 'L');
			$this->Cell(35, 3, 'Fecha Vto.: ' . $fechaCAE->format('d/m/Y'), 0, 0, 'R');
            $this->Ln(2);

            // leyenda REGIMEN DE TRANSPARENCIA FISCAL, solo para RI, cuando la fc es B
            if ((int)$this->empresa['empresa']['categoria_iva_id'] === 1 && !$this->discrimino) {
                $this->Cell(70, 3, $this->delimitador(2), 0, 1, 'L');
                $this->SetFont('Arial','',6);
                $this->Cell(75,3,'REGIMEN DE TRANSPARENCIA FISCAL AL CONSUMIDOR (LEY 27743)',0,1,'L');
                $ivaContenido = number_format(floatval($factura['total_iva']),2, ',', '.');
                $this->Cell(75,3,'IVA CONTENIDO: $ '. $ivaContenido,0,1,'L');
                $this->Cell(75,3,'OTROS IMPUESTOS NACIONALES INDIRECTOS: $ 0,00.-',0,0,'L');
            }

			// Genero del código de barra QR
			$arrQr = [
				'empresa' => $this->empresa,
				'comprobante' => $this->comprobante['cabecera'],
			];
			$y = $this->GetY() + 10;
			$urlQrAfip = QRGenerator::armoCodigoQRFacturaAFIP($arrQr);
			$pathQRImagen = QRGenerator::GenerarQR($urlQrAfip);
			$this->Image($pathQRImagen,20,$y,42,42, 'jpg' );
		}

	}


	/**
	 * Analiza cabecera, movimientos y footer para saber el largo del ticket
	 * @return int
	 */
	private function determinoLargoTicket():int {
		$largo = 60;
		if ($this->esFE) {
			$largo += 90;  // son 50 de cabecera y 50 para cod QR al final
		}
		// recorro movimientos para saber cuanto renglones tengo y la cant de c/u
		foreach ($this->comprobante['movimientos'] as $movimiento) {
			$largo += 4;
			if ((int)$movimiento['cantidad'] !== 1){
				$largo += 4;
			}
		}
		if ($this->discrimino){
			$largo += 10;
		}
		if ($largo < 80) {
			$largo = 81;
		}
        if ((int)$this->empresa['empresa']['categoria_iva_id'] === 1 && !$this->discrimino) {
           $largo += 20;
        }
		return $largo;
	}

	/**
	 * @param int $tipo
	 * @return string
	 */
	private function delimitador(int $tipo):string {
		$delimitador =  '================================================';
		if ($tipo === 2){
			$delimitador =  '------------------------------------------------------------------------------------';
		}
		return $delimitador;
	}


	public function setComprobante(array $comprobante): ComprobanteTicketPdf
	{
		$this->comprobante = $comprobante;
		return $this;
	}

	public function setEmpresa(array $empresa): ComprobanteTicketPdf
	{
		$this->empresa = $empresa;
		return $this;
	}

	public function setMovimIVA(array $movimIVA): ComprobanteTicketPdf
	{
		$this->movimIVA = $movimIVA;
		return $this;
	}

	public function setDiscrimino(bool $discrimino): ComprobanteTicketPdf
	{
		$this->discrimino = $discrimino;
		return $this;
	}

	public function setEsFE(bool $esFE): ComprobanteTicketPdf
	{
		$this->esFE = $esFE;
		return $this;
	}

	public function setSalida(string $Salida): ComprobanteTicketPdf
	{
		$this->Salida = $Salida;
		return $this;
	}

}
