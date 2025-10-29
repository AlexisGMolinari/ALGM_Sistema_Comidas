<?php

namespace App\Reportes\Empresa\Productos;

use App\Utils\StringUtil;
use Fpdf\Fpdf;

class EtiquetasPreciosPdf extends FPDF
{
	protected array $productos = [];

	public function GenerarPdf(): string
	{
		$this->AddPage();
		$this->SetTitle(mb_convert_encoding('Factura $imple - Impresión de Etiquetas de precios de los productos', 'ISO-8859-1', 'UTF-8'));


		if (count($this->productos) === 0) {
			$this->SetFont('Arial', '', 10);
			$this->SetTextColor(255, 0,0);
			$this->Cell(190,10, ' * No se encontraron productos con el precio actualizado para el rango de fechas seleccionado', 0, 0, 'C');
		}else{
			$this->generoEtiquetas();
		}
		return $this->Output('S', 'factura-simple-etiquetas-productos.pdf');
	}


	/**
	 * Imprime las etiquetas izq. y derecha
	 * @return void
	 */
	private function generoEtiquetas (): void
	{
		$x = 10;        //eje x
		$y = 10;       // eje y
		$c = 0;        // contador de etiquetas
		$alto = 28;       //alto etiqueta
		$ancho = 93;       // ancho etiqueta
		$izq = true;     // si es izquierda o derecha
		for ($f = 0; $f < count($this->productos); $f++) {
			$etiqueta = $this->productos[$f];
			$precioProd = '$' . number_format($etiqueta['precio'], 2, ',', '.');
			$nombreProducto = mb_convert_encoding(substr($etiqueta['nombre'], 0, 90), 'ISO-8859-1', 'UTF-8');
			$codigo = 'Código: ' . $etiqueta['codigo'];
			if ($izq) {
				//rectángulo izquierda
				$this->SetXY($x, $y);
				$this->Rect($x, $y, $ancho, $alto);
				$this->SetFont('Arial', '', 10);
				$this->SetXY($x, $y);
				$this->MultiCell(93, 7, mb_convert_encoding($codigo, 'ISO-8859-1', 'UTF-8'), 'B', 'L');

				//nombre del producto
				$this->SetFont('Arial', 'B', 12);
				// $this->Rect($x, $y + 5, $ancho, 7);
				$this->SetXY($x, $y + 7);
				$this->MultiCell(93, 5, $nombreProducto, 0, 'L');


				//Precio PRoducto más grande
				$this->SetFont('Arial', '', 20);
				$x = 10;
				$this->SetXY($x, $y + 18);
				$this->MultiCell(92, 11, $precioProd, 0, 'R');

			} else {
				//rectángulo derecha
				$x = $x + $ancho + 0.5;
				$this->SetXY($x, $y);
				$this->Rect($x, $y, $ancho, $alto);


				$this->SetXY($x, $y);
				$this->SetFont('Arial', '', 10);
				$this->SetXY($x, $y);
				$this->MultiCell(93, 7, StringUtil::utf8($codigo), 'B', 'L');


				//nombre del producto
				$this->SetFont('Arial', 'B', 12);
				$this->SetXY($x, $y + 7);
				$this->MultiCell(93, 5, $nombreProducto, 0, 'L');

				//Precio PRoducto más grande
				$this->SetFont('Arial', '', 20);
				$this->SetXY($x, $y + 18);
				$this->MultiCell(93, 11, $precioProd, 0, 'R');

				$y = $y + $alto + 0.8;      //salto de lineas de etiquetas
			}
			$izq = !$izq;

			//
			if ($c > 16) {
				$y = 10;
				$c = 0;
				//pongo esta linea para que no agregue una hoja si es la última etiqueta
				if ($f < count($this->productos) - 1) {
					$this->AddPage();
				}
			} else {
				$c++;
			}
			$x = 10;
		}
	}

	/**
	 * @param array $productos
	 * @return $this
	 */
	public function setProductos(array $productos): EtiquetasPreciosPdf
	{
		$this->productos = $productos;
		return $this;
	}


}
