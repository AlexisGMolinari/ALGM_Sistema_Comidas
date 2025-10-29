<?php

namespace App\Form\Shared;

use App\Form\AbstractTypes;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;

class GenerarQRType extends AbstractTypes
{
    public function constraints(): Assert\Collection
    {
        return new Assert\Collection([
            'texto' => [ new Assert\NotBlank(),
                        new Assert\Length(['min' => 10]),
                        new Assert\Type('string')],
        ]);
    }

    public function controloRegistro(array $postValues): void
    {
        $constCompr = $this->constraints();
        $errors = $this->validation->validate($postValues, $constCompr);
        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }
    }
}