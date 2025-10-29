<?php

declare(strict_types=1);

namespace App\Controller\Administrador\Caja;

use App\Form\Administrador\Caja\AdminCajaType;
use App\Repository\Administrador\Caja\AdminCajaRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route("api/caja", name: "api_caja_")]
class AdminCajaController extends AbstractController
{
    /**
     * @param AdminCajaRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/", name: "get_actual", methods: ["GET"])]
    public function cajaActual(AdminCajaRepository $repository): JsonResponse
    {
        $registro = $repository->getCajaActual();
        if (empty($registro)) {
            return $this->json([
                'status' => 'error',
                'message' => 'No hay caja abierta actualmente'
            ], 404);
        }

        return $this->json($registro);
    }

    /**
     * @param GetRequestValidator $requestValidator
     * @param AdminCajaType $type
     * @param AdminCajaRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/", name:"add", methods:["POST"])]
    public function create(GetRequestValidator $requestValidator,
                           AdminCajaType $type,
                           AdminCajaRepository $repository): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $type->controloApertura($postValues, 0);
        $postValues['abierta_usuario_id'] = $this->getUser()->getId();
        $postValues['abierta'] = 1;
        $repository->createRegistro($postValues);
        return $this->json([], 201);
    }

    /**
     * @param int $id
     * @param GetRequestValidator $requestValidator
     * @param AdminCajaType $type
     * @param AdminCajaRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/{id}", name:"cierre", requirements: ["id" => "\d+"], methods: ["PUT"])]
    public function cierreCaja(int $id,
                           GetRequestValidator $requestValidator,
                           AdminCajaType $type,
                           AdminCajaRepository $repository): JsonResponse
    {
        $repository->checkIdExiste($id);
        $postValues = $requestValidator->getRestBody();
        $type->controloRegistro($postValues, $id);
        $postValues['cerrada_usuario_id'] = $this->getUser()->getId();
        $postValues['abierta'] = 0;
        $postValues['cerrada_fecha'] = date("Y-m-d H:i:s");
        $repository->updateRegistro($postValues, $id);
        return $this->json([], 201);
    }
}
