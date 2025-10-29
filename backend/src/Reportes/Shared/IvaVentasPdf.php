<?php


namespace App\Reportes\Shared;


use App\Utils\StringUtil;
use App\Utils\Utils;
use Fpdf\Fpdf;
//use phpDocumentor\Reflection\Types\This;

class IvaVentasPdf extends Fpdf
{

    /**
     * variable para setear si el PDF sale por navegador o se adjunta a un email
     */
    private string $Salida = 'I';

    /**
     * Movimientos del subdiario
     */
    private array $movimientos = [];


    /**
     * se le asigna el nombre del archivo a guardar
     */
    private string $nombreArchivo = 'ivaVenta.pdf';


    /**
     *variable para los datos de la cabecera
     */
    protected array $datosCabecera = [];


    /**
     * array de totales por categoría de Clientes
     */
    protected array $totalesPorCategoria = [];

    /**
     * totales por categorías y tasa de iva (Tabla final)
     */
    protected array $totalesPorCategoriaYTasa = [];




    /**
     * función que arma la cabecera del iva venta
     */
    function Header()
    {
        $this->SetFont('Arial','B',14);
        $this->Cell(200,7, StringUtil::utf8($this->datosCabecera['nombreEmpresa']),0,0,'L');
        $mens = StringUtil::utf8('Página '.$this->PageNo().'/{nb}');
        $this->SetFont('Arial','',10);
        $this->Cell(80,7, $mens,0,1,'R');
        $this->SetFont('Arial','B',12);
        $this->Cell(93,7, $this->datosCabecera['cuit'],0,0,'L');
        $this->SetFont('Arial','B',14);
        $this->Cell(93,7, 'Libro IVA Ventas',0,0,'C');
        $mens = $this->datosCabecera['fechaDesde'] . ' - ' . $this->datosCabecera['fechaHasta'];
        $this->SetFont('Arial','',12);
        $this->Cell(93,7, $mens,0,1,'R');

        $this->Ln(6);
        $this->SetFont('Arial','',9);
        $this->Cell(17,6, 'Fecha',1,0,'C');
        $this->Cell(35,6, 'Comprobante',1,0,'C');
        $this->Cell(23,6, StringUtil::utf8('Nro. Comprob.'),1,0,'C');
        $this->Cell(74,6, 'Cliente',1,0,'C');
        $this->Cell(23,6, 'CUIT / DNI',1,0,'C');
        $this->Cell(21,6, 'Neto',1,0,'C');
        $this->SetFont('Arial','',8);
        $this->Cell(10,6, 'Exento',1,0,'C');
        $this->SetFont('Arial','',9);
        $this->Cell(15,6, 'No Grav.',1,0,'C');
        $this->Cell(20,6, 'IVA',1,0,'C');
        $this->Cell(18,6, 'Imp. Int.',1,0,'C');
        $this->Cell(22,6, 'Total',1,1,'C');
    }


    /**
     * función que genera los movmientos y exporta el objeto PDF
     * @return string
     */
    public function GenerarPdf()
    {
        $this->AliasNbPages();
        $this->AddPage('L','A4');
        $this->SetAutoPageBreak(false);
        $this->SetTitle(StringUtil::utf8('Factura Simple - Libro IVA Venta: ' . $this->datosCabecera['nombreEmpresa']));

        $this->SetFont('Arial','',8);
        $this->Ln(2);

        $x      = 10;
        $y      = 37;
        $conta  = 0;                     //cuento la cantidad de Renglones
        $this->setXY($x,$y);             // seteo donde empiezan los movimientos

        $totalNeto      = 0.00;
        $totalExcento   = 0.00;
        $totalIVA       = 0.00;
        $totalFinal     = 0.00;
        $totalImpInterno = 0.00;
        $totalNOGravado = 0.00;
        foreach ($this->movimientos as $mov) {

            $this->setArrCategoria($mov['categoriaIvaId'], $mov['categoriaIva']);
            if(intval($mov['concepto']) === 2){
                $signo          = '';
                $totalNeto      = $totalNeto + $mov['total_neto'];
                $totalExcento   = $totalExcento + $mov['total_exento'];
                $totalIVA       = $totalIVA + $mov['total_iva'];
                $totalFinal     = $totalFinal + $mov['total_final'];
                $totalImpInterno = $totalImpInterno + $mov['impuesto_interno'];
                $totalNOGravado = $totalNOGravado + $mov['total_no_gravado'];
            }else{
                $signo          = '-';
                $totalNeto      = $totalNeto - $mov['total_neto'];
                $totalExcento   = $totalExcento - $mov['total_exento'];
                $totalIVA       = $totalIVA - $mov['total_iva'];
                $totalFinal     = $totalFinal - $mov['total_final'];
                $totalImpInterno = $totalImpInterno - $mov['impuesto_interno'];
                $totalNOGravado  = $totalNOGravado - $mov['total_no_gravado'];
            }

            $this->MultiCell(17,7, $mov['fecha'] ,0,'',0);
            $x = $x + 17;
            $this->setXY($x,$y);

            $this->MultiCell(35,7, StringUtil::utf8($mov['nombre']) ,0,'L',0);
            $x = $x + 35;
            $this->setXY($x,$y);

            $nroComprobante = str_pad($mov['punto_venta'],4,'0',STR_PAD_LEFT) . '-' . str_pad($mov['numero'],8,'0',STR_PAD_LEFT);
            $this->MultiCell(23,7, $nroComprobante ,0,'L',0);
            $x = $x + 23;
            $this->setXY($x,$y);

            $nombre = StringUtil::utf8($mov['nombre_fantasia']);
            if (strlen($nombre) < 40){
                $this->MultiCell(74,7, $nombre ,0,'L',0);
            }
            if (strlen($nombre) > 40 && strlen($nombre) <= 80){
                $this->MultiCell(74,4, $nombre ,0,'L',0);
            }
            if (strlen($nombre) > 80){
                $this->MultiCell(74,3, $nombre ,0,'L',0);
            }
            $x = $x + 74;
            $this->setXY($x,$y);

            $dniCuit = Utils::getCuitDniFormat($mov['numero_documento']);
            $this->MultiCell(23,7, $dniCuit ,0,'R');
            $x = $x + 23;
            $this->setXY($x,$y);

            // si es RI discrimino el IVA en el reporte sino pongo los totales finales
            $montoNeto      = $signo . number_format($mov['total_neto'],2,',','.');
            $montoExento    = $signo . number_format($mov['total_exento'],2,',','.');
            $montoIva       = $signo . number_format($mov['total_iva'],2,',','.');
            $montoImpInterno = $signo . number_format($mov['impuesto_interno'],2,',','.');
            $montoFinal     = $signo . number_format($mov['total_final'],2,',','.');
            $montoNoGravado = $signo . number_format($mov['total_no_gravado'],2,',','.');
            if ( intval($this->datosCabecera['categoria_iva_id']) !== 1 ){
                $montoNeto      = $signo . number_format($mov['total_final'],2,',','.');
                $montoExento    = $signo . number_format($mov['total_exento'],2,',','.');
                $montoIva       = '0,00';
                $montoFinal     = $signo . number_format($mov['total_final'],2,',','.');
                $totalIVA       = 0;
            }

            $this->MultiCell(21,7, $montoNeto  ,0,'R');
            $x = $x + 21;
            $this->setXY($x,$y);

            $this->MultiCell(10,7, $montoExento ,0,'R');
            $x = $x + 10;
            $this->setXY($x,$y);

            $this->MultiCell(15,7, $montoNoGravado,0,'R');
            $x = $x + 15;
            $this->setXY($x,$y);

            $this->MultiCell(20,7, $montoIva,0,'R');
            $x = $x + 20;
            $this->setXY($x,$y);

            $this->MultiCell(18,7, $montoImpInterno,0,'R');
            $x = $x + 18;
            $this->setXY($x,$y);

            $this->MultiCell(22,7, $montoFinal ,0,'R');
            $x = $x + 22;
            $this->setXY($x,$y);

            $this->acumuloArrCategoria($mov);
            $conta++;

            if ($conta > 26) {
                //salto de pagina
                $this->AddPage('L','A4');
                $x      = 10;
                $y      = 37;
                $conta  = 0;
                $this->setXY($x,$y);
            } else {
                //siguiente renglon
                $y  = $y + 6;
                $x  = 10;
                $this->setXY($x,$y);
            }
        }

        $this->SetFont('Arial','B',9);
        $x = 10;
        $y = $y + 3;
        $this->setXY($x,$y);
        $this->MultiCell(172,7, 'Totales: ',0,'R');
        $x = $x + 172;
        $this->setXY($x,$y);

        // si no es RI el monto total y el neto son lo mismo
        if ( intval($this->datosCabecera['categoria_iva_id']) !== 1 ){
            $totalNeto = $totalFinal;
        }

        $this->MultiCell(21,7, number_format($totalNeto,2,',','.') ,0,'R');
        $x = $x + 21;
        $this->setXY($x,$y);

        $this->MultiCell(10,7, number_format($totalExcento,2,',','.') ,0,'R');
        $x = $x + 10;
        $this->setXY($x,$y);

        $this->MultiCell(15,7, number_format($totalNOGravado,2,',','.') ,0,'R');
        $x = $x + 15;
        $this->setXY($x,$y);


        $this->MultiCell(20,7, number_format($totalIVA,2,',','.') ,0,'R');
        $x = $x + 20;
        $this->setXY($x,$y);

        $this->MultiCell(18,7, number_format($totalImpInterno,2,',','.') ,0,'R');
        $x = $x + 18;
        $this->setXY($x,$y);

        $this->MultiCell(22,7, number_format($totalFinal,2,',','.') ,0,'R');
        $x = $x + 22;
        $this->setXY($x,$y);

		$this->AddPage('L','A4');
		$x      = 10;
		$y      = 47;
		$conta  = 0;
		$this->setXY($x,$y);
		$this->SetFont('Arial','B',9);
		$reTotalNeto = 0.0;
		$retotalExento = 0.0;
		$retotalIva = 0.0;
		$retotalFinal = 0.0;
		$retotalImpInterno = 0.0;
		$retotalNOGravado = 0.0;
		foreach ($this->totalesPorCategoria as $totalesCategoria){
			$this->Cell(172,7, StringUtil::utf8($totalesCategoria['nombreCategoria']) ,1,0,'L');
			$this->Cell(21,7, number_format($totalesCategoria['totalNeto'],2,',','.')  ,1,0,'R');
			$this->Cell(10,7, number_format($totalesCategoria['totalExento'],2,',','.')  ,1,0,'R');
			$this->Cell(15,7, number_format($totalesCategoria['totalNOGravado'],2,',','.')  ,1,0,'R');
			$this->Cell(20,7, number_format($totalesCategoria['totalIva'],2,',','.')  ,1,0,'R');
			$this->Cell(18,7, number_format($totalesCategoria['impuesto_interno'],2,',','.')  ,1,0,'R');
			$this->Cell(22,7, number_format($totalesCategoria['totalFinal'],2,',','.')  ,1,1,'R');
			$reTotalNeto += $totalesCategoria['totalNeto'];
			$retotalExento += $totalesCategoria['totalExento'];
			$retotalIva += $totalesCategoria['totalIva'];
			$retotalFinal += $totalesCategoria['totalFinal'];
			$retotalFinal += $totalesCategoria['impuesto_interno'];
			$retotalNOGravado += $totalesCategoria['totalNOGravado'];
		}
		$this->SetFont('Arial','B',9);
		$this->Cell(172,7, '' ,1,0,'R');
		$this->Cell(21,7, number_format($reTotalNeto,2,',','.')  ,1,0,'R');
		$this->Cell(10,7, number_format($retotalExento,2,',','.')  ,1,0,'R');
		$this->Cell(15,7, number_format($retotalNOGravado,2,',','.')  ,1,0,'R');
		$this->Cell(20,7, number_format($retotalIva,2,',','.')  ,1,0,'R');
		$this->Cell(18,7, number_format($retotalImpInterno,2,',','.')  ,1,0,'R');
		$this->Cell(22,7, number_format($retotalFinal,2,',','.')  ,1,1,'R');
		$this->Ln(10);

		// muestro totales por categoría y por TASA de IVA
		foreach ($this->totalesPorCategoriaYTasa as $item){
			$this->Cell(90,7, StringUtil::utf8($item['nombre']) ,1,0,'L');
			$tasa = $item['tasaNombre'] . ' - ' . $item['tasa'] . ' %';
			$this->Cell(82,7, StringUtil::utf8($tasa) ,1,0,'L');
			$this->Cell(21,7, number_format($item['neto'],2,',','.')  ,1,0,'R');
			$this->Cell(10,7, '' ,1,0,'R');
			$this->Cell(15,7, '' ,1,0,'R');
			$this->Cell(20,7, number_format($item['iva'],2,',','.')  ,1,0,'R');
			$this->Cell(18,7, ''  ,1,0,'R');
			$this->Cell(22,7, number_format($item['total'],2,',','.')  ,1,1,'R');
		}

        return $this->Output($this->nombreArchivo, $this->Salida);
    }

    /**
     * función pié del reporte PDF
     */
    function Footer()
    {}

    /**
     * arma un array por cada una de las categorías de IVA del cliente
     * @param int $idCategoria
     * @param string $nombreCategoria
     */
    public function setArrCategoria(int $idCategoria, string $nombreCategoria): void
    {
        if (!isset($this->totalesPorCategoria[$idCategoria])){
            $this->totalesPorCategoria[$idCategoria]['nombreCategoria'] = $nombreCategoria;
            $this->totalesPorCategoria[$idCategoria]['totalNeto'] = 0.0;
            $this->totalesPorCategoria[$idCategoria]['totalExento'] = 0.0;
            $this->totalesPorCategoria[$idCategoria]['totalIva'] = 0.0;
            $this->totalesPorCategoria[$idCategoria]['totalFinal'] = 0.0;
            $this->totalesPorCategoria[$idCategoria]['impuesto_interno'] = 0.0;
            $this->totalesPorCategoria[$idCategoria]['totalNOGravado'] = 0.0;
        }
    }

    /**
     * actualiza los acumuladores de totales por cada categoría
     * @param array $movimiento
     */
    public function acumuloArrCategoria(array $movimiento): void
    {
        $idCategoria = (int)$movimiento['categoriaIvaId'];
        if (intval($movimiento['concepto']) === 2){
            $this->totalesPorCategoria[$idCategoria]['totalNeto'] += (float)$movimiento['total_neto'];
            $this->totalesPorCategoria[$idCategoria]['totalExento'] += (float)$movimiento['total_exento'];
            $this->totalesPorCategoria[$idCategoria]['totalIva'] += (float)$movimiento['total_iva'];
            $this->totalesPorCategoria[$idCategoria]['totalFinal'] += (float)$movimiento['total_final'];
            $this->totalesPorCategoria[$idCategoria]['impuesto_interno'] += (float)$movimiento['impuesto_interno'];
            $this->totalesPorCategoria[$idCategoria]['totalNOGravado'] += (float)$movimiento['total_no_gravado'];
        }else{
            $this->totalesPorCategoria[$idCategoria]['totalNeto'] -= (float)$movimiento['total_neto'];
            $this->totalesPorCategoria[$idCategoria]['totalExento'] -= (float)$movimiento['total_exento'];
            $this->totalesPorCategoria[$idCategoria]['totalIva'] -= (float)$movimiento['total_iva'];
            $this->totalesPorCategoria[$idCategoria]['totalFinal'] -= (float)$movimiento['total_final'];
            $this->totalesPorCategoria[$idCategoria]['impuesto_interno'] -= (float)$movimiento['impuesto_interno'];
            $this->totalesPorCategoria[$idCategoria]['totalNOGravado'] -= (float)$movimiento['total_no_gravado'];
        }
    }






    /**
     * @param string $Salida
     */
    public function setSalida(string $Salida): void
    {
        $this->Salida = $Salida;
    }


    /**
     * @param string $nombreArchivo
     */
    public function setNombreArchivo(string $nombreArchivo): void
    {
        $this->nombreArchivo = $nombreArchivo;
    }


    /**
     * @param array $movimientos
     */
    public function setMovimientos(array $movimientos): void
    {
        $this->movimientos = $movimientos;
    }

    /**
     * @param $datosCabecera array
     */
    public function setDatosCabecera(array $datosCabecera){
        $this->datosCabecera = $datosCabecera;
    }

    /**
     * @param array $totalesPorCategoriaYTasa
     */
    public function setTotalesPorCategoriaYTasa(array $totalesPorCategoriaYTasa): void
    {
        $this->totalesPorCategoriaYTasa = $totalesPorCategoriaYTasa;
    }
}
