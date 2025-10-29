<?php

namespace App\Form\Empresa\Presupuestos;

use App\Form\AbstractTypes;
use App\Repository\Empresa\Clientes\ClienteRepository;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;

class PresupuestoType extends AbstractTypes
{

	/**
	 * Constrains de la cabecera
	 * @param int $id
	 * @return Assert\Collection
	 */
	private function constraints(int $id): Assert\Collection
	{
		$arrConst = [
			'id' => new Assert\Range(['min' => $id, 'max' => $id]),
			'fecha' => new Assert\Date(),
			'fecha_estado' => new Assert\DateTime(),
			'cliente_id' => [new Assert\NotBlank(), new Assert\Range(array('min' => 0))],
			'cotizacion' => new Assert\Range(array('min' => 1)),
			'moneda' => new Assert\Optional(),
			'observaciones' => new Assert\Optional()
		];

		if ($id > 0) {
			$arrConst['codigo'] = new Assert\Length(array('min' => 10));
            $arrConst['total_exento'] = new Assert\Range(array('min' => 0));
            $arrConst['total_neto'] = new Assert\Range(array('min' => 1));
            $arrConst['total_iva'] = new Assert\Range(array('min' => 0));
            $arrConst['total_final'] = new Assert\Range(array('min' => 1));
            $arrConst['estado'] = new Assert\Range(array('min' => 10, 'max' => 99));
		}else{
			$arrConst['codigo'] = new Assert\Optional();
			$arrConst['total_exento'] = new Assert\Optional();
			$arrConst['total_neto'] = new Assert\Optional();
			$arrConst['total_iva'] = new Assert\Optional();
			$arrConst['total_final'] = new Assert\Optional();
			$arrConst['estado'] = new Assert\Optional();
		}
		return new Assert\Collection($arrConst);
	}

	/**
	 * Constrains de los movimientos
	 * @return Assert\Collection
	 */
	private function constrainsMovimientos(): Assert\Collection
	{
		return new Assert\Collection(array(
			'id'                => new Assert\Range(array('min' => 1)),
			'cantidad'          => [new Assert\Range(['min' => 0.01]), new Assert\NotBlank(['message' => 'Campo cantidad en blanco'])],
			'codigo'            => new Assert\Optional(),
			'nombre'            => new Assert\Length(['min' => 2]),
			'tasaIva'           => new Assert\Optional(),
			'total'             => new Assert\Range(['min' => 0]),
			'precio'            => new Assert\Range(['min' => 0]),
			'precioOriginal'    => new Assert\Range(['min' => 0]),
			'porcRecBonif'      => new Assert\Range(['min' => 0]),
			'precioBonif'       => new Assert\NotBlank(),
			'fecha_modif'       => new Assert\Optional(),
			'costo'             => new Assert\Optional(),
			'utilidad'          => new Assert\Optional(),
		));
	}

    /**
     * Controla la cliente, cabecera y movimientos del presupuesto
     * @param array $postValues
     * @param int $id
     * @return void
     * @throws Exception
     */
	public function controloRegistro(array $postValues, int $id = 0): void
	{
		if (!(isset($postValues['presupuesto'], $postValues['productos'], $postValues['cliente'])))
			throw new HttpException(400, 'Parámetros Erróneos');

		$presupuesto = $postValues['presupuesto'];
		$movimientos = $postValues['productos'];
        $cliente = $postValues['cliente'];

		$constPresupuesto = $this->constraints($id);
		$errors = $this->validation->validate($presupuesto, $constPresupuesto);
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

        $this->controlFK('Cliente', $cliente['id'], false,
            (new ClienteRepository($this->connection, $this->security)));
	}
}
