<?php

namespace App\Form\Empresa\Clientes;

use App\Form\AbstractTypes;
use App\Repository\Empresa\Clientes\ClienteRepository;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;

class ClienteType extends AbstractTypes
{
	/**
	 * @param int $id
	 * @return Assert\Collection
	 */
    private function constraints(int $id): Assert\Collection
    {
        return new Assert\Collection([
            'id'             => new Assert\Range(['min' => $id, 'max' => $id]),
            'localidad_id'       => new Assert\Optional(),
            'nombre'         => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(['min' => 5])],
            'email'              => new Assert\Email(),
            'tipo_documento'     => new Assert\Optional(),
            'numero_documento'   => new Assert\Range(['min' => 100000, 'max' => 99999999999]),
            'codigo'             => new Assert\Optional(),
            'domicilio'          => new Assert\Optional(),
            'localidad'          => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(['min' => 4])],
            'codigo_postal'      => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(['min' => 4])],
            'provincia_afip'     => new Assert\Range(['min' => 0]),
            'provincia_nombre'   => new Assert\Optional(),
            'telefono'           => new Assert\Optional(),
            'categoria_iva_id'   => new Assert\Range(['min' => 1]),
            'observaciones'      => new Assert\Optional(),
            'saldo'              => new Assert\Optional(),
            'activo'             => new Assert\Choice([0,1,"0","1"]),
			'condicion_venta_id' => [new Assert\NotBlank(), new Assert\Range(['min' => 1])]
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

        //Validar CUIT/Documento
        $clienteRepository = new ClienteRepository($this->connection, $this->security);
        $existeRegistro = $clienteRepository->getByNroCuit($postValues['numero_documento']);
        if ($existeRegistro !== false && $postValues['id'] !== $existeRegistro['id'] && $postValues['numero_documento'] !== 11111111)
            throw new HttpException(400, 'El CUIT/Documento ya existe');

    }


    /**
     * Validaciones para los campos para la generación del resumen de cuenta de cliente
     */
    private function constraintsResumenCta(): Assert\Collection
    {
        return new Assert\Collection([
            'idCliente'         => new Assert\Range(['min' => 1]),
            'nombreCliente'     => new Assert\Optional(),
            'periodoResumen'    => new Assert\Range(['min' => 1, 'max' => 3600]),
            'saldoCliente'      => new Assert\Optional(),
        ]);
    }

    public function controloRegistroResumenCta(array $postValues): void
    {
        $constCompr = $this->constraintsResumenCta();
        $errors = $this->validation->validate($postValues, $constCompr);
        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }
    }


}
