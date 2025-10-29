<?php

namespace App\Service\Perfil;

use Symfony\Component\HttpKernel\Exception\HttpException;

class GetCuotasEmpresas
{
	const URL_ADMIN_FS = 'https://adminfs.cuotasimple.ar/api/';

	/**
	 * Trae todas las cuotas del cliente F$ desde el sistema Admin F$
	 * @param int $clienteFS
	 * @return array
	 */
	public function getCuotasClienteFS(int $clienteFS): array
	{
		$urlEndPoint = self::URL_ADMIN_FS . $clienteFS . '/cuotas';

		$ch = curl_init( $urlEndPoint);

		// THIS LINE DISABLES CERTIFICATE VERIFICATION!!!
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		curl_setopt($ch, CURLOPT_HEADER, false);

		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS, []);
		$result = curl_exec($ch);

		if (curl_errno($ch)) {
			$error_msg = curl_error($ch);
			throw new HttpException(400, $error_msg);
		}

		curl_close($ch);
		// verifico si es un json válido
		$arrCuotas = json_decode($result, true);
		if (json_last_error() === JSON_ERROR_NONE) {
			if (isset($arrCuotas['error'])) {
				throw new HttpException(400, $arrCuotas['error']);
			}else{
				$devo = $arrCuotas;
			}
		}else{
			$devo = []; // hubo un error
		}
		return $devo;
	}
}
