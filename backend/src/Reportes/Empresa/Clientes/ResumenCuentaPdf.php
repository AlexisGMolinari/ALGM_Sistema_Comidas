<?php


namespace App\Reportes\Empresa\Clientes;


use App\Utils\StringUtil;
use Fpdf\Fpdf;
class ResumenCuentaPdf extends Fpdf
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
    private string $nombreArchivo = 'resumen_cuenta.pdf';

    /**
     *variable para los datos de la cabecera
     */
    protected array $datosCabecera = [];

    /**
     * nombre del cliente, periódo de meses a emitir
     */
    protected array $datosResumen = [];

    /**
     * es el saldo total de toda la cuenta
     */
    protected float $saldoTotal = 0.0;




    /**
     * función que arma la cabecera del iva venta
     */
    function Header()
    {
        $columnaIzq = 10;

        // ---------- analizo si tiene logo lo muestro sino muestro en grande el nombre de la empresa
        if ($this->datosCabecera['logo']) {
            $logo = 'logos/' . $this->datosCabecera['logo'];
            $this->Image($logo, $columnaIzq, 7, 60);
        }else{
            $this->SetXY($columnaIzq,14);
            $this->SetFont('Arial','B',18);
            $empresaNombre = StringUtil::utf8($this->datosCabecera['nombre']);
            $this->MultiCell(100,$columnaIzq,  $empresaNombre,0,'L');
        }


        /*$this->SetXY($columnaIzq, 14);
        $this->SetFont('Arial', 'B', 18);
        $empresaNombre = StringUtil::utf8($this->datosCabecera['nombre_fantasia']);
        $this->MultiCell(100, $columnaIzq, $empresaNombre, 0, 'L');*/

        $columnaDer =  100;
        $this->SetXY($columnaDer,14);
        $this->SetFont('Arial','B',18);
        $this->MultiCell(100,10,  'Resumen de Cuenta',0,'R');


        $this->SetXY($columnaDer, 24);
        $this->SetFont('Arial', 'B', 12);
        $this->MultiCell(100, 8, StringUtil::utf8('Cliente: '. $this->datosResumen['nombreCliente']), 0, 'R');

        $this->SetXY($columnaDer, 30);
        $this->SetFont('Arial','I', 12);
        $periTxt = 'Período Emitido: ' . $this->armoPeriodo($this->datosResumen['periodoResumen']);
        $this->MultiCell(100, 7, StringUtil::utf8($periTxt) , 0, 'R');

        // saldo
        $this->SetXY($columnaIzq, 40);
        $this->SetFont('Arial','B', 12);
        $this->Cell(155, 7, 'Saldo: ', 0, 0, 'R');

        $this->SetXY($columnaIzq+155, 40);
        $this->SetFont('Arial','B', 12);
        $saldoTotal =  '$ ' . number_format($this->saldoTotal,2, ',', '.');
        $this->Cell(35, 7, $saldoTotal, 0, 0, 'R');

        //$this->Line($columnaIzq, 40, 200, 40);
        $this->SetFont('Arial','b', 10);
        $this->SetFillColor(230,230,230);
        $this->SetXY($columnaIzq, 48);
        $this->Cell(30, 7, 'Fecha', 1, 0, 'C',true);
        $this->Cell(50, 7, 'Comprobante ', 1, 0, 'C',true);
        $this->Cell(40, 7, 'Nro. Comprobante', 1, 0, 'C',true);
        $this->Cell(35, 7, 'Importe', 1, 0, 'C',true);
        $this->Cell(35, 7, 'Saldo', 1, 1, 'C',true);

    }

    /**
     * función que genera los movmientos y exporta el objeto PDF
     */
    public function GenerarPdf(): string
    {
        $this->AliasNbPages();
        $this->AddPage('P', 'A4');
        $this->SetAutoPageBreak(false);
        $this->SetTitle(StringUtil::utf8('Factura $imple - Resumen de Cuenta: ' . $this->datosResumen['nombreCliente']));
        // 3 columnas
        $this->SetFont('Arial', '', 10);

        //arranco con el saldo total y le voy descontando las operaciones
        $saldoParcial = $this->saldoTotal;

        $linea = 1;
        // para ir formando el saldo tengo que sumar recibos (y mostrarlos en negativos) y restar facturas (mostrarlas en possitivo)
        foreach ($this->movimientos as $movimiento) {
            $importeMostrar     = $movimiento['importe'];
            $importeDescontar   = $movimiento['importe'] * (-1);
            if ((int)$movimiento['concepto'] === 1){
                $importeMostrar     = $movimiento['importe'] * (-1);
                $importeDescontar   = $movimiento['importe'];
            }

            $impoMostrar    = '$ ' . number_format($importeMostrar,2, ',', '.');
            $saldoMostrar   = '$ ' . number_format($saldoParcial,2, ',', '.');
            $this->Cell(30, 7, $movimiento['fecha'], 0, 0, 'C');
            $this->Cell(50,  7,StringUtil::utf8($movimiento['nombre']), 0, 0, 'L');
            $this->Cell(40, 7, $movimiento['numero'], 0, 0, 'R');
            $this->Cell(35, 7, $impoMostrar, 0, 0, 'R');
            $this->Cell(35, 7, $saldoMostrar, 0, 1, 'R');
            $saldoParcial +=  $importeDescontar;

            if ($linea > 30) {
                $this->AddPage();
                $linea = 1;
            }else{
                $linea++;
            }
        }

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
        $this->SetFont('Arial', 'I', 8);
        // Número de página
        $this->Cell(0, 10, StringUtil::utf8('Página: ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }


    /**
     * @param string $Salida
     */
    public function setSalida(string $Salida): void
    {
        $this->Salida = $Salida;
    }

    public function setMovimientos(array $movimientos): void
    {
        $this->movimientos = $movimientos;
    }

    public function setNombreArchivo(string $nombreArchivo): void
    {
        $this->nombreArchivo = $nombreArchivo;
    }

    public function setDatosCabecera(array $datosCabecera): void
    {
        $this->datosCabecera = $datosCabecera;
    }

    public function setDatosResumen(array $datosResumen): void
    {
        $this->datosResumen = $datosResumen;
    }

    public function setSaldoTotal(float $saldoTotal): void
    {
        $this->saldoTotal = $saldoTotal;
    }


    /**
     * función que arma el texto de los meses emitidos
     */
    private function armoPeriodo(int $cantMeses): string
    {
        $texto = '';
        switch ($cantMeses){
            case 1:
                $texto = 'Ultimo Mes';
                break;
            case 2:
                $texto = 'Ultimos 2 meses';
                break;
            case 6:
                $texto = 'Ultimos 6 meses';
                break;
            case 12:
                $texto = 'Ultimo año';
                break;
            case 3600:
                $texto = 'Desde el inicio';
        }
        return $texto;
    }
}
