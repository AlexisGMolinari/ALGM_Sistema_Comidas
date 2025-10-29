<?php

namespace App\Reportes\Empresa\Comprobantes;


use App\Reportes\UtilesPdf;
use App\Service\Comprobantes\QRGenerator;
use App\Utils\StringUtil;
use DateTime;
use Fpdf\Fpdf;

class ComprobantePdf extends Fpdf
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

    function Header(): void
	{
        $factura = $this->comprobante['cabecera'];
        $empresa = $this->empresa['empresa'];
        $usuario = $this->empresa['usuario'];

        $this->Rect(5, 5, 200, 55);
        //      linea division fecha vto. comprobante
        $this->Line(5 , 54 , 205 , 54);
        //      rectangulo: letra del comprobante
        $this->Rect(95, 11, 20, 20);
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



        // -------------------------- CENTROOO ------------------------------------------
        $this->SetXY(100,12);
        $this->SetFont('Arial','B',30);
        $this->Cell(10,10,  $factura['letra_factura'],0,0,'R');


        $this->SetXY(95,22);
        $this->SetFont('Arial','B',10);
        $tipoComprobante = $factura['tipo_comprobante_afip'];
        $this->MultiCell(20,8,  StringUtil::utf8('COD. '.sprintf('%02d',$tipoComprobante)),0, 'C');


        // -------------------------- Columna Derecha ------------------------------------------

        $columnaDer = 120;
        $this->SetXY($columnaDer,11);
        $comprobante = $factura['tipoComprobanteNombre'];
        $this->SetFont('Arial','B',18);
        $this->MultiCell(85,10,  StringUtil::utf8($comprobante),0,'L','');

        $this->SetXY($columnaDer,20);
        $this->SetFont('Arial','B',9);
        $this->MultiCell(27,10,  StringUtil::utf8('Punto de Venta:'),0);
        $this->SetXY($columnaDer + 27,20);
        $this->SetFont('Arial','',9);
        $this->MultiCell(10,10,  StringUtil::utf8(str_pad((int) $factura['punto_venta'],4,"0",STR_PAD_LEFT)),0);
        $this->SetXY($columnaDer + 30 + 10,20);
        $this->SetFont('Arial','B',9);
        $this->MultiCell(20,10,  StringUtil::utf8('Comp. Nro:'),0);
        $this->SetFont('Arial','',9);
        $this->SetXY($columnaDer + 30 + 10 + 20,20);
        $this->MultiCell(25,10,  StringUtil::utf8(str_pad((int) $factura['numero'],8,"0",STR_PAD_LEFT)),0);


        $this->SetXY($columnaDer,25);
        $fechaEmision = 'Fecha de Emisión: ' . DateTime::createFromFormat('Y-m-d H:i:s', $factura['fecha'])->format('d/m/Y');
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
        $fechaVencimiento = 'Fecha de Vto. para el pago: ' . \DateTime::createFromFormat('Y-m-d H:i:s', $factura['fecha_vencimiento_factura'])->format('d/m/Y');
        $this->MultiCell(90,7,  StringUtil::utf8($fechaVencimiento),0);


        // -------------------------- CLIENTE -> Columna Izquierda ------------------------------------------

        $this->SetXY($columnaIzq,60);
        //si es consumidor final muestro el DNI
        if (strlen($factura['numero_documento']) < 11){
            $clienteCuit = $factura['numero_documento'];
        }else{
            $clienteCuit = substr($factura['numero_documento'], 0,2) . '-' . substr($factura['numero_documento'], 2,8) . '-' . substr($factura['numero_documento'], 10,1);

        }
        $this->MultiCell(100,10,  StringUtil::utf8('CUIT/DNI: ' .$clienteCuit),0);

        $this->SetXY($columnaIzq,65);
        $clienteCategoriaIva = 'Condición frente al IVA: ' . $factura['categoriaIVACliente'];
        $this->MultiCell(100,10,  StringUtil::utf8($clienteCategoriaIva),0);

        $this->SetXY($columnaIzq,70);
        $clienteCondicion = 'Condición de venta: ' . $factura['condicionVenta'];
        $this->MultiCell(100,10,  StringUtil::utf8($clienteCondicion),0);



        // -------------------------- CLIENTE -> Columna Derecha  ------------------------------------------
        $this->SetXY($columnaDer-15,63);
        $clienteRazon = 'Razón Social: ' . $factura['nombre'];
        $this->MultiCell(100,4,  StringUtil::utf8($clienteRazon),0);

        $this->SetXY($columnaDer-15,70);
        $domicilio = 'Domicilio: ' . $factura['domicilio'];
        $this->MultiCell(100,10,  StringUtil::utf8($domicilio),0);

        $this->SetXY($columnaDer-15,75);
        $ciudad = 'Ciudad.: ' . $factura['codigo_postal'] . ' - ' . $factura['localidad'] . ' - ' . $factura['provincia'];
        $this->MultiCell(100,10,   StringUtil::utf8($ciudad),0);

        if ($this->discrimino) {
            //      datos: titulos del detalle de producto toda la info para resp inscriptos
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
     * Función que genera los movimientos y exporta el objeto PDF
     * @return string
     */
    public function GenerarPdf(): string
	{
        $this->AddPage();
        $this->SetTitle(StringUtil::utf8('Factura Simple - ' . $this->empresa['usuario']['nombre'] . ' - Impresión de Comprobantes'));
        $this->SetFont('Arial','',9);
        $cont           = 0;
        $renglonesPag   = 22;
        $filaY          = 93;

        foreach ($this->comprobante['movimientos'] as $factm) {
            $cont           = $cont + 1;
            $codigo         = StringUtil::utf8($factm['producto_codigo']);
            $nombre         = StringUtil::utf8($factm['producto_nombre']);
            $cantidad       = $factm['cantidad'];
            $unidad         = StringUtil::utf8($factm['producto_unidad']);

            // Establezco el alto del renglón y los demás valores de acuerdo al nombre del producto
            $arrVal = (new UtilesPdf())->definoAlto(strlen($nombre));
            $alto   = $arrVal['altoLinea'];

            if ($this->discrimino) {        // si discrimino imprimo de una forma
                $subtotalSiva = number_format($factm['monto_neto'] ,2, ',', '.');
                $subtotalCiva = floatval($factm['monto_iva'] + $factm['monto_neto'] + $factm['monto_no_gravado']);
                $subtotalCiva = number_format($subtotalCiva ,2, ',', '.');
                $montoDescuento = number_format($factm['monto_descuento'],2, ',', '.');
                $descuentoPorc = number_format($factm['porcentaje_descuento'],2, ',', '.');
                $alicuota  = number_format($factm['porcentaje_iva'],2);

                // calculo el IVA de la bonif o del recargo y se lo sumo al precio unitario sin IVA
                $tasaIvaBonif   = floatval($alicuota/100) + 1;
                $impoIvaBonif   = floatval((abs($factm['monto_descuento']) * $tasaIvaBonif) / $cantidad); //abs por si está ne negativo
                $precioUnitario = number_format(($factm['precio_unitario_siva'] + $impoIvaBonif),2, ',', '.');

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

                $anchoCol = 19;
                $this->MultiCell($anchoCol, $alto, $montoDescuento, 0, 'R');
                $colX += $anchoCol;
                $this->SetXY($colX, $filaY);

                $anchoCol = 12;
                $this->MultiCell($anchoCol, $alto, $alicuota, 0, 'R');
                $colX += $anchoCol;
                $this->SetXY($colX, $filaY);

                $anchoCol = 23;
                $this->MultiCell($anchoCol, $alto, $subtotalCiva, 0, 'R');

            } else {
                $bonifRec   = floatval(($factm['monto_descuento'] * (-1)) / $cantidad);                             // cambio signo para volver el precio original
                $precioUnitario = number_format(($factm['precio_unitario_civa'] + $bonifRec ) ,2, ',', '.');
                $subtotalSiva   = number_format(floatval($factm['precio_unitario_civa'] * $factm['cantidad']) ,2, ',', '.');
                $descuentoPorc  = number_format($factm['porcentaje_descuento'],2, ',', '.');
                $montoDescuento = number_format($factm['monto_descuento'],2, ',', '.');
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
        return $this->Output($this->Salida, $this->nombrePDf);
    }

    /**
     * Función pié de la Factura PDF
     */
    function Footer(): void
	{
        $factura = $this->comprobante['cabecera'];
        $this->SetY(-62);
        $y = $this->GetY();

        if ($this->entrega > 0){
            $this->SetXY(5, $y - 7);
            $montoE = 'Su entrega: $ ' . number_format($this->entrega, 2, ',', '.');
            $this->SetFont('Arial', 'B', 12);
            $this->MultiCell(50, 8, $montoE, 0, 'L');
        }

        //      rectángulo: totales del comprobante
        $this->Rect(5, 235, 200, 45);
        $this->SetFont('Arial', 'B', 9);
        if ($this->discrimino) {
            $montoN = number_format($factura['total_neto'], 2, ',', '.');
            $texto  = 'Importe Neto Gravado:';
        }else{
            $montoN = number_format($factura['total_final'], 2, ',', '.');
            $texto  = 'Subtotal: ';
        }
        // muestro neto gravado
        $this->SetXY(130, $y);
        $this->MultiCell(50, 8, $texto, 0, 'R');
        $this->SetXY(180,$y);
        $this->SetFont('Arial','',9);
        $this->MultiCell(25,8,  StringUtil::utf8('$ '.$montoN),0,'R');

        // muestro el NO gravado
        $totalNOGravado = (float)$factura['total_no_gravado'];
        if ($totalNOGravado > 0){
            $y += 4;
            $totalNOGravado = number_format($totalNOGravado, 2, ',', '.');
            $this->SetXY(130, $y);
            $this->SetFont('Arial', 'B', 9);
            $this->MultiCell(50, 8, 'Importe Neto NO Gravado', 0, 'R');
            $this->SetXY(180,$y);
            $this->SetFont('Arial','',9);
            $this->MultiCell(25,8,  StringUtil::utf8('$ '.$totalNOGravado),0,'R');
        }

        $this->Ln(1);

        $x=130;
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
                $y= $y + 5;
            }
        }

        $y = 270;
        $this->SetFont('Arial','B',8);
        // -------- Tabla otros tributos --------
        $this->SetXY($x-80 ,$y-33);
        $this->Cell(25,4,'Otros Tributos',0,2,'L');
        $this->Cell(1);
        $this->SetFillColor(210);
        $this->SetFont('Arial','',8);
        $this->Cell(35,4,StringUtil::utf8('Descripción'),1,0,'L',true);
        $this->Cell(25,4,'Detalle',1,0,'L',true);
        $this->Cell(11,4,StringUtil::utf8('Alíc. %'),1,0,'C',true);
        $this->Cell(17,4,'Importe',1,1,'R',true);
        $total = 0;

        if ($factura['impuesto_interno']>0) {
            $this->Cell(40);
            $this->Cell(35,4,StringUtil::utf8('Impuestos Internos'),0,0,'L');
            $this->Cell(25,4,'',0,0,'L');
            $this->Cell(11,4,StringUtil::utf8(''),0,0,'C');
            $this->Cell(19,4,number_format(floatval($factura['impuesto_interno']),2, ',', '.'),0,1,'R');
            $total +=  $factura['impuesto_interno'];
        }

        // leyenda REGIMEN DE TRANSPARENCIA FISCAL, solo para RI, cuando la fc es B
        if ((int)$this->empresa['empresa']['categoria_iva_id'] === 1 && !$this->discrimino) {
            $this->SetX(50);
            $this->SetFont('Arial','',7);
            $this->Cell(88,5,'REGIMEN DE TRANSPARENCIA FISCAL AL CONSUMIDOR (LEY 27743)','B',1,'C');
            $this->SetX(50);
            $ivaContenido = number_format(floatval($factura['total_iva']),2, ',', '.');
            $this->Cell(88,5,'IVA CONTENIDO: $ '. $ivaContenido,0,1,'R');

            $this->SetX(50);
            $this->Cell(88,5,'OTROS IMPUESTOS NACIONALES INDIRECTOS: $ 0,00.-',0,0,'R');
        }

        if($total>0){
            $this->SetFont('Arial','',9);
            $this->Cell(42);
            $this->Cell(88,5,StringUtil::utf8('Total: $ ') . number_format(floatval($total),2, ',', '.'),0,1,'R');
        }

        $this->SetFont('Arial','B',16);
        $this->SetXY($x-20 ,$y);
        $totalFinal = 'Total: $ ' . number_format($this->comprobante['cabecera']['total_final'],2, ',', '.');
        $this->MultiCell(95,10, $totalFinal ,0,'R');

        $nroCAE     = $this->comprobante['cabecera']['cae'];
        $fechaCAE   = \DateTime::createFromFormat('Y-m-d H:i:s', $this->comprobante['cabecera']['fecha_vencimiento_cae']);

        // Genero del código de barra QR
        $arrQr = [
            'empresa' => $this->empresa,
            'comprobante' => $this->comprobante['cabecera'],
        ];

        $urlQrAfip = QRGenerator::armoCodigoQRFacturaAFIP($arrQr);
        $pathQRImagen = QRGenerator::GenerarQR($urlQrAfip);
        $this->Image($pathQRImagen,7,237,42,42, 'jpg' );

        // impresion del CAE
        $y += 11;
        $x -= 20;

        $this->SetFont('Arial','B',10);
        $this->SetXY($x ,$y);
        $this->MultiCell(50,6,  StringUtil::utf8('CAE N°: '),0,'R');

        $this->SetXY($x + 50 ,$y);
        $this->SetFont('Arial','',10);
        $this->MultiCell(45,6,  StringUtil::utf8($nroCAE),0,'L');

        $y += 5;
        $this->SetXY($x ,$y);
        $this->SetFont('Arial','B',10);
        $this->MultiCell(50,6,  StringUtil::utf8('Fecha de Vto. de CAE: '),0,'R');
        $this->SetFont('Arial','',10);
        if ($fechaCAE){
            $this->SetXY($x + 50 ,$y);
            $this->MultiCell(45,6,  $fechaCAE->format('d/m/Y'),0,'L');
        }

    }

    /**
     * @param float $entrega
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
     * @param bool $discrimino
     */
    public function setDiscrimino(bool $discrimino): void
    {
        $this->discrimino = $discrimino;
    }

    /**
     * @param string $nombrePDf
     */
    public function setNombrePDf(string $nombrePDf): void
    {
        $this->nombrePDf = $nombrePDf;
    }
}
