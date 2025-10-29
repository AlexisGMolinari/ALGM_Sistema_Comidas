<?php

namespace App\Form\Administrador\Egresos;

use App\Form\AbstractTypes;
use App\Repository\Administrador\Egreso\Categoria\CategoriaEgresoExpensasRepository;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;

class EgresoType extends AbstractTypes
{
    /**
     * @param int $id
     * @return Assert\Collection
     */
    private function constraints(int $id): Assert\Collection
    {
        return new Assert\Collection([
            'id'             => new Assert\EqualTo($id),
            'monto'          => [new Assert\NotBlank(), new Assert\Range(['min' => 0.0])],
            'categoria_id'   => new Assert\Range(['min' => 1]),
            'descripcion'    => [new Assert\NotBlank()],
        ]);
    }

    /**
     * @throws Exception
     */
    public function controloRegistro(array $postValues, int $id): void
    {
        $constCompr = $this->constraints($id);
        $errors = $this->validation->validate($postValues, $constCompr);

        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }
        $categoriaRepository = new CategoriaEgresoExpensasRepository($this->connection, $this->security);
        $categoria = $categoriaRepository->getById($postValues['categoria_id']);

        if(!$categoria){
            throw new HttpException(400, "No existe la categoría seleccionada");
        }
    }
}