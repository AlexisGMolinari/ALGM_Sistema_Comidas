<?php

namespace App\Form\Empresa\Cobranzas;

use App\Form\AbstractTypes;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;


class ReciboType extends AbstractTypes
{
	/**
	 * Constraints del alta de un pago
	 * @return Assert\Collection
	 */
	private function constraints(): Assert\Collection
	{
		return new Assert\Collection(array(
			'idFacturas'     => new Assert\Count(['min' => 1, 'minMessage' => 'Debe indicar al menos una factura']),
			'montoACobrar'  => new Assert\Range(['min' => 0, 'minMessage' => 'El monto del recibo tiene que ser mayor a cero']),
			'idCliente'     => new Assert\Range(['min' => 1]),
			'detallePago'   => new Assert\Optional(),
		));
	}

	/**
	 * @param array $postValues
	 * @return void
	 */
	public function controloAltaPago(array $postValues): void
	{
		$constPago = $this->constraints();
		$errors = $this->validation->validate($postValues, $constPago);
		if (0 !== count($errors)) {
			$mensaje = $this->traduccionError($errors[0]);
			throw new HttpException(400, $mensaje);
		}
	}
}
