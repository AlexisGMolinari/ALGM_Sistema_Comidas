<?php


namespace App\Reportes\Empresa\Clientes;


use App\Utils\Helpers\FechaHelper;
use App\Utils\StringUtil;
use Fpdf\Fpdf;
class ComprobantesImpagosPdf extends Fpdf
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
    private string $nombreArchivo = 'comprobantes_impagos.pdf';

    /**
     *variable para los datos de la cabecera
     */
    protected array $datosCabecera = [];

    /**
     * datos del cliente
     */
    protected array $datosCliente = [];

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
            $empresaNombre = StringUtil::utf8($this->datosCabecera['nombreEmpresa']);
            $this->MultiCell(100,$columnaIzq,  $empresaNombre,0,'L');
        }


        /*$this->SetXY($columnaIzq, 14);
        $this->SetFont('Arial', 'B', 18);
        $empresaNombre = StringUtil::utf8($this->datosCabecera['nombre_fantasia']);
        $this->MultiCell(100, $columnaIzq, $empresaNombre, 0, 'L');*/

        $columnaDer =  100;
        $this->SetXY($columnaDer,14);
        $this->SetFont('Arial','B',18);
        $this->MultiCell(100,10,  'Comprobantes impagos',0,'R');


        $this->SetXY($columnaDer, 24);
        $this->SetFont('Arial', 'B', 12);
        $this->MultiCell(100, 8, StringUtil::utf8('Cliente: '. $this->datosCliente['nombre']), 0, 'R');


        $this->SetXY($columnaIzq, 34);
        $this->SetFont('Arial','B', 12);
        $this->Cell(190, 7, 'Fecha: ' . FechaHelper::fechaActual() , 0, 0, 'R');

        $this->SetFont('Arial','b', 10);
        $this->SetFillColor(230,230,230);
        $this->SetXY($columnaIzq, 48);
        $this->Cell(25, 7, 'Fecha', 1, 0, 'C',true);
        $this->Cell(40, 7, 'Comprobante ', 1, 0, 'C',true);
        $this->Cell(30, 7, StringUtil::utf8('Número'), 1, 0, 'C',true);
        $this->Cell(25, 7, 'Monto $', 1, 0, 'C',true);
        $this->Cell(25, 7, 'Pagado $', 1, 0, 'C',true);
        $this->Cell(25, 7, 'Fecha cobro', 1, 0, 'C',true);
        $this->Cell(25, 7, 'Saldo $', 1, 1, 'C',true);

    }

    /**
     * Función que genera los movimientos y exporta el objeto PDF
     */
    public function GenerarPdf(): string
    {
        $this->AliasNbPages();
        $this->AddPage('P', 'A4');
        $this->SetAutoPageBreak(false);
        $this->SetTitle(StringUtil::utf8('Factura $imple - Comprobantes impagos: ' . $this->datosCliente['nombre']));
        // 3 columnas
        $this->SetFont('Arial', '', 10);


        $linea = 1;
        foreach ($this->movimientos as $movimiento) {
            $saldo = $movimiento['total_final'] - $movimiento['pagado'];
            if ($saldo > 0) {
                $this->Cell(25, 7, $movimiento['fecha'], 0, 0, 'C');
                $this->Cell(40,  7,StringUtil::utf8($movimiento['tipoComprobanteNombre']), 0, 0, 'L');
                $this->Cell(30, 7, $movimiento['numero'], 0, 0, 'C');
                $this->Cell(25, 7, number_format($movimiento['total_final'],2, ',', '.') , 0, 0, 'R');
                $this->Cell(25, 7, number_format($movimiento['pagado'],2, ',', '.'), 0, 0, 'R');
                $this->Cell(25, 7, $movimiento['fechaPago'], 0, 0, 'C');
                $this->Cell(25, 7, number_format($saldo,2, ',', '.'), 0, 1, 'R');

                $this->saldoTotal   += $saldo;

                if ($linea > 30) {
                    $this->AddPage();
                    $linea = 1;
                }else{
                    $linea++;
                }

            }

        }
        $this->Cell(170, 7, 'Total $', 0,0, 'R');
        $this->Cell(25, 7, number_format($this->saldoTotal,2, ',', '.'), 0, 1, 'R');
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

    public function setDatosCliente(array $datosCliente): void
    {
        $this->datosCliente = $datosCliente;
    }
}
