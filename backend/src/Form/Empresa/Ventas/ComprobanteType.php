<?php

namespace App\Form\Empresa\Ventas;

use App\Form\AbstractTypes;
use App\Repository\Contador\ContadorPuntoDeVentaRepository;
use App\Repository\Empresa\Clientes\ClienteRepository;
use App\Repository\Empresa\Perfil\CajerosRepository;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;


class ComprobanteType extends AbstractTypes
{

	const PRECIO_MENSAJE = 'El precio del producto debe ser CERO o mayor a cero';

	/**
	 * Restricciones de la cabecera del comprobante
	 * @return Assert\Collection
	 */
	private function constrainsCabecera(): Assert\Collection
	{
		return new Assert\Collection(array(
			'id'                    => new Assert\Optional(),
			'fecha'                 => [new Assert\NotBlank(),new Assert\Date()],
			'condicion_venta_id'    => [new Assert\NotBlank(), new Assert\Range(array('min' => 1))],
			'punto_venta_id'        => [new Assert\Range(array('min' => 1)), new Assert\NotBlank()],
			'tipo_comprobante_id'   => [new Assert\Range(array('min' => 1)),new Assert\NotBlank()],
			'entrega'               => new Assert\Optional(),
			'presupuesto_id'        => new Assert\Optional(),
			'tipo_comprobante_asociado' => new Assert\Optional(),
			'punto_vta_comprobante_asociado' => new Assert\Optional(),
			'numero_comprobante_asociado' => new Assert\Optional(),
			'cajero_id' => new Assert\Optional()
		));
	}

	/**
	 * Constrains de los movimientos
	 * @return Assert\Collection
	 */
	private function constrainsMovimientos(): Assert\Collection
	{
		return new Assert\Collection([
			'id'                => new Assert\Range(array('min' => 1)),
			'cantidad'          => [new Assert\Range(['min' => 0.01]), new Assert\NotBlank(['message' => 'Campo cantidad en blanco'])],
			'codigo'            => new Assert\Optional(),
			'nombre'            => new Assert\Length(['min' => 2]),
			'tasaIva'           => new Assert\Optional(),
			'total'             => [new Assert\Range(['min' => 0, 'minMessage' => self::PRECIO_MENSAJE]), new Assert\NotBlank(['message' => self::PRECIO_MENSAJE])],
			'precio'            => [new Assert\Range(['min' => 0, 'minMessage' => self::PRECIO_MENSAJE]), new Assert\NotBlank(['message' => self::PRECIO_MENSAJE])],
			'precioOriginal'    => [new Assert\Range(['min' => 0, 'minMessage' => self::PRECIO_MENSAJE]), new Assert\NotBlank(['message' => self::PRECIO_MENSAJE])],
            'porcRecBonif'      => [new Assert\Range(['min' => -99.99, 'max' => 9999])],
			'precioBonif'       => new Assert\NotBlank(),
			'fecha_modif'       => new Assert\Optional(),
			'costo'             => new Assert\Optional(),
			'utilidad'          => new Assert\Optional(),
		]);
	}

	public function controloPuntoVenta(int $idPuntoVenta): void
	{
		(new ContadorPuntoDeVentaRepository($this->connection, $this->security))->checkIdExiste($idPuntoVenta);
	}

	/**
	 * Controlo to do el comprobante completo
	 * @param array $postValues
	 * @return void
	 * @throws Exception
	 */
	public function controloRegistro(array $postValues): void
	{
		if (!(isset($postValues['comprobante'], $postValues['productos'], $postValues['cliente'])))
			throw new HttpException(400, 'Parámetros Erróneos');

		$cabecera = $postValues['comprobante'];
		$movimientos = $postValues['productos'];
		$cliente = $postValues['cliente'];

		$constCabecera = $this->constrainsCabecera();
		$errors = $this->validation->validate($cabecera, $constCabecera);
		if (0 !== count($errors)) {
			$mensaje = $this->traduccionError($errors[0]);
			throw new HttpException(400, $mensaje);
		}

		//controlo movimientos
		if (count($movimientos) === 0) {
			throw new HttpException(400, 'Debe seleccionar al menos un producto');
		}
		$constMovimientos = $this->constrainsMovimientos();
		foreach ($movimientos as $movimiento) {
			$errors = $this->validation->validate($movimiento, $constMovimientos);
			if (0 !== count($errors)) {
				$mensaje = $this->traduccionError($errors[0]);
				throw new HttpException(400, $mensaje);
			}
		}

		$this->controlFK('Punto Venta', $cabecera['punto_venta_id'], true,
			(new ContadorPuntoDeVentaRepository($this->connection, $this->security)));

		$this->controlFK('Cliente', $cliente['id'], false,
			(new ClienteRepository($this->connection, $this->security)));

        $this->controlFK('Cajero', $cabecera['cajero_id'], false,
            (new CajerosRepository($this->connection, $this->security)));
	}
}
