<?php

namespace App\Form\Empresa\Stock;

use App\Form\AbstractTypes;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;

class FamiliaType extends AbstractTypes
{
	/**
	 * @param int $id
	 * @return Assert\Collection
	 */
	private function constraints(int $id): Assert\Collection
	{
		return new Assert\Collection([
			'id' => new Assert\Range(['min' => $id, 'max' => $id]),
			'nombre' => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(['min' => 3])],
			'activo' => new Assert\Choice([0,1,"0","1"]),
		]);
	}

	/**
	 * @param array $postValues
	 * @param int $id
	 * @return void
	 */
	public function controloRegistro(array $postValues, int $id = 0): void
	{
		$constCompr = $this->constraints($id);
		$errors = $this->validation->validate($postValues, $constCompr);
		if (0 !== count($errors)) {
			$mensaje = $this->traduccionError($errors[0]);
			throw new HttpException(400, $mensaje);
		}
	}
}
