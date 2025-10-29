<?php

namespace App\Reportes;

class UtilesPdf
{
	/**
	 * Calcula el alto de la línea, el del campo y el tamaño de fuente de acuerdo al largo del nombre del campo
	 *
	 * @param $largoNombre integer
	 * @return array
	 */
	public function definoAlto(int $largoNombre): array
	{
		$altoLinea  = 7;
		$altoCampo  = 7;
		$font       = 9;
		if ($largoNombre > 32 && $largoNombre <= 65 ) {
			$altoLinea  = 8;
			$altoCampo  = 4;
		}elseif ($largoNombre > 64 && $largoNombre <= 99 ) {
			$altoLinea  = 8;
			$altoCampo  = 4;
		}elseif ($largoNombre >98 && $largoNombre <= 132 ) {
			$altoLinea  = 10;
			$altoCampo  = 3;
			$font       = 8;
		}elseif ($largoNombre >131 && $largoNombre <= 165 ) {
			$altoLinea  = 11;
			$altoCampo  = 2;
			$font       = 7;
		}elseif ($largoNombre >165) {
			$altoLinea  = 13;
			$altoCampo  = 2;
			$font       = 7;
		}
		return [
			'altoLinea' => $altoLinea,
			'altoCampo' => $altoCampo,
			'fuente' => $font
		];
	}
}
