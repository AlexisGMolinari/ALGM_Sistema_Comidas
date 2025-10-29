<?php
declare(strict_types=1);
namespace App\Form\Empresa\Stock;

use App\Form\AbstractTypes;
use App\Repository\Empresa\Stock\FamiliaRepository;
use App\Repository\Empresa\Stock\ProductoRepository;
use App\Repository\Empresa\Stock\SubfamiliaRepository;
use App\Repository\Shared\TablasAFIPRepository;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;

class ProductoType extends AbstractTypes
{
    protected function constraints(int $id): Assert\Collection
    {
        return new Assert\Collection([
            'id'            => new Assert\Range(['min' => $id, 'max' => $id]),
            'codigo'        => new Assert\Optional(),
            'nombre'        => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(['min' => 2])],
            'subfamilia_id' => new Assert\Range(array('min' => 1)),
            'tasa_iva_id'   => new Assert\Range(array('min' => 1)),
            'unidad_id'     => new Assert\Range(array('min' => 1)),
            'precio'        => new Assert\Range(array('min' => 0)),
            'observaciones' => new Assert\Optional(),
            'fecha_modif'   => new Assert\Optional(),
            'costo'         => new Assert\Optional(),
            'utilidad'      => new Assert\Optional(),
            'impuesto_interno' => new Assert\Range(array('min' => 0)),
            'stock_minimo'  =>  new Assert\Range(array('min' => 0)),
            'familia'  		=> new Assert\Optional(),
            'activo'        => new Assert\Choice([0,1,"0","1"]),
        ]);
    }



	/**
     * @throws Exception
     */
    public function controloRegistro(array $postValues, int $id = 0): void
    {
        $constCompr = $this->constraints($id);
        $errors = $this->validation->validate($postValues, $constCompr);
        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }
        $this->controlFK('subfamilia', $postValues['subfamilia_id'], true, (new SubfamiliaRepository($this->connection, $this->security)));
        $tablasAfipRepo = (new TablasAFIPRepository($this->connection));

        $registroExiste = $tablasAfipRepo->getTasaIvaById($postValues['tasa_iva_id']);
        if (!$registroExiste)
            throw new HttpException(404, 'No se encuentra el registro de tasa de iva ingresado.');

        $registroExiste = $tablasAfipRepo->getUnidadMedidaById($postValues['unidad_id']);
        if (!$registroExiste)
            throw new HttpException(404, 'No se encuentra el registro de unidad de medida ingresado.');

		// si ingresó un código compruebo que no exista otro igual
		if ($postValues['codigo']){
			$productoRepository = (new ProductoRepository($this->connection, $this->security));
			$yaExisteCodigo = $productoRepository->getByCodigo($postValues['codigo']);
			if ($yaExisteCodigo && (int)$yaExisteCodigo['id'] !== $id)
				throw new HttpException(404, 'Ya existe el código de producto ingresado.');
		}

    }
}
