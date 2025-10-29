<?php

namespace App\Utils;

/**
 * Clase con funciones útiles a to do el proyecto
 */
class Utils
{
    /**
     * Método que verifica si es un CUIT lo formatea y lo devuelve; sino devuelve el DNI
     * @param $documento string documento del cliente
     * @return string con el cuit formateado o el DNI
     */
    public static function getCuitDniFormat(string $documento): string
    {
		$dato = number_format($documento,0,'','.');
        if (strlen($documento) > 8){
            $dato = substr($documento,0,2) . '-' .substr($documento,2,8) . '-' .substr($documento,10,1);
        }
        return $dato;
    }
}
