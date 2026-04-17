<?php

namespace App\Form\Administrador\Configuracion;
use App\Form\AbstractTypes;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;

class ConfiguracionType extends AbstractTypes
{
    /**
     * @return Assert\Collection
     */
    private function constraints(): Assert\Collection
    {
        return new Assert\Collection([
            // 1 = imprime ticket, 0 = no imprime ticket
            'imprime_ticket'  => [new Assert\NotBlank(), new Assert\Choice([1, '1', 0, '0'])],

            // 1 = ticket en 58mm, 2 = ticket en 80mm
            'formato_ticket'  => [new Assert\Optional(new Assert\Choice([null, -0, '0',1, '1', 2, '2']))],
        ]);
    }

    /**
     * @param array $postValues
     * @return void
     */
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