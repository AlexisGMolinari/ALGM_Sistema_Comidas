<?php

namespace App\Form\Administrador\Caja;

use App\Form\AbstractTypes;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;

class AdminCajaType extends AbstractTypes
{
    /**
     * @param int $id
     * @return Assert\Collection
     */
    private function constraintsCaja(int $id): Assert\Collection
    {
        return new Assert\Collection([
            'id'             => new Assert\Range(['min' => $id, 'max' => $id]),
            'monto_final'  => [new Assert\Optional(), new Assert\Range(['min' => 0.0])],
            'total_ventas'  => [new Assert\Optional(), new Assert\Range(['min' => 0.0])],
            'total_gastos'  => [new Assert\Optional(), new Assert\Range(['min' => 0.0])],
            'observaciones'    => [new Assert\Optional()],
        ]);
    }

    /**
     * @param int $id
     * @return Assert\Collection
     */
    private function constraints(int $id): Assert\Collection
    {
        return new Assert\Collection([
            'id'             => new Assert\Range(['min' => $id, 'max' => $id]),
            'monto_inicial'  => [new Assert\NotBlank(), new Assert\Range(['min' => 0.0])],
        ]);
    }

    /**
     * @param array $postValues
     * @param int $id
     * @return void
     */
    public function controloApertura(array $postValues, int $id = 0): void
    {
        $constCompr = $this->constraints($id);
        $errors = $this->validation->validate($postValues, $constCompr);

        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }
    }
    /**
     * @param array $postValues
     * @param int $id
     * @return void
     */
    public function controloRegistro(array $postValues, int $id = 0): void
    {
        $constCompr = $this->constraintsCaja($id);
        $errors = $this->validation->validate($postValues, $constCompr);

        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }

    }

}