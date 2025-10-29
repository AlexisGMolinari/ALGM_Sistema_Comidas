<?php


namespace App\Reportes\Empresa\Comprobantes;


use App\Utils\StringUtil;
use Fpdf\Fpdf;

class ResumenDeVentaPdf extends Fpdf
{

    /**
     * variable para setear si el PDF sale por navegador o se adjunta a un email
     * @var string
     */
    private $Salida = 'I';

    /**
     * Movimientos del subdiario
     * @var array
     */
    private $movimientos = [];


    /**
     * @var string
     * se le asigna el nombre del archivo a guardar
     */
    private $nombreArchivo = 'resumen_de_ventas.pdf';


    /**
     *variable para los datos de la cabecera
     * @var array
     */
    protected $datosCabecera = [];

    protected $datosMediosPagos = [];

    protected $totalesPorSubFlia = [];

    /**
     * función que arma la cabecera del iva venta
     */
    function Header()
    {
        // --------------- Cabecera columna Izquierda
        $columnaIzq = 10;
        // ---------- analizo si tiene logo lo muestro sino muestro en grande el nombre de la empresa
        if ($this->datosCabecera['logo']) {
            $logo = 'logos/' . $this->datosCabecera['logo'];
            $this->Image($logo, $columnaIzq, 7, 60);
        }else{
            $this->SetXY($columnaIzq,14);
            $this->SetFont('Arial','B',18);
            $empresaNombre = StringUtil::utf8($this->datosCabecera['nombreEmpresa']);
            $this->MultiCell(100,$columnaIzq,  $empresaNombre,0,'L');
        }
        // --------------- Cabecera columna derecha
        $columnaDer =  100;
        $this->SetXY($columnaDer,14);
        $this->SetFont('Arial','B',18);
        $this->MultiCell(100,10,  'Resumen de Ventas',0,'R');

        $this->SetXY($columnaDer,25);
        $this->SetFont('Arial','B',11);
        $txtDesde = 'Desde: ' . $this->datosCabecera['fechaDesde'] . ' -> Hasta: ' . $this->datosCabecera['fechaHasta'];
        $this->MultiCell(100,7, $txtDesde  ,0,'R');

        $this->SetXY($columnaDer,31);
        $this->SetFont('Arial','',10);
        $this->MultiCell(100,7, 'Emitido: ' . date('d/m/Y H:i:s')  ,0,'R');
        $this->Ln(5);
    }

    /**
     * Función que genera los movimientos y exporta el objeto PDF
     * @return string
     */
    public function GenerarPdf(): string
    {
        $this->AliasNbPages();
        $this->AddPage('P','A4');
        $this->SetAutoPageBreak(false);
        $this->SetTitle(StringUtil::utf8('Factura Simple - Resumen de Ventas: ' . $this->datosCabecera['nombreEmpresa']));

        // totales
        $totalCantidad  = 0;
        $totalImporte   = 0;

        $this->SetFont('Arial','',9);
        $alto           = 6;
        $colX           = 10;
        $cont           = 0;
        $renglonesPag   = 36;
        $filaY          = 55;

        //cabecera movimientos
        $this->SetXY(10, 45);
        $this->SetFont('Arial', 'B', 11);
        $this->MultiCell(130, 10, 'Productos / Servicios', 1, 'C');

        $this->SetXY(140, 45);
        $this->MultiCell(30, 10, 'Cantidad', 1, 'C');

        $this->SetXY(170, 45);
        $this->MultiCell(30, 10, 'Importe $', 1, 'C');
        $this->Ln(5);


        foreach ($this->movimientos as $factm) {
            $cont           = $cont + 1;
            $nombre         = StringUtil::utf8($factm['producto_nombre']);
            $cantidad       = round($factm['cantidad'],2);
            $importe        = number_format((float)$factm['importe'],2, ',', '.');

            $this->SetXY($colX, $filaY);

            $anchoCol = 130;
            if (strlen($nombre) < 40) {
                $this->SetFont('Arial','',9);
            } elseif (strlen($nombre) > 40 && strlen($nombre) <= 80) {
                $this->SetFont('Arial', '', 8);
            } elseif (strlen($nombre) > 80 && strlen($nombre) <= 120) {
                $this->SetFont('Arial', '', 8);
            } elseif (strlen($nombre) > 120) {
                $nombre = substr($nombre, 0,120);
                $this->SetFont('Arial', '', 6);
            }
            $this->MultiCell($anchoCol, $alto, $nombre, 'B', 'L');
            $colX += $anchoCol;
            $this->SetXY($colX, $filaY);

            $this->SetFont('Arial','',9);
            // cantidad
            $anchoCol = 30;
            $this->MultiCell($anchoCol, $alto, $cantidad, 'B', 'R');
            $colX += $anchoCol;
            $this->SetXY($colX, $filaY);

            // importe
            $anchoCol = 30;
            $this->MultiCell($anchoCol, $alto, $importe, 'B', 'R');
            $colX += $anchoCol;
            $this->SetXY($colX, $filaY);


            if ($cont> $renglonesPag) {
                $this->AddPage();
                $cont  = 0;
                $filaY = 55;
            }else{
                $filaY += $alto;
            }
            $colX           = 10;
            $totalCantidad += $cantidad;
            $totalImporte  += (float)$factm['importe'];
        }

        $filaY = $filaY + 10;
        $this->SetXY($colX, $filaY);
        $anchoCol = 130;
        $this->SetFont('Arial', 'B', 11);
        $this->MultiCell($anchoCol + 5, $alto, 'Total -----> ', 0, 'R');
        $colX += $anchoCol;
        $this->SetXY($colX, $filaY);
        // total cantidad
        $anchoCol = 25;
        $this->MultiCell($anchoCol, $alto,  number_format($totalCantidad,0, ',', '.'), 0, 'C');
        $colX += $anchoCol;
        $this->SetXY($colX, $filaY);

        // total Importe
        $anchoCol = 35;
        $txtImpo = '$ ' . number_format($totalImporte,2, ',', '.');
        $this->MultiCell($anchoCol, $alto, $txtImpo , 0, 'R');

        $filaY = $filaY + 20;
        if ($this->GetY() > 220 ){
            $this->AddPage();
            $filaY = 75;
        }
        $this->SetXY(10, $filaY);
        $this->SetFont('Arial', 'B', 11);
        $this->MultiCell(190, 6, 'Detalle de medios de pagos:', 'B', 'L');

        $filaY = $filaY + 6;
        $this->SetXY(10, $filaY);
        foreach ($this->datosMediosPagos as $mediosPago) {
            $this->Cell(160,8,StringUtil::utf8($mediosPago['nombre']),1,0,'L');
            $txtImpo = '$ ' . number_format($mediosPago['totalMP'],2, ',', '.');
            $this->Cell(30,8,$txtImpo,1,1,'R');
        }

        $this->AddPage();
        $filaY = 45;

        $this->SetXY(10, $filaY);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(60, 6, 'Familias:', 0, 0,'L');
        $this->Cell(60, 6, 'Sub-Familia:', 0, 0,'L');
        $this->Cell(30, 6, 'Total Cant.', 0, 0,'C');
        $this->Cell(40, 6, 'Total Importe $', 0, 1,'C');

        $familiaId = (int)$this->totalesPorSubFlia[0]['id'];
        $this->setFillColor(230,230,230);
        $this->Cell(190, 6, StringUtil::utf8($this->totalesPorSubFlia[0]['familia']), 1, 1,'L', 1);

        $totalCantidad = 0;
        $totalImporte = 0;
        foreach ($this->totalesPorSubFlia as $subFamilia) {
            if ($familiaId === (int)$subFamilia['id']){
                $this->SetFont('Arial', '', 10);
                $this->Cell(60, 6, '', 1, 0,'L');
                $this->Cell(60, 6, StringUtil::utf8($subFamilia['nombre']), 1, 0,'L');

                $txtCantidad = number_format($subFamilia['cantidad'],2, ',', '.');
                $this->Cell(30,6,$txtCantidad,1,0,'R');

                $txtImpo = '$ ' . number_format($subFamilia['importe'],2, ',', '.');
                $this->Cell(40,6,$txtImpo,1,1,'R');
            }else{

                $familiaId = (int)$subFamilia['id'];
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(120,6,'Total Familia -->',1,0,'R');

                $totaCantiTxt = number_format($totalCantidad,2, ',', '.');
                $this->Cell(30,6, $totaCantiTxt,1,0,'R');

                $totalImporteTxt = number_format($totalImporte,2, ',', '.');
                $this->Cell(40,6, $totalImporteTxt,1,1,'R');

                $totalCantidad = 0;
                $totalImporte = 0;

                if ($this->GetY() > 260 ){
                    $this->AddPage();
                    $this->SetY(50);
                }
                $this->setFillColor(230,230,230);
                $this->Cell(190, 6, StringUtil::utf8($subFamilia['familia']), 1, 1,'L', 1);

                $this->setFillColor(255,255,255);
                $this->Cell(60, 6, '', 1, 0,'L');
                $this->SetFont('Arial', '', 10);
                $this->Cell(60, 6, StringUtil::utf8($subFamilia['nombre']), 1, 0,'L');

                $txtCantidad = number_format($subFamilia['cantidad'],2, ',', '.');
                $this->Cell(30,6,$txtCantidad,1,0,'R');

                $txtImpo = '$ ' . number_format($subFamilia['importe'],2, ',', '.');
                $this->Cell(40,6,$txtImpo,1,1,'R');
            }
            $totalCantidad += (float)$subFamilia['cantidad'];
            $totalImporte += (float)$subFamilia['importe'];

        }

        $this->SetFont('Arial', 'B', 11);
        $this->Cell(120,6,'Total Familia -->',1,0,'R');

        $totaCantiTxt = number_format($totalCantidad,2, ',', '.');
        $this->Cell(30,6, $totaCantiTxt,1,0,'R');

        $totalImporteTxt = number_format($totalImporte,2, ',', '.');
        $this->Cell(40,6, $totalImporteTxt,1,1,'R');

        return $this->Output($this->nombreArchivo, $this->Salida);
    }

    /**
     * función pié del reporte PDF
     */
    function Footer()
    {
        // Posición: a 1,5 cm del final
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Número de página
        $this->Cell(0,10,StringUtil::utf8('Página: ') . $this->PageNo() . '/{nb}',0,0,'C');
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
    public function setDatosCabecera(array $datosCabecera): void
    {
        $this->datosCabecera = $datosCabecera;
    }

    /**
     * @param array $datosMediosPagos
     * @return ResumenDeVentaPdf
     */
    public function setDatosMediosPagos(array $datosMediosPagos): ResumenDeVentaPdf
    {
        $this->datosMediosPagos = $datosMediosPagos;
        return $this;
    }

    /**
     * @param array $totalesPorSubFlia
     * @return ResumenDeVentaPdf
     */
    public function setTotalesPorSubFlia(array $totalesPorSubFlia): ResumenDeVentaPdf
    {
        $this->totalesPorSubFlia = $totalesPorSubFlia;
        return $this;
    }
}
