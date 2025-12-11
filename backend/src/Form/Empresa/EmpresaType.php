<?php

namespace App\Form\Empresa;

use App\Form\AbstractTypes;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints as Assert;


class EmpresaType extends AbstractTypes
{

    /**
     * Restricciones de activación de empresa del administrador
     * @return Collection
     */
    private function constraintsActivaAdmin(): Assert\Collection
    {
        return new Assert\Collection(array(
            'activo' => [new Assert\NotBlank(), new Assert\Choice([0,1,"0","1"])],
        ));
    }

    /**
     * Validaciones para los campos de empresa - esto afecta a las tablas usuario/empresa/localidad
     */
    private function constraintsFecha(): Assert\Collection
    {
        return new Assert\Collection(array(
            'fechaCertificado'  => [new Assert\NotBlank(), new Assert\Date()],
            'empresaId' => [new Assert\NotBlank(), new Assert\Range(['min' => 1])]
        ));
    }


    /**
     * Validaciones para los campos de empresa - esto afecta a las tablas usuario/empresa/localidad
     */
    private function constraintsEmpresa(): Assert\Collection
    {
        return new Assert\Collection(array(
            'id'             => [new Assert\NotBlank(), new Assert\Range(array('min' => 0))],
            'nombre'             => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(array('min' => 5))],
            'nombre_fantasia'    => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(array('min' => 3))],
            'email'              => new Assert\Email(),
            'clave'              => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(array('min' => 6))],
            'claveConfirm'       => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(array('min' => 6))],
            'cuit'               => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(array('min' => 11))],
            'direccion'          => [new Assert\NotBlank(), new Assert\Length(array('min' => 5))],
            'localidad'          => new Assert\Length(array('min' => 4)),
            'codigo_postal'      => new Assert\Length(array('min' => 4)),
            'provincia_afip'     => new Assert\Range(array('min' => 0)),
            'provincia_nombre'   => new Assert\Optional(),
            'telefono'           => new Assert\Length(array('min' => 6)),
            'pago'               => new Assert\Optional(),
            'observaciones_afip' => new Assert\Optional(),
            'categoria_iva_id'   => [new Assert\NotBlank(), new Assert\Length(array('min' => 1))],
            'concepto_id'        => [new Assert\NotBlank(), new Assert\Length(array('min' => 1))],
            'iibb'               => new Assert\Optional(),
            'fecha_inicio'       => new Assert\Optional()
        ));
    }


    /**
     * Validaciones para los campos de empresa - esto afecta a las tablas usuario/empresa/localidad  - UPDATE
     */
    private function constraintsEmpresaUpdate($id): Collection
    {

        return new Assert\Collection(array(
            'id'                 => new Assert\Range(['min' => $id, 'max' => $id]),
            'nombre'             => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(array('min' => 5))],
            'nombre_fantasia'    => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(array('min' => 3))],
            'email'              => new Assert\Email(),
            'clave'              => new Assert\Optional(new Assert\Length(array('min' => 6))),
            'claveConfirm'       => new Assert\Optional(new Assert\Length(array('min' => 6))),
            'cuit'               => [new Assert\NotBlank(['normalizer'=>'trim']), new Assert\Length(array('min' => 11))],
            'direccion'          => [new Assert\NotBlank(), new Assert\Length(array('min' => 5))],
            'localidad'          => new Assert\Length(array('min' => 4)),
            'codigo_postal'      => new Assert\Length(array('min' => 4)),
            'provincia_afip'     => new Assert\Range(array('min' => 0)),
            'provincia_nombre'   => new Assert\Optional(),
            'telefono'           => new Assert\Length(array('min' => 6)),
            'pago'               => new Assert\Optional(),
            'localidad_id'       => new Assert\Optional(),
            'observaciones_afip' => new Assert\Optional(),
            'categoria_iva_id'   => [new Assert\NotBlank(), new Assert\Length(array('min' => 1))],
            'concepto_id'        => [new Assert\NotBlank(), new Assert\Length(array('min' => 1))],
            'iibb'               => new Assert\Optional(),
            'fecha_inicio'       => new Assert\Optional()
        ));
    }



    /**
     * Controlo datos para el cambio de estado
     * @param array $postValues
     * @return void
     */
    public function controloActivacionEmpresaAdministrador(array $postValues): void
    {
        $constCompr = $this->constraintsActivaAdmin();
        $errors = $this->validation->validate($postValues, $constCompr);
        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }
    }


    /**
     * @param array $postValues
     * @return void
     */
    public function controloRegistroFecha(array $postValues): void
    {
        $constCompr = $this->constraintsFecha();
        $errors = $this->validation->validate($postValues, $constCompr);
        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }
    }

    /**
     * Métod que controla el registro enviado
     */
    public function controloRegistro(array $postValues, int $id = 0): void
    {
        $constCompr = $this->constraintsEmpresa();
        if ($id > 0)
            $constCompr = $this->constraintsEmpresaUpdate($id);
        $errors = $this->validation->validate($postValues, $constCompr);
        if (0 !== count($errors)) {
            $mensaje = $this->traduccionError($errors[0]);
            throw new HttpException(400, $mensaje);
        }

        //Si es create o cambió la clave
        if ($id === 0 || isset($postValues['clave']))
            if ($postValues['clave'] !== $postValues['claveConfirm'])
                throw new HttpException(400, 'Las claves NO coinciden');
    }

}