<?php

namespace App\Form\Empresa\Informes;

use App\Form\AbstractTypes;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;

class LibroIvaVentaType extends AbstractTypes
{
    public function constraints(int $id): Assert\Collection
    {
        return new Assert\Collection([
            'fechaDesde'         => new Assert\Date(),
            'fechaHasta'         => new Assert\Date(),
            'puntoVenta'         => new Assert\NotBlank(),
            'formato'            => new Assert\Choice(['xls','pdf']),
            'empresaId'          => new Assert\Optional(),
        ]);
    }

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
