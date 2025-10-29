<?php

namespace App\Form\Empresa\Stock\Producto\Precios;

use App\Form\AbstractTypes;
use App\Repository\Empresa\Stock\FamiliaRepository;
use App\Repository\Empresa\Stock\SubfamiliaRepository;
use App\Utils\Helpers\FechaHelper;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;


class ProductoPreciosType extends AbstractTypes
{

	/**
	 * @return Assert\Collection
	 */
	private function constraintsActualizarPrecios(): Assert\Collection
	{
		return new Assert\Collection([
			'idFlia'     => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Range(['min' => 1])],
			'idSubFlia'  => new Assert\Optional(new Assert\Range(['min' => 1])),
			'porciento'  => [new Assert\NotBlank(['normalizer'=>'trim']),new Assert\Range(['min' => -99, 'max' => 99])]
		]);
	}

	/**
	 * @throws Exception
	 */
	public function controloActualizacionDePrecios(array $postValues): void
	{
		$constCompr = $this->constraintsActualizarPrecios();
		$errors = $this->validation->validate($postValues, $constCompr);
		if (0 !== count($errors)) {
			$mensaje = $this->traduccionError($errors[0]);
			throw new HttpException(400, $mensaje);
		}
		$this->controlFK('familia', $postValues['idFlia'], true, (new FamiliaRepository($this->connection, $this->security)));
		$this->controlFK('subfamilia', $postValues['idSubFlia'], false, (new SubfamiliaRepository($this->connection, $this->security)));
	}


	/**
	 * @return Assert\Collection
	 */
	private function constraintsImpresionEtiquetas(): Assert\Collection
	{
		return new Assert\Collection([
			'fechaDesde' => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Date()],
			'fechaHasta' => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Date()]
		]);
	}

	/**
	 * @param array $postValues
	 * @return void
	 */
	public function controloImpresionEtiquetas(array $postValues): void
	{
		$constCompr = $this->constraintsImpresionEtiquetas();
		$errors = $this->validation->validate($postValues, $constCompr);
		if (0 !== count($errors)) {
			$mensaje = $this->traduccionError($errors[0]);
			throw new HttpException(400, $mensaje);
		}
		FechaHelper::controlFechaDesdeHasta($postValues['fechaDesde'], $postValues['fechaHasta']);
	}

}
