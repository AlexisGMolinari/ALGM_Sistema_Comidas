<?php

namespace App\Reportes\Empresa\Comprobantes;

use App\Reportes\UtilesPdf;
use App\Utils\StringUtil;
use DateTime;
use Fpdf\Fpdf;

class PresupuestoPdf extends Fpdf
{
	protected array $comprobante = [];
	protected array $empresa = [];
	protected array $movimIVA = [];
	protected bool $discrimino = false;
	protected int $entrega  = 0;
	/**
	 * variable para setear si el PDF sale por navegador o se adjunta a un email
	 * @var string
	 */
	private string $salida = 'I';

	function Header(): void
	{
		$presupuesto = $this->comprobante['cabecera'];
		$empresa = $this->empresa['empresa'];
		$usuario = $this->empresa['usuario'];

		$this->Rect(5, 5, 200, 55);
		//      linea division fecha vto. comprobante
		$this->Line(5 , 54 , 205 , 54);
		//      rectangulo: cliente
		$this->Rect(5, 61, 200, 24);
		//      rectangulo: titulos de detalle del comprobante
		$this->Rect(5, 86, 200, 5);

		// ----------------------------     columna izquierda Cabecera ------------------------
		$columnaIzq = 10;

		// ---------- analizo si tiene logo lo muestro sino muestro en grande el nombre de la empresa
		if ($empresa['logo']) {
			$logo = 'logos/' . $empresa['logo'];

			$this->Image($logo,$columnaIzq,7,50);

			$this->SetXY($columnaIzq,34);
			$this->SetFont('Arial','B',12);
			$empresaNombre = StringUtil::utf8($usuario['nombre']);
			$this->MultiCell(90,10,  $empresaNombre,0,'L');

			$this->SetXY($columnaIzq,40);
			$this->SetFont('Arial','',10);
			$domicilio = "Domicilio: " . $empresa['direccion'] . ' - ' . $empresa['localidad'] ;
			$this->MultiCell(100,10,  StringUtil::utf8($domicilio),0,'L');

			$this->SetXY($columnaIzq,45);
			$categoriaIvaEmpresa = 'Condición frente al IVA: ' . $empresa['categoriaIVA'];
			$this->Cell(100,10,  StringUtil::utf8($categoriaIvaEmpresa),0,0);

		} else{
			$this->SetXY($columnaIzq,12);
			$this->SetFont('Arial','B',18);
			$empresaNombre = StringUtil::utf8($usuario['nombre']);
			$this->MultiCell(90,10,  $empresaNombre,0,'L');

			$this->SetXY($columnaIzq,35);
			$this->SetFont('Arial','',10);
			$domicilio = "Domicilio: " . $empresa['direccion'] . ' - ' . $empresa['localidad'] ;
			$this->MultiCell(100,10,  StringUtil::utf8($domicilio),0,'L');

			$this->SetXY($columnaIzq,41);
			$categoriaIvaEmpresa = 'Condición frente al IVA: ' . $empresa['categoriaIVA'];
			$this->Cell(100,10,  StringUtil::utf8($categoriaIvaEmpresa),0,0);
		}



// -------------------------- Columna Derecha ------------------------------------------

		$columnaDer = 120;
		$this->SetXY($columnaDer,11);
		$comprobante = 'PRESUPUESTO';
		$this->SetFont('Arial','B',18);
		$this->MultiCell(85,10,  StringUtil::utf8($comprobante),0,'L','');

		$this->SetXY($columnaDer,20);
		$this->SetFont('Arial','B',9);
		$this->MultiCell(20,10,  StringUtil::utf8('Comp. Nro:'),0);
		$this->SetFont('Arial','',9);
		$this->SetXY($columnaDer + 20,20);
		$this->MultiCell(25,10,  StringUtil::utf8(str_pad((int) $presupuesto['id'],8,"0",STR_PAD_LEFT)),0);


		$this->SetXY($columnaDer,25);
		$fechaEmision = 'Fecha de Emisión: ' . DateTime::createFromFormat('Y-m-d H:i:s', $presupuesto['fecha'])->format('d/m/Y');
		$this->SetFont('Arial','B',9);
		$this->MultiCell(0,10,  StringUtil::utf8($fechaEmision),0);

		$this->SetXY($columnaDer,30);
		$cuit = substr($empresa['cuit'], 0,2) . '-' . substr($empresa['cuit'], 2,8) . '-' . substr($empresa['cuit'], 10,1);
		$cuit = 'CUIT: ' . $cuit;
		$this->MultiCell(90,10, $cuit ,0);

		$this->SetXY($columnaDer,35);
		$iibb = 'Ingresos Brutos: ' . $empresa['iibb'];
		$this->MultiCell(90,10,  StringUtil::utf8($iibb),0);

		$this->SetXY($columnaDer,40);
		$fechaInicio = 'Fecha de Inicio de Actividades: ';
		if ($empresa['fecha_inicio']){
			$fechaInicio .= \DateTime::createFromFormat('Y-m-d', $empresa['fecha_inicio'])->format('d/m/Y');

		}
		$this->MultiCell(90,10,  StringUtil::utf8($fechaInicio),0);

		$this->SetXY($columnaDer,54);
		$estado = 'Estado: ' . $this->getEstado((int)$presupuesto['estado']);
		$fecha = \DateTime::createFromFormat('Y-m-d H:i:s', $presupuesto['fecha_estado']);

		// todo: revisar el timezone del servidor
		// $fecha->setTimezone(new DateTimeZone("America/Argentina/Cordoba"));
		$this->MultiCell(90,7,  StringUtil::utf8($estado . ' - ' . $fecha->format('d/m/Y H:i:s')),0);
		// -------------------------- CLIENTE -> Columna Izquierda ------------------------------------------

		$this->SetXY($columnaIzq,60);
		//si es consumidor final muestro el DNI
		if (strlen($presupuesto['numero_documento']) < 11){
			$clienteCuit = $presupuesto['numero_documento'];
		}else{
			$clienteCuit = substr($presupuesto['numero_documento'], 0,2) . '-' . substr($presupuesto['numero_documento'], 2,8) . '-' . substr($presupuesto['numero_documento'], 10,1);

		}
		$this->MultiCell(100,10,  StringUtil::utf8('CUIT/DNI: ' .$clienteCuit),0);

		$this->SetXY($columnaIzq,65);
		$clienteCategoriaIva = 'Condición frente al IVA: ' . $presupuesto['categoriaIVACliente'];
		$this->MultiCell(100,10,  StringUtil::utf8($clienteCategoriaIva),0);

		// -------------------------- CLIENTE -> Columna Derecha  ------------------------------------------
		$this->SetXY($columnaDer-15,63);
		$clienteRazon = 'Razón Social: ' . $presupuesto['nombre'];
		$this->MultiCell(100,4,  StringUtil::utf8($clienteRazon),0);

		$this->SetXY($columnaDer-15,70);
		$domicilio = 'Domicilio: ' . $presupuesto['domicilio'];
		$this->MultiCell(100,10,  StringUtil::utf8($domicilio),0);

		$this->SetXY($columnaDer-15,75);
		$ciudad = 'Ciudad.: ' . $presupuesto['codigo_postal'] . ' - ' . $presupuesto['localidad'] . ' - ' . $presupuesto['provincia'];
		$this->MultiCell(100,10,   StringUtil::utf8($ciudad),0);


		if ($this->discrimino) {
			//datos: titulos del detalle de producto to do para resp inscriptos
			$this->SetXY($columnaIzq, 84);
			$this->SetFont('Arial', 'B', 7);
			$this->Cell(23, 10, StringUtil::utf8('Código'), 0, 0);
			$this->Cell(55, 10, StringUtil::utf8('Producto / Servicio'), 0, 0);
			$this->Cell(14, 10, StringUtil::utf8('Cantidad'), 0, 0);
			$this->Cell(19, 10, StringUtil::utf8('U. medida'), 0, 0);
			$this->Cell(16, 10, StringUtil::utf8('Precio Unit.'), 0, 0);
			$this->Cell(18, 10, StringUtil::utf8('% Bonif'), 0, 0);
			$this->Cell(16, 10, StringUtil::utf8('Subtotal'), 0, 0);
			$this->Cell(15, 10, StringUtil::utf8('Alicuota %'), 0, 0);
			$this->Cell(23, 10, StringUtil::utf8('Subtotal c/IVA'), 0, 0);
			$this->Ln(7);
		} else {
			$colX = $columnaIzq - 5;
			$this->SetXY($colX, 84);
			$this->SetFont('Arial', 'B', 8);
			$anchoCol = 25;
			$this->MultiCell($anchoCol, 10, StringUtil::utf8('Código'), 0, 'C');
			$colX += $anchoCol;
			$this->SetXY($colX, 84);

			$anchoCol = 55;
			$this->MultiCell($anchoCol, 10, StringUtil::utf8('Producto / Servicio'), 0, 'C');
			$colX += $anchoCol;
			$this->SetXY($colX, 84);

			$anchoCol = 15;
			$this->MultiCell($anchoCol, 10, StringUtil::utf8('Cant.'), 0, 'R');
			$colX += $anchoCol;
			$this->SetXY($colX, 84);

			$anchoCol = 20;
			$this->MultiCell($anchoCol, 10, StringUtil::utf8('U.medida'), 0, 'R');
			$colX += $anchoCol;
			$this->SetXY($colX, 84);

			$anchoCol = 25;
			$this->MultiCell($anchoCol, 10, StringUtil::utf8('Precio Unit'), 0, 'R');
			$colX += $anchoCol;
			$this->SetXY($colX, 84);

			$anchoCol = 15;
			$this->SetFont('Arial', 'B', 7);
			$this->MultiCell($anchoCol, 10, StringUtil::utf8('% Bon/Rec'), 0, 'C');
			$colX += $anchoCol;
			$this->SetXY($colX, 84);
			$this->SetFont('Arial', 'B', 8);

			$anchoCol = 20;
			$this->MultiCell($anchoCol, 10, StringUtil::utf8('Imp.Bonif'), 0, 'R');
			$colX += $anchoCol;
			$this->SetXY($colX, 84);

			$anchoCol = 25;
			$this->MultiCell($anchoCol, 10, StringUtil::utf8('Subtotal'), 0, 'R');
		}
	}


	/**
	 * Genera los movimientos y exporta el objeto PDF
	 * @return string
	 */
	public function generarPdf(): string
	{
		$this->AddPage();
		$this->SetTitle(StringUtil::utf8('Factura Simple - ' . $this->empresa['usuario']['nombre'] . ' - Impresión de Presupuesto/Pedido'));
		$this->SetFont('Arial','',9);
		$cont = 0;
		$filaY = 93;

		foreach ($this->comprobante['movimientos'] as $presuMov) {
			$cont           = $cont + 1;
			$codigo         = StringUtil::utf8($presuMov['producto_codigo']);
			$nombre         = StringUtil::utf8($presuMov['producto_nombre']);
			$cantidad       = $presuMov['cantidad'];
			$unidad         = StringUtil::utf8($presuMov['producto_unidad']);

			// Establezco el alto del renglón y los demás valores de acuerdo al nombre del producto
			$arrVal = (new UtilesPdf())->definoAlto(strlen($nombre));
			$alto   = $arrVal['altoLinea'];

			if ($this->discrimino) {        // si discrimino imprimo de una forma
				$subtotalSiva   = number_format($presuMov['monto_neto'] ,2, ',', '.');
				$subtotalCiva   = number_format(floatval($presuMov['monto_iva'] + $presuMov['monto_neto']) ,2, ',', '.');
				$montoDescuento = number_format($presuMov['monto_descuento'],2, ',', '.');
				$descuentoPorc  = number_format($presuMov['porcentaje_descuento'],2, ',', '.');
				$alicuota       = $presuMov['porcentaje_iva'];

				// calculo el IVA de la bonif o del recargo y se lo sumo al precio unitario sin IVA
				$tasaIvaBonif   = floatval($alicuota/100) + 1;
				$impoIvaBonif   = floatval((abs(floatval($montoDescuento)) * $tasaIvaBonif) / $cantidad); //abs por si está ne negativo
				$precioUnitario = number_format($presuMov['precio_unitario_siva'] + $impoIvaBonif ,2, ',', '.');

				$colX  = 5;
				$this->SetXY($colX, $filaY);
				$this->SetFont('Arial', '', 9);
				$anchoCol = 25;
				$this->MultiCell($anchoCol, 5,$codigo, 0, 'L');
				$colX += $anchoCol;
				$this->SetXY($colX, $filaY);

				$anchoCol = 60;
				$this->SetFont('Arial', '', $arrVal['fuente']);
				$this->MultiCell($anchoCol, $arrVal['altoCampo'], $nombre, 0, 'L');
				$colX += $anchoCol;
				$this->SetXY($colX, $filaY);

				$this->SetFont('Arial', '', 9);

				$anchoCol = 12;
				$this->MultiCell($anchoCol, $alto, $cantidad, 0, 'C');
				$colX += $anchoCol;
				$this->SetXY($colX, $filaY);

				$anchoCol = 18;
				$this->MultiCell($anchoCol, $alto, $unidad, 0, 'C');
				$colX += $anchoCol;
				$this->SetXY($colX, $filaY);

				$anchoCol = 18;
				$this->MultiCell($anchoCol, $alto, $precioUnitario, 0, 'R');
				$colX += $anchoCol;
				$this->SetXY($colX, $filaY);

				$anchoCol = 13;
				$this->MultiCell($anchoCol, $alto, $descuentoPorc, 0, 'R');
				$colX += $anchoCol;
				$this->SetXY($colX, $filaY);

				$anchoCol = 16;
				$this->MultiCell($anchoCol, $alto, $montoDescuento, 0, 'R');
				$colX += $anchoCol;
				$this->SetXY($colX, $filaY);

				$anchoCol = 15;
				$this->MultiCell($anchoCol, $alto, number_format($alicuota,1, ',', '.'), 0, 'R');
				$colX += $anchoCol;
				$this->SetXY($colX, $filaY);

				$anchoCol = 23;
				$this->MultiCell($anchoCol, $alto, $subtotalCiva, 0, 'R');

			} else {
				$bonifRec   = floatval(($presuMov['monto_descuento'] * (-1)) / $cantidad);                             // cambio signo para volver el precio original
				$precioUnitario = number_format($presuMov['precio_unitario_civa'] + $bonifRec,2, ',', '.');
				$subtotalSiva   = number_format(floatval($presuMov['precio_unitario_civa'] * $presuMov['cantidad']) ,2, ',', '.');
				$descuentoPorc  = number_format($presuMov['porcentaje_descuento'],2, ',', '.');
				$montoDescuento = number_format($presuMov['monto_descuento'],2, ',', '.');
				$subtotalCiva   = $subtotalSiva;

				$colX  = 5;
				$this->SetXY($colX, $filaY);
				$this->SetFont('Arial', '', 9);
				$anchoCol = 25;
				$this->MultiCell($anchoCol, 5,$codigo, 0, 'L');
				$colX += $anchoCol;
				$this->SetXY($colX, $filaY);

				$anchoCol = 60;
				$this->SetFont('Arial', '', $arrVal['fuente']);
				$this->MultiCell($anchoCol, $arrVal['altoCampo'], $nombre, 0, 'L');
				$colX += $anchoCol;
				$this->SetXY($colX, $filaY);

				$this->SetFont('Arial', '', 9);

				$anchoCol = 12;
				$this->MultiCell($anchoCol, $alto, $cantidad, 0, 'C');
				$colX += $anchoCol;
				$this->SetXY($colX, $filaY);

				$anchoCol = 18;
				$this->MultiCell($anchoCol, $alto, $unidad, 0, 'C');
				$colX += $anchoCol;
				$this->SetXY($colX, $filaY);

				$anchoCol = 25;
				$this->MultiCell($anchoCol, $alto, $precioUnitario, 0, 'R');
				$colX += $anchoCol;
				$this->SetXY($colX, $filaY);

				$anchoCol = 15;
				$this->MultiCell($anchoCol, $alto, $descuentoPorc, 0, 'R');
				$colX += $anchoCol;
				$this->SetXY($colX, $filaY);

				$anchoCol = 20;
				$this->MultiCell($anchoCol, $alto, $montoDescuento, 0, 'R');
				$colX += $anchoCol;
				$this->SetXY($colX, $filaY);

				$anchoCol = 25;
				$this->MultiCell($anchoCol, $alto, $subtotalCiva, 0, 'R');
			}
			$this->Line(5, $filaY - 2, 205, $filaY - 2);
			if ($filaY > 220) {
				$this->AddPage();
				$cont  = 0;
				$filaY = 93;
			}else{
				$filaY += ($alto + 2);
			}
		}
		return $this->Output($this->salida);
	}

	/**
	 * @return void
	 */
	function Footer(): void
	{
		$presupuesto = $this->comprobante['cabecera'];
		$this->SetY(-62);
		$y = $this->GetY();

		//rectángulo: totales del comprobante
		$this->Rect(5, 235, 200, 45);
		$this->SetFont('Arial', 'B', 9);
		if ($this->discrimino) {
			$montoN = number_format($presupuesto['total_neto'], 2, ',', '.');
			$texto  = 'Importe Neto Gravado: ';
		}else{
			$montoN = number_format($presupuesto['total_final'], 2, ',', '.');
			$texto  = 'Subtotal: ';
		}
		$simboloMoneda = '$ ';
		if ($this->comprobante['cabecera']['moneda'] === 'dolar') {
			$this->SetXY(5,$y + 6);
			$simboloMoneda = 'US$ ';
			$cotizacion = number_format($this->comprobante['cabecera']['cotizacion'], 2, ',', '.');
			$textoAclaracion = StringUtil::utf8('Aclaración: para realizar el cambio se tomará la cotización oficial del dólar venta Banco Nación al momento del pago, hoy $' . $cotizacion);
			$this->MultiCell(105,4, $textoAclaracion,0,'L');
		}
		$this->SetXY(5, $y);
		$this->MultiCell(125,6, 'Observaciones:',0,'L');

		$this->SetXY(130, $y);
		$this->MultiCell(50, 8, $texto, 0, 'R');

		$this->SetXY(180,$y);
		$this->SetFont('Arial','',9);
		$this->MultiCell(25,8,  utf8_decode($simboloMoneda . $montoN),0,'R');

		// Observaciones
		$this->SetXY(5,$y + 5);
		$this->SetFont('Arial','',9);
		$this->MultiCell(125,4,  StringUtil::utf8($presupuesto['observaciones']),0,'L');

		$this->Ln(1);

		$x = 130;
		$y += 8;
		if ($this->discrimino){
			foreach ($this->movimIVA as $mov) {
				$montoIva = number_format(floatval($mov['montoIva']),2, ',', '.');
				$this->SetXY($x,$y);
				$this->SetFont('Arial','B',9);
				$this->MultiCell(50,8, StringUtil::utf8($mov['nombre']),0,'R');

				$this->SetXY($x + 50,$y);
				$this->SetFont('Arial','',8);
				$this->MultiCell(25,8,  StringUtil::utf8('$'.$montoIva),0,'R');
				$y=$y+8;
			}
		}

		$y = 270;
		$this->SetXY($x-20 ,$y);
		$this->SetFont('Arial','B',16);
		$totalFinal = 'Total: $ ' . number_format($this->comprobante['cabecera']['total_final'],2, ',', '.');
		if ($this->comprobante['cabecera']['moneda'] == "dolar") {
			$totalFinal = 'Total: US$ ' . number_format($this->comprobante['cabecera']['total_final'],2, ',', '.');
		}

		$this->MultiCell(95,10, $totalFinal ,0,'R');

	}



	/**
	 * @return array
	 */
	public function getComprobante(): array
	{
		return $this->comprobante;
	}

	/**
	 * @param array $comprobante
	 * @return PresupuestoPdf
	 */
	public function setComprobante(array $comprobante): PresupuestoPdf
	{
		$this->comprobante = $comprobante;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getEmpresa(): array
	{
		return $this->empresa;
	}

	/**
	 * @param array $empresa
	 * @return PresupuestoPdf
	 */
	public function setEmpresa(array $empresa): PresupuestoPdf
	{
		$this->empresa = $empresa;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getMovimIVA(): array
	{
		return $this->movimIVA;
	}

	/**
	 * @param array $movimIVA
	 * @return PresupuestoPdf
	 */
	public function setMovimIVA(array $movimIVA): PresupuestoPdf
	{
		$this->movimIVA = $movimIVA;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isDiscrimino(): bool
	{
		return $this->discrimino;
	}

	/**
	 * @param bool $discrimino
	 * @return PresupuestoPdf
	 */
	public function setDiscrimino(bool $discrimino): PresupuestoPdf
	{
		$this->discrimino = $discrimino;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getEntrega(): int
	{
		return $this->entrega;
	}

	/**
	 * @param int $entrega
	 * @return PresupuestoPdf
	 */
	public function setEntrega(int $entrega): PresupuestoPdf
	{
		$this->entrega = $entrega;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSalida(): string
	{
		return $this->salida;
	}

	/**
	 * @param string $salida
	 * @return PresupuestoPdf
	 */
	public function setSalida(string $salida): PresupuestoPdf
	{
		$this->salida = $salida;
		return $this;
	}

	/**
	 * Devuelve el nombre de estado
	 * @param $idEstado
	 * @return string
	 */
	private function getEstado($idEstado):string {
		$estadoNombre = '';
		switch ($idEstado){
			case 10:
				$estadoNombre = 'Creado';
				break;
			case 20:
				$estadoNombre = 'Aprobado';
				break;
			case 30:
				$estadoNombre = 'Entregado';
				break;
			case 40:
				$estadoNombre = 'Facturado';
				break;
			case 50:
				$estadoNombre = 'Cobrado';
				break;
			case 90:
				$estadoNombre = 'Anulado';
				break;
		}
		return $estadoNombre;
	}
}
