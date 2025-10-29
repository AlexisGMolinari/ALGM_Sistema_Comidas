<?php

declare(strict_types=1);

namespace App\Controller\Empresa\Perfil;

use App\Form\Empresa\Perfil\CajeroType;
use App\Repository\Empresa\Perfil\CajerosRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use function Symfony\Component\Translation\t;

#[Route("api/perfil/cajeros", name: "api_perfil_cajeros_")]
class EmpresaPerfilCajerosController extends AbstractController
{
    /**
     * @param CajerosRepository $repository
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/', name:"get_all", methods:["GET"])]
    public function index(CajerosRepository $repository, Request $request): JsonResponse
    {
        $cajeros = $repository->getAllCajeros($request);
        return $this->json($cajeros);
    }

    /**
     * @param int $id
     * @param CajerosRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/{id}", name: "getOne", requirements: ["id" => "\d+"], methods: ["GET"])]
    public function getRegistroById(int $id, CajerosRepository $repository): JsonResponse
    {
        $cajero = $repository->checkIdExiste($id);
        return $this->json($cajero);
    }

    /**
     * @param GetRequestValidator $requestValidator
     * @param CajeroType $type
     * @param CajerosRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/", name: "add", methods: ["POST"])]
    public function createRegistro(GetRequestValidator $requestValidator,
                                   CajeroType $type,
                                   CajerosRepository $repository): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $type->controloRegistro($postValues);
        $repository->createRegistro($postValues);
        return $this->json([], 201);
    }

    /**
     * @param GetRequestValidator $requestValidator
     * @param CajeroType $type
     * @param CajerosRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/get-by-codigo", name: "get-by-codigo", methods: ["POST"])]
    public function getByCodigo(GetRequestValidator $requestValidator,
                                   CajeroType $type,
                                   CajerosRepository $repository): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $type->controloLogueoPorCodigo($postValues);
        $cajero = $repository->getByCodigo($postValues['codigo'], true);
        return $this->json($cajero, 201);
    }

    /**
     * @param GetRequestValidator $requestValidator
     * @param int $id
     * @param CajeroType $type
     * @param CajerosRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/{id}", name: "edit", requirements: ["id" => "\d+"], methods: ["PUT"])]
    public function updateRegistro(GetRequestValidator $requestValidator,
                                   int $id,
                                   CajeroType $type,
                                   CajerosRepository $repository): JsonResponse
    {
        $valores = $requestValidator->getRestBody();
        $type->controloRegistro($valores, $id);
        $repository->checkIdExiste($id);
        $repository->updateRegistro($valores, $id);
        return $this->json([]);
    }

    /**
     * @param int $id
     * @param CajerosRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/{id}", name: "del", requirements: ["id" => "\d+"], methods: ["DELETE"])]
    public function deleteRegistro(int $id, CajerosRepository $repository): JsonResponse
    {
        $repository->checkIdExiste($id);
        $repository->deleteRegistro($id);
        return $this->json([]);
    }

}
