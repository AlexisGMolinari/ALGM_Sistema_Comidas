<?php


namespace App\Reportes\Empresa\Comprobantes;


use App\Reportes\UtilesPdf;
use DateTime;
use Fpdf\Fpdf;

class ComprobanteNOEPdf extends Fpdf
{

    protected array $comprobante = [];

    protected array $empresa = [];

    protected array $movimIVA = [];

    protected bool $discrimino = false;

    protected int $entrega  = 0;

    /**
     * variable para setear si el PDF sale por navegador o se adjunta a un email
     */
    private string $Salida = 'I';

    /**
     * si se le setea un nombre genera el PDF con ese nombre (fact Lote)
     */
    private string $nombrePDf = '';

    function Header()
    {
        $factura = $this->comprobante['cabecera'];

        //imprimo PRESUPUESTO
        $this->SetXY(5, 5);
        $this->SetFont('Arial', 'B', 18);
        $this->MultiCell(200, 7, 'PRESUPUESTO', 'B', 'C');

        // datos del cliente
        $this->SetXY(5, 13);
        $this->SetFont('Arial', '', 10);
        $cliente = "Cliente: " . utf8_decode($factura['nombre']);
        $this->MultiCell(100, 4, $cliente, 0, 'L');

        // domicilio
        $this->SetXY(5, 18);
        $this->SetFont('Arial', '', 9);
        $domicilio = "Domicilio: " . utf8_decode($factura['domicilio']);
        $this->MultiCell(100, 5, $domicilio, 0, 'L');

        // ciudad
        $this->SetXY(5, 22);
        $this->SetFont('Arial', '', 9);
        $ciudad = 'Ciudad.: ' . $factura['codigo_postal'] . ' - ' . $factura['localidad'] . ' - ' . $factura['provincia'];
        $this->MultiCell(100, 5, utf8_decode($ciudad), 0, 'L');



        // fecha
        $this->SetXY(105, 12);
        $fechaEmision = 'Fecha: ' . DateTime::createFromFormat('Y-m-d H:i:s', $factura['fecha'])->format('d/m/Y');
        $this->MultiCell(100, 7, utf8_decode($fechaEmision), 0, 'R');
        // Numero comprobante
        $this->SetXY(105, 17);
        $nroComp = str_pad((int)$factura['punto_venta'], 4, "0", STR_PAD_LEFT) . ' - '
            . str_pad((int)$factura['numero'], 8, "0", STR_PAD_LEFT);
        $this->MultiCell(100, 10, utf8_decode($nroComp), 0, 'R');


        //cabecera
        $this->Rect(5, 28, 200, 8);
        $colX = 5;
        $this->SetXY($colX, 27);
        $this->SetFont('Arial', 'B', 8);
        $anchoCol = 25;
        $this->MultiCell($anchoCol, 10, utf8_decode('Código'), 0, 'C');
        $colX += $anchoCol;
        $this->SetXY($colX, 27);

        $anchoCol = 55;
        $this->MultiCell($anchoCol, 10, utf8_decode('Producto / Servicio'), 0, 'C');
        $colX += $anchoCol;
        $this->SetXY($colX, 27);

        $anchoCol = 15;
        $this->MultiCell($anchoCol, 10, utf8_decode('Cant.'), 0, 'R');
        $colX += $anchoCol;
        $this->SetXY($colX, 27);

        $anchoCol = 20;
        $this->MultiCell($anchoCol, 10, utf8_decode('U.medida'), 0, 'R');
        $colX += $anchoCol;
        $this->SetXY($colX, 27);

        $anchoCol = 25;
        $this->MultiCell($anchoCol, 10, utf8_decode('Precio Unit'), 0, 'R');
        $colX += $anchoCol;
        $this->SetXY($colX, 27);

        $anchoCol = 15;
        $this->SetFont('Arial', 'B', 7);
        $this->MultiCell($anchoCol, 10, utf8_decode('% Bon/Rec'), 0, 'C');
        $colX += $anchoCol;
        $this->SetXY($colX, 27);
        $this->SetFont('Arial', 'B', 8);

        $anchoCol = 20;
        $this->MultiCell($anchoCol, 10, utf8_decode('$ Bonif/Rec'), 0, 'R');
        $colX += $anchoCol;
        $this->SetXY($colX, 27);

        $anchoCol = 25;
        $this->MultiCell($anchoCol, 10, utf8_decode('Subtotal'), 0, 'R');
    }

    /**
     * función que genera los movmientos y exporta el objeto PDF
     * @return string
     */
    public function GenerarPdf()
    {
        $factura = $this->comprobante['cabecera'];
        $this->AddPage();
        $this->SetTitle(utf8_decode('Factura Simple - ' . $this->empresa['usuario']['nombre'] . ' - Impresión de Presupuesto'));
        $this->SetFont('Arial', '', 9);
        $cont = 0;
        $renglonesPag = 22;
        $filaY = 38;
        foreach ($this->comprobante['movimientos'] as $factm) {
            $cont = $cont + 1;
            $codigo = utf8_decode($factm['producto_codigo']);
            $nombre = utf8_decode($factm['producto_nombre']);
            $cantidad = $factm['cantidad'];
            $unidad = utf8_decode($factm['producto_unidad']);
            $bonifRec = floatval(($factm['monto_descuento'] * (-1)) / $cantidad);                             // cambio signo para volver el precio original
            $precioUnitario = number_format($factm['precio_unitario_civa'] + $bonifRec, 2, ',', '.');
            $subtotalSiva = number_format(floatval($factm['precio_unitario_civa'] * $factm['cantidad']), 2, ',', '.');
            $descuentoPorc = number_format($factm['porcentaje_descuento'], 2, ',', '.');
            $montoDescuento = number_format($factm['monto_descuento'], 2, ',', '.');
            $subtotalCiva = $subtotalSiva;

            // Establezco el alto del renglón y los demás valores de acuerdo al nombre del producto
            $arrVal = (new UtilesPdf())->definoAlto(strlen($nombre));
            $colX = 5;
            $alto = $arrVal['altoLinea'];
            $this->SetXY($colX, $filaY);
            $this->SetFont('Arial', '', 9);
            $anchoCol = 25;
            $this->MultiCell($anchoCol, 5, $codigo, 0, 'L');
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

            $this->Line(5, $filaY - 2, 205, $filaY - 2);
            if ($cont > $renglonesPag) {
                $this->AddPage();
                $cont = 0;
                $filaY = 38;
            } else {
                $filaY += ($alto + 2);
            }
        }

        //imprimo totales
        $montoN = 'Total: ' . number_format($factura['total_final'], 2, ',', '.');
        $this->SetXY(5, $filaY + 5);
        $this->SetFont('Arial', 'B', 12);
        $this->MultiCell(200, 10, $montoN, 0, 'R');

        // si tiene una entrega para esa factura muestro le cartel
        if ($this->entrega > 0){
            $this->SetXY(5, $filaY + 15);
            $montoE = 'Su entrega: $ ' . number_format($this->entrega, 2, ',', '.');
            $this->MultiCell(50, 8, $montoE, 0, 'L');
        }
        return $this->Output($this->Salida, $this->nombrePDf);

    }



    /**
     * función pié de la Factura PDF
     */
    function Footer()
    {
    }


    /**
     * @param int $entrega
     */
    public function setEntrega(float $entrega): void
    {
        $this->entrega = $entrega;
    }

    /**
     * @param string $Salida
     */
    public function setSalida(string $Salida): void
    {
        $this->Salida = $Salida;
    }

    /**
     * @param array $movimIVA
     */
    public function setMovimIVA(array $movimIVA): void
    {
        $this->movimIVA = $movimIVA;
    }

    /**
     * @param array $empresa
     */
    public function setEmpresa(array $empresa): void
    {
        $this->empresa = $empresa;
    }

    /**
     * @param array $comprobante
     */
    public function setComprobante(array $comprobante): void
    {
        $this->comprobante = $comprobante;
    }

    /**
     * @param string $nombrePDf
     */
    public function setNombrePDf(string $nombrePDf): void
    {
        $this->nombrePDf = $nombrePDf;
    }
}
