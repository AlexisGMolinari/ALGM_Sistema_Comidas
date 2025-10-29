<?php

namespace App\Form\Empresa\Stock;

use App\Form\AbstractTypes;

use App\Repository\Contador\DepositoRepository;
use App\Repository\Empresa\EmpresaRepository;
use App\Repository\Empresa\Stock\ProductoRepository;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;

class MovimientoStockType extends AbstractTypes
{

	/**
	 * @param int $tipoMovimiento
	 * @return Assert\Collection
	 */
	private function constraints(int $tipoMovimiento): Assert\Collection
	{
		$arrConst = [
			'fecha' => new Assert\Date(),
			'empresa_id' => new Assert\Optional(),                   // si viene empresa lo ignoro porque lo coloco en entity fijo
			'producto_id' => new Assert\Range(['min'=>1]),
			'tipo_movimiento' => [new Assert\NotBlank(), new Assert\Choice(['1','2','3'])],
			'deposito_id' => new Assert\Range(['min'=>1]),
			'factura_movimiento_id' => new Assert\Optional(),
			'cantidad' => [new Assert\NotBlank(),new Assert\Range(['min'=>1])]
		];

		if ($tipoMovimiento === 3) {
			$arrConst['deposito_destino_id'] = new Assert\Range(['min'=>1]);
		}else{
			$arrConst['deposito_destino_id'] = new Assert\Optional();
		}
		return new Assert\Collection($arrConst);
	}

    /**
     * @return Assert\Collection
     */
    private function constraintsEmpresa(): Assert\Collection
    {
        return new Assert\Collection([
            'empresa_id' => new Assert\Range(['min' => 1])
        ]);
    }

	/**
	 * @param array $postValues
	 * @return void
	 * @throws Exception
	 */
	public function controloRegistro(array $postValues): void
	{
		$const = $this->constraints($postValues['tipo_movimiento']);
		$errors = $this->validation->validate($postValues, $const);
		if (0 !== count($errors)) {
			$mensaje = $this->traduccionError($errors[0]);
			throw new HttpException(400, $mensaje);
		}
		$this->controlFK('Producto', $postValues['producto_id'], true, (new ProductoRepository($this->connection, $this->security)));
		$this->controlFK('Depósito Origen', $postValues['deposito_id'], true, (new DepositoRepository($this->connection, $this->security)));
		if (((int)$postValues['tipo_movimiento']) === 3) {
			$this->controlFK('Depósito Destino', (int)$postValues['deposito_destino_id'], true, (new DepositoRepository($this->connection, $this->security)));
			if ((int)$postValues['deposito_id'] === (int)$postValues['deposito_destino_id']) {
				throw new HttpException(400, 'El depósito de origen es el mismo que el depósito de destino');
			}
		}
	}

    /**
     * @param array $postValues
     * @return void
     * @throws Exception
     */
    public function controloEmpresa(array $postValues): void
    {
        $constCompr = $this->constraintsEmpresa();
        $errors = $this->validation->validate($postValues, $constCompr);
        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }
        $this->controlFK('Empresa', $postValues['empresa_id'], true, (new EmpresaRepository($this->connection, $this->security)));
    }
}
