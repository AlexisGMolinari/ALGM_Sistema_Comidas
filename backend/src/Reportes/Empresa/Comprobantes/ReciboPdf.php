<?php

namespace App\Reportes\Empresa\Comprobantes;

use App\Utils\StringUtil;
use DateTime;
use Fpdf\Fpdf;

class ReciboPdf extends Fpdf
{

	protected array $recibo = [];
	protected array $empresa = [];
	protected array $facturasimputadas = [];
	protected bool $discrimino = false;
	protected array $cliente = [];


	/**
	 * variable para setear si el PDF sale por navegador o se adjunta a un email
	 * @var string
	 */
	private string $Salida = 'I';

	/**
	 * Cabecera del ticket
	 * @return void
	 */
	function Header(): void
	{
		$recibo  = $this->recibo;
		$empresa = $this->empresa['empresa'];
		$usuario = $this->empresa['usuario'];
		$cliente = $this->cliente;

		$this->Rect(5, 5, 200, 55);
		//      rectangulo: cliente
		$this->Rect(5, 61, 200, 24);
		//      rectangulo: titulos de detalle del comprobante
		$this->Rect(5, 86, 200, 5);

		// ----------------------------     columna izquierda Cabecera ------------------------
		$columnaIzq = 10;

		// ---------- analizo si tiene logo lo muestro sino muestro en grande el nombre de la empresa
		if ($empresa['logo']) {
			$logo = 'logos/' . $empresa['logo'];

			$this->Image($logo, $columnaIzq, 7, 50);

			$this->SetXY($columnaIzq, 34);
			$this->SetFont('Arial', 'B', 12);
			$empresaNombre = StringUtil::utf8($usuario['nombre']);
			$this->MultiCell(90, 10, $empresaNombre, 0, 'L');

			$this->SetXY($columnaIzq, 40);
			$this->SetFont('Arial', '', 10);
			$domicilio = "Domicilio: " . $empresa['direccion'] . ' - ' . $empresa['localidad'];
			$this->MultiCell(100, 10, StringUtil::utf8($domicilio), 0, 'L');

			$this->SetXY($columnaIzq, 45);
			$categoriaIvaEmpresa = 'Condición frente al IVA: ' . $empresa['categoriaIVA'];
			$this->Cell(100, 10, StringUtil::utf8($categoriaIvaEmpresa), 0, 0);

		} else {
			$this->SetXY($columnaIzq, 12);
			$this->SetFont('Arial', 'B', 18);
			$empresaNombre = StringUtil::utf8($usuario['nombre']);
			$this->MultiCell(90, 10, $empresaNombre, 0, 'L');

			$this->SetXY($columnaIzq, 35);
			$this->SetFont('Arial', '', 10);
			$domicilio = "Domicilio: " . $empresa['direccion'] . ' - ' . $empresa['localidad'];
			$this->MultiCell(100, 10, StringUtil::utf8($domicilio), 0, 'L');

			$this->SetXY($columnaIzq, 41);
			$categoriaIvaEmpresa = 'Condición frente al IVA: ' . $empresa['categoriaIVA'];
			$this->Cell(100, 10, StringUtil::utf8($categoriaIvaEmpresa), 0, 0);
		}
		// -------------------------- Columna Derecha ------------------------------------------

		$columnaDer = 120;
		$this->SetXY($columnaDer,11);
		$comprobante = 'RECIBO';
		$this->SetFont('Arial','B',18);
		$this->MultiCell(85,10,  StringUtil::utf8($comprobante),0,'L','');

		$this->SetXY($columnaDer,20);
		$this->SetFont('Arial','B',12);
		$nroRecibo = 'Nro: ' . str_pad((int) $recibo['id'],8,"0",STR_PAD_LEFT);
		$this->MultiCell(60,10,  StringUtil::utf8($nroRecibo),0);


		$this->SetXY($columnaDer,33);
		$fechaEmision = 'Fecha de Emisión: ' . DateTime::createFromFormat('Y-m-d H:i:s', $recibo['fecha'])->format('d/m/Y');
		$this->SetFont('Arial','B',9);
		$this->MultiCell(0,10,  StringUtil::utf8($fechaEmision),0);

		$this->SetXY($columnaDer,38);
		$cuit = substr($empresa['cuit'], 0,2) . '-' . substr($empresa['cuit'], 2,8) . '-' . substr($empresa['cuit'], 10,1);
		$cuit = 'CUIT: ' . $cuit;
		$this->MultiCell(90,10, $cuit ,0);

		$this->SetXY($columnaDer,43);
		$iibb = 'Ingresos Brutos: ' . $empresa['iibb'];
		$this->MultiCell(90,10,  StringUtil::utf8($iibb),0);

		$this->SetXY($columnaDer,48);
		$fechaInicio = 'Fecha de Inicio de Actividades: ';
		if ($empresa['fecha_inicio']){
			$fechaInicio .= \DateTime::createFromFormat('Y-m-d', $empresa['fecha_inicio'])->format('d/m/Y');

		}
		$this->MultiCell(90,10,  StringUtil::utf8($fechaInicio),0);

		// -------------------------- CLIENTE -> Columna Izquierda ------------------------------------------

		$this->SetXY($columnaIzq,62);
		//si es consumidor final muestro el DNI
		if (strlen($cliente['numero_documento']) < 11){
			$clienteCuit = $cliente['numero_documento'];
		}else{
			$clienteCuit = substr($cliente['numero_documento'], 0,2) . '-'
				. substr($cliente['numero_documento'], 2,8) . '-'
				. substr($cliente['numero_documento'], 10,1);
		}
		$this->MultiCell(100,10,  StringUtil::utf8('CUIT/DNI: ' .$clienteCuit),0);

		// -------------------------- CLIENTE -> Columna Derecha  ------------------------------------------
		$this->SetXY($columnaDer-15,62);
		$clienteRazon = 'Razón Social: ' . $cliente['nombre'];
		$this->MultiCell(100,10,  StringUtil::utf8($clienteRazon),0);

		// -------------------------- CLIENTE -> completo  ------------------------------------------
		$this->SetXY($columnaIzq,68);
		$clienteDomi = 'Domicilio: ' . $cliente['domicilio'] . ' - (' . $cliente['codigo_postal'] .') - '
			. $cliente['localidad'] . ' - ' . $cliente['provincia_nombre'];
		$this->MultiCell(190,10,  StringUtil::utf8($clienteDomi),0);

		$this->SetXY($columnaIzq,74);
		$clienteTel = 'Tel: ' . $cliente['telefono'] . ' / Email: ' . $cliente['email'];
		$this->MultiCell(190,10,  StringUtil::utf8($clienteTel),0);
	}

	/**
	 * Función que genera los movimientos y exporta el objeto PDF
	 * @return string
	 */
	public function GenerarPdf(): string
	{
		$columnaIzq = 5;
		$this->AddPage();
		$this->SetTitle(StringUtil::utf8('Factura $imple - ' . $this->empresa['usuario']['nombre'] . ' - Impresión de Recibos'));


		// -------------------------- Título -> Facturas imputadas ------------------------------------------
		$this->SetFont('Arial', '', 9);
		$this->SetXY($columnaIzq,84);
		$this->MultiCell(190,10,  StringUtil::utf8('FACTURAS IMPUTADAS'),0,'C');

		$y = 94;
		$this->SetXY($columnaIzq,$y);
		$this->SetFont('Arial', '', 10);
		foreach ($this->facturasimputadas as $factu) {
			$puntoVta = str_pad((int) $factu['punto_venta'],4,"0",STR_PAD_LEFT);
			$nroFact  = str_pad((int) $factu['numero'],8,"0",STR_PAD_LEFT);
			$fechaFac = \DateTime::createFromFormat('Y-m-d H:i:s', $factu['fechaFac']);
			$comprobNombre = StringUtil::utf8($factu['nombre']);
			$celdaUno = $comprobNombre . ' - ' . $puntoVta . '-' . $nroFact . '  -  '. $fechaFac->format('d/m/Y');

			$totalFac = (float)$factu['total_final'];
			$importeRec = (float)$factu['importe'];
			if ((int)$factu['concepto'] === 1){
				$totalFac = ($totalFac) * (-1);
			}

			$totalSal   = (float)$factu['saldoTotalFc'];

			if ($totalSal > $totalFac){    // si pagó demás una factura (sería saldo a favor) le pongo el total de la factura
				if ((int)$factu['concepto'] !== 1) {
					$totalSal = $totalFac;
				}
			}

			$totalFactura   = number_format($totalFac,2, ',', '.');
			$totalSaldo     = number_format($totalSal,2, ',', '.');

			// 3 columnas
			$this->Cell(95,6, $celdaUno ,0,0,'L');
			$this->Cell(35,6, 'Total: $ ' . $totalFactura ,0,0,'R');
			$this->Cell(35,6, 'Saldo: $ ' . $totalSaldo ,0,0,'R');
			$this->Cell(35,6, 'Entrega: $ ' . $importeRec ,0,1,'R');
			$this->SetX($columnaIzq);
			$y += 7;
		}

		// -------------------------- Título -> Pagos ------------------------------------------
		$this->SetFont('Arial', 'b', 9);
		$this->SetXY($columnaIzq,$y);
		$this->MultiCell(200,9,  StringUtil::utf8('P A G O'),1,'C');
		$y += 10;
		$this->SetXY($columnaIzq,$y);
		$this->SetFont('Arial', '', 10);
		$importeRecibo  = 'Total Entregado: $ '. number_format($this->recibo['importe'],2, ',', '.');
		$this->MultiCell(140,6,StringUtil::utf8($this->recibo['detalle']) ,0);
		$this->SetXY($columnaIzq+140,$y);
		$this->SetFont('Arial', 'b', 12);
		$this->MultiCell(60,10, $importeRecibo ,0,'R');

		return $this->Output($this->Salida);
	}


	/**
	 * @param array $recibo
	 * @return $this
	 */
	public function setRecibo(array $recibo): ReciboPdf
	{
		$this->recibo = $recibo;
		return $this;
	}

	/**
	 * @param array $empresa
	 * @return $this
	 */
	public function setEmpresa(array $empresa): ReciboPdf
	{
		$this->empresa = $empresa;
		return $this;
	}

	/**
	 * @param array $facturasimputadas
	 * @return $this
	 */
	public function setFacturasimputadas(array $facturasimputadas): ReciboPdf
	{
		$this->facturasimputadas = $facturasimputadas;
		return $this;
	}

	/**
	 * @param bool $discrimino
	 * @return $this
	 */
	public function setDiscrimino(bool $discrimino): ReciboPdf
	{
		$this->discrimino = $discrimino;
		return $this;
	}

	/**
	 * @param array $cliente
	 * @return $this
	 */
	public function setCliente(array $cliente): ReciboPdf
	{
		$this->cliente = $cliente;
		return $this;
	}

	/**
	 * @param string $Salida
	 * @return $this
	 */
	public function setSalida(string $Salida): ReciboPdf
	{
		$this->Salida = $Salida;
		return $this;
	}

}
