<?php

namespace App\Form\Empresa\Informes;

use App\Form\AbstractTypes;
use App\Repository\Empresa\Perfil\CajerosRepository;
use App\Utils\Helpers\FechaHelper;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;

class ResumenVentaType extends AbstractTypes
{
	/**
	 * @return Assert\Collection
	 */
    public function constraints(): Assert\Collection
    {
        return new Assert\Collection([
            'fechaDesde' => new Assert\DateTime(['format' => 'Y-m-d H:i']),
            'fechaHasta' => new Assert\DateTime(['format' => 'Y-m-d H:i']),
            'puntoVenta' => new Assert\NotBlank(),
            'cajero_id' => new Assert\Optional(),
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
		FechaHelper::controlFechaDesdeHasta($postValues['fechaDesde'], $postValues['fechaHasta']);
    }
}
