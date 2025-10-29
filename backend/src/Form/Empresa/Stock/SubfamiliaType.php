<?php

namespace App\Form\Empresa\Stock;

use App\Form\AbstractTypes;
use App\Repository\Empresa\Stock\FamiliaRepository;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;

class SubfamiliaType extends AbstractTypes
{
    public function constraints(int $id): Assert\Collection
    {
        return new Assert\Collection([
            'id'             => new Assert\Range(['min' => $id, 'max' => $id]),
            'familia_id'     => [new Assert\NotBlank(), new Assert\Range(['min' => 1])],
            'nombre'         => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(['min' => 3])],
            'activo'         => new Assert\Choice([0,1,"0","1"]),
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
        $this->controlFK('familia', $postValues['familia_id'], true, (new FamiliaRepository($this->connection, $this->security)));
    }
}
