<?php

namespace App\Utils\Helpers;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FechaHelper
{

	/**
	 * Controla las fechas de tipo DATE
	 * @param string $fechaDesde
	 * @param string $fechaHasta
	 * @return void
	 */
	public static function controlFechaDesdeHasta(string $fechaDesde, string $fechaHasta):void {
		$fechaDesde = \DateTime::createFromFormat('Y-m-d', $fechaDesde);
		$fechaHasta = \DateTime::createFromFormat('Y-m-d', $fechaHasta);
		if ($fechaDesde > $fechaHasta){
			throw new HttpException(400, 'La fecha Desde debe ser igual o Mayor a la Fecha Hasta');
		}
	}

	/**
	 * Controla fechas y horas
	 * @param string $fechaDesde
	 * @param string $fechaHasta
	 * @return void
	 */
	public static function controlFechaHoraDesdeHasta(string $fechaDesde, string $fechaHasta):void {
		$fechaDesde = \DateTime::createFromFormat('Y-m-d H:i:s', $fechaDesde);
		$fechaHasta = \DateTime::createFromFormat('Y-m-d H:i:s', $fechaHasta);
		if ($fechaDesde > $fechaHasta){
			throw new HttpException(400, 'La fecha Desde debe ser igual o Mayor a la Fecha Hasta');
		}
	}

	/**
	 * Castea a Fecha Argentina una fecha
	 * @param string $fecha
	 * @return string
	 */
	public static function casteoFecha(string $fecha): string {
		$fecha = \DateTime::createFromFormat('Y-m-d', $fecha);
		if (!$fecha){
			throw new HttpException(400, 'Error en el campo Fecha');
		}
		return $fecha->format('d/m/Y');
	}

    public static function fechaActual(): string{
        return (new \DateTime("now"))->format('d/m/Y');
    }
}
