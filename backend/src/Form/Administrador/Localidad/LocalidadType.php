<?php

namespace App\Form\Administrador\Localidad;

use App\Form\AbstractTypes;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints as Assert;


class LocalidadType extends AbstractTypes
{

    /**
     * Restricciones de activación de empresa del administrador
     * @param int $id
     * @return Collection
     */
    private function constraints(int $id): Assert\Collection
    {
        return new Assert\Collection(array(
            'id' => [new Assert\NotBlank(), new Assert\EqualTo($id)],
            'nombre' => [new Assert\NotBlank(), new Assert\Length(['min' => 3])],
            'activo' => [new Assert\NotBlank(), new Assert\Choice([0,1,"0","1"])],
        ));
    }

    /**
     * @param array $postValues
     * @param int $id
     * @return void
     */
    public function controloRegistro(array $postValues, int $id): void
    {
        $constCompr = $this->constraints($id);
        $errors = $this->validation->validate($postValues, $constCompr);
        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }
    }
}