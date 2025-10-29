<?php

namespace App\Utils;

class StringUtil
{
	/**
	 * @param string|null $texto
	 * @return array|bool|string|null
	 */
    public static function utf8(?string $texto): array|bool|string|null
	{
        if ($texto === null)
            $texto = '';
        return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
    }
}
