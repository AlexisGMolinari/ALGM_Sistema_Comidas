<?php

namespace App\Form\Empresa;

use App\Form\AbstractTypes;
use App\Repository\Shared\LocalidadRepository;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints as Assert;


class EmpresaType extends AbstractTypes
{

    /**
     * Restricciones de activación de empresa del administrador
     * @return Collection
     */
    private function constraintsActivaAdmin(): Assert\Collection
    {
        return new Assert\Collection(array(
            'activo' => [new Assert\NotBlank(), new Assert\Choice([0,1,"0","1"])],
        ));
    }

    /**
     * Validaciones para los campos de empresa - esto afecta a las tablas usuario/empresa/localidad
     */
    private function constraintsEmpresa(int $id): Assert\Collection
    {
        return new Assert\Collection(array(
            'id'             => [new Assert\NotBlank(), new Assert\EqualTo($id)],
            'nombre'             => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(array('min' => 5))],
            'cuit'               => [new Assert\Optional()],
            'direccion'          => [new Assert\Optional()],
            'telefono'           => [new Assert\Optional()],
            'email'               => new Assert\Optional(),
            'cbu_alias' => new Assert\Optional(),
            'url_sitioweb'               => new Assert\Optional(),
            'activa'       => [new Assert\NotBlank(), new Assert\Choice([0,1,"0","1"])],
            'localidad_id'          => [new Assert\NotBlank(), new Assert\Length(array('min' => 1))],
        ));
    }

    /**
     * @param array $postValues
     * @param int $id
     * @return void
     * @throws Exception
     */
    public function controloRegistro(array $postValues, int $id): void
    {
        $constCompr = $this->constraintsEmpresa($id);
        $errors = $this->validation->validate($postValues, $constCompr);
        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }
        $this->controlFK('localidad', $postValues['localidad_id'], true,
            (new LocalidadRepository($this->connection, $this->security)));
    }


    /**
     * Controlo datos para el cambio de estado
     * @param array $postValues
     * @return void
     */
    public function controloActivacionEmpresaAdministrador(array $postValues): void
    {
        $constCompr = $this->constraintsActivaAdmin();
        $errors = $this->validation->validate($postValues, $constCompr);
        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }
    }

}