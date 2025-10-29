<?php

namespace App\Form\Stock\Producto;

use App\Form\AbstractTypes;
use App\Repository\Stock\Categoria\CategoriaRepository;
use App\Repository\Stock\Producto\ProductoRepository;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;


class ProductoType extends AbstractTypes
{
    /**
     * @param int $id
     * @return Assert\Collection
     */
    private function constraints(int $id): Assert\Collection
    {
        return new Assert\Collection([
            'id'             => new Assert\Range(['min' => $id, 'max' => $id]),
            'nombre'         => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(['min' => 3])],
            'precio'              => [new Assert\NotBlank(), new Assert\Range(['min' => 0.0])],
            'categoria_prod_id'   => new Assert\Range(['min' => 1]),
            'stock_actual'      => [new Assert\NotBlank(), new Assert\Range(['min' => 0])],
            'activo'             => new Assert\Choice([0,1,"0","1"]),
        ]);
    }

    /**
     * @param int $id
     * @return Assert\Collection
     */
    private function constraintsStock(int $id): Assert\Collection
    {
        return new Assert\Collection([
            'id'             => new Assert\EqualTo($id),
            'tipo_movimiento' => new Assert\Choice([1,2,"1","2"]),
            'cantidad'      => [new Assert\NotBlank(), new Assert\Range(['min' => 0])],
        ]);
    }

    /**
     * @return Collection
     */
    private function constraintsCombo(): Assert\Collection
    {
        return new Assert\Collection([
            'producto_id'          => [new Assert\NotBlank(), new Assert\Range(['min' => 1])],
            'cantidad'             => [new Assert\NotBlank(), new Assert\Range(['min' => 1])],
        ]);
    }

    /**
     * @param array $postValues
     * @param int $id
     * @return void
     */
    public function controloStock(array $postValues, int $id): void
    {
        $constCompr = $this->constraintsStock($id);
        $errors = $this->validation->validate($postValues, $constCompr);

        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }
    }

    /**
     * @param array $postValues
     * @return void
     * @throws Exception
     */
    public function controloCombo(array $postValues): void
    {
        // Validar que 'componentes' exista y sea un array no vacío
        if (!isset($postValues['componentes']) || !is_array($postValues['componentes']) || count($postValues['componentes']) === 0) {
            throw new HttpException(400, "Se requieren los productos componentes del combo.");
        }

        // Validación de cada componente
        $constraints = $this->constraintsCombo();
        foreach ($postValues['componentes'] as $i => $componente) {
            $errors = $this->validation->validate($componente, $constraints);
            if (count($errors) > 0) {
                $mensaje = $this->traduccionError($errors[0]);
                throw new HttpException(400, "Error en componente #" . ($i + 1) . ": " . $mensaje);
            }

            // Validar existencia del producto referenciado
            $this->controlFK('Producto', $componente['producto_id'], true,
                new ProductoRepository($this->connection, $this->security)
            );
        }
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
        $categoriaRepository = new CategoriaRepository($this->connection, $this->security);
        $categoria = $categoriaRepository->getById($postValues['categoria_prod_id']);

        if(!$categoria){
            throw new HttpException(400, "No existe la categoría seleccionada");
        }
    }
}