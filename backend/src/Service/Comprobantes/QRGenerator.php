<?php

namespace App\Service\Comprobantes;

use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Servicio que genera un archivo imagen con un código QR
 */
class QRGenerator
{
	/**
	 * @param array $params
	 * @return string
	 */
	public static function armoCodigoQRFacturaAFIP(array $params): string
	{
		$empresa = $params['empresa'];
		$comprobante = $params['comprobante'];

		$tipoDoc = 80;
		if (intval($comprobante['categoria_iva_id']) === 3) {
			$tipoDoc = 96;
		}

		$arrQR = [
			'ver' => 1,
			'fecha' => substr($comprobante['fecha'], 0, 10),
			'cuit' => (double)$empresa['empresa']['cuit'],
			'ptoVta' => (int)$comprobante['punto_venta'],
			'tipoCmp' => (int)$comprobante['tipo_comprobante_afip'],
			'nroCmp' => (int)$comprobante['numero'],
			'importe' => round((float)$comprobante['total_final'], 2),
			'moneda' => 'PES',
			'ctz' => 1,
			'tipoDocRec' => $tipoDoc,
			'nroDocRec' => (int)$comprobante['numero_documento'],
			'tipoCodAut' => 'E',
			'codAut' => (double)$comprobante['cae']
		];

		$qrJson = json_encode($arrQR);
		return 'https://www.afip.gob.ar/fe/qr/?p=' . base64_encode($qrJson);
	}

	/**
	 * @param string $texto
	 * @param string|null $path con barra al final ej.: bkend-assets/
	 * @param string|null $fileName
	 * @return string
	 */
	public static function GenerarQR(string $texto, string $path = null, string $fileName = null): string
	{
		$renderer = new ImageRenderer(
			new RendererStyle(400),
			new ImagickImageBackEnd('jpg')
		);
		$writer = new Writer($renderer);

		if ($path === null)
			$path = __DIR__ .'../../../../public/tempQR/';

		if ($fileName === null)
			$fileName = uniqid(rand(), false) . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", 'QRImage.jpg');

		$writer->writeFile($texto, $path . $fileName);
		return $path . $fileName;
	}
}
