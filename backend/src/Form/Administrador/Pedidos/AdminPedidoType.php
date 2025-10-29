<?php

namespace App\Form\Administrador\Pedidos;

use App\Form\AbstractTypes;
use App\Repository\Administrador\Auxiliares\AdminEstadoPedidoRepository;
use App\Repository\Administrador\Auxiliares\AdminMetodoPagoRepository;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;

class AdminPedidoType extends AbstractTypes
{
    /**
     * @param int $id
     * @return Assert\Collection
     */
    private function constraints(int $id): Assert\Collection
    {
        return new Assert\Collection([
            'id'               => new Assert\Range(['min' => $id, 'max' => $id]),
            'nombre_cliente'   => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(['min' => 3])],
            'estado_id'        => new Assert\Range(['min' => 1]),
            'total'            => [new Assert\NotBlank(), new Assert\Range(['min' => 0.0])],
            'total_efectivo'   => [new Assert\Optional(), new Assert\Range(['min' => 0.0])],
            'total_transferencia' => [new Assert\Optional(), new Assert\Range(['min' => 0.0])],
            'metodo_pago_id'   => new Assert\Range(['min' => 1]),
            'comprobante_img'  => [ new Assert\Optional() ],
        ]);
    }

    /**
     * @param int $id
     * @return Assert\Collection
     */
    private function constraintsEdit(int $id): Assert\Collection
    {
        return new Assert\Collection([
            'id'             => new Assert\Range(['min' => $id, 'max' => $id]),
            'nombre_cliente'         => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(['min' => 3])],
            'estado_id'   => new Assert\Range(['min' => 1]),
            'metodo_pago_id'   => new Assert\Range(['min' => 1]),
            'comprobante_img'  =>   [ new Assert\Optional() ],
        ]);
    }

    /**
     * @param int $id
     * @return Assert\Collection
     */
    private function constraintsEstado(int $id): Assert\Collection
    {
        return new Assert\Collection([
            'id'             => new Assert\Range(['min' => $id, 'max' => $id]),
            'estado_id'   => new Assert\Range(['min' => 1]),
        ]);
    }

    /**
     * @param int $id
     * @return Assert\Collection
     */
    private function constraintsImg(int $id): Assert\Collection
    {
        return new Assert\Collection([
            'id'             => new Assert\Range(['min' => $id, 'max' => $id]),
            'comprobante_img' => [
                new Assert\NotNull(),
                new File([
                    'maxSize' => '5M',
                    'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                    'mimeTypesMessage' => 'El archivo debe ser una imagen válida (jpg, png, webp).'
                ])
            ]
        ]);
    }
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param array $postValues
     * @param int $id
     * @return void
     * @throws Exception
     */
    public function controloEstado(array $postValues, int $id): void
    {
        $constCompr = $this->constraintsEstado($id);
        $errors = $this->validation->validate($postValues, $constCompr);

        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }
        $estadoPedidoRepository = new AdminEstadoPedidoRepository($this->connection, $this->security);
        $estado = $estadoPedidoRepository->getById($postValues['estado_id']);
        if(!$estado){
            throw new HttpException(400, "No existe el estado seleccionado");
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
        $metodPagoRepository = new AdminMetodoPagoRepository($this->connection, $this->security);
        $estadoPedidoRepository = new AdminEstadoPedidoRepository($this->connection, $this->security);
        $metodPago = $metodPagoRepository->getById($postValues['metodo_pago_id']);
        $estado = $estadoPedidoRepository->getById($postValues['estado_id']);
        if(!$metodPago){
            throw new HttpException(400, "No existe el método seleccionado");
        }
        if(!$estado){
            throw new HttpException(400, "No existe el estado seleccionado");
        }
    }

    /**
     * @param array $postValues
     * @param int $id
     * @return void
     */
    public function controloImg(array $postValues, int $id): void
    {
        $constCompr = $this->constraintsImg($id);
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
     * @throws Exception
     */
    public function controloRegistroEdit(array $postValues, int $id): void
    {
        $constCompr = $this->constraintsEdit($id);
        $errors = $this->validation->validate($postValues, $constCompr);

        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }
        $metodPagoRepository = new AdminMetodoPagoRepository($this->connection, $this->security);
        $estadoPedidoRepository = new AdminEstadoPedidoRepository($this->connection, $this->security);
        $metodPago = $metodPagoRepository->getById($postValues['metodo_pago_id']);
        $estado = $estadoPedidoRepository->getById($postValues['estado_id']);
        if(!$metodPago){
            throw new HttpException(400, "No existe el método seleccionado");
        }
        if(!$estado){
            throw new HttpException(400, "No existe el estado seleccionado");
        }
    }
}