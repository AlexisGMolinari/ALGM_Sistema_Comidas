<?php

namespace App\Form\Empresa\Perfil;

use App\Form\AbstractTypes;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;

class CajeroType extends AbstractTypes
{

    /**
     * @param int $id
     * @return Assert\Collection
     */
    private function constraints(int $id): Assert\Collection
    {
        return new Assert\Collection([
            'id'             => new Assert\Range(['min' => $id, 'max' => $id]),
            'codigo'         => [new Assert\NotBlank(), new Assert\Range(['min' => 1, 'max' => 9999])],
            'nombre'         => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(['min' => 5])],
            'activo'             => new Assert\Choice([0,1,"0","1"])
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

    /**
     * @return Assert\Collection
     */
    private function constraintsLogueoPorCodigo(): Assert\Collection
    {
        return new Assert\Collection([
            'codigo' => [new Assert\NotBlank(), new Assert\Range(['min' => 1, 'max' => 9999])],
        ]);
    }

    /**
     * @param array $postValues
     * @return void
     */
    public function controloLogueoPorCodigo(array $postValues): void
    {
        $constCompr = $this->constraintsLogueoPorCodigo();
        $errors = $this->validation->validate($postValues, $constCompr);
        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }
    }
}