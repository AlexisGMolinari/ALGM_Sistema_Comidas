<?php

declare(strict_types=1);

namespace App\Controller\Administrador\Egresos;

use App\Form\Administrador\Egresos\EgresoType;
use App\Repository\Administrador\Egreso\Categoria\CategoriaEgresoExpensasRepository;
use App\Repository\Administrador\Egreso\EgresoRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route("api/egreso", name: "api_egreso_")]
class EgresosController extends AbstractController
{
    /**
     * @param GetRequestValidator $requestValidator
     * @param EgresoRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/", name: "index", methods: ["GET"])]
    public function index(GetRequestValidator $requestValidator,
                          EgresoRepository   $repository): JsonResponse
    {
        $registros = $repository->getAllPaginados($requestValidator->getRequest());
        return $this->json($registros);
    }

    /**
     * @param CategoriaEgresoExpensasRepository $egresoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/categorias-egreso", name: "get_categorias", methods: ["GET"])]
    public function getCategoriasEgreso(CategoriaEgresoExpensasRepository $egresoRepository): JsonResponse
    {
        $registros = $egresoRepository->getAll(true);
        return $this->json($registros);

    }

    /**
     * devuelve los egresos de una categoria
     * @param int $idCategoria
     * @param CategoriaEgresoExpensasRepository $egresoRepository
     * @param EgresoRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/categorias-egreso/{idCategoria}", name: "get_categorias_id", requirements: ["id" => "\d+"], methods: ["GET"])]
    public function getByCategoriaEgreso(int $idCategoria,
                                         CategoriaEgresoExpensasRepository $egresoRepository,
                                         EgresoRepository $repository): JsonResponse
    {
        $egresoRepository->checkIdExiste($idCategoria);
        $registros = $repository->getByCategoria($idCategoria);
        return $this->json($registros);
    }

    /**
     * @param GetRequestValidator $requestValidator
     * @param EgresoType $type
     * @param EgresoRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/", name:"add", methods:["POST"])]
    public function create(GetRequestValidator $requestValidator,
                           EgresoType $type,
                           EgresoRepository $repository): JsonResponse
    {
        $valores = $requestValidator->getRestBody();
        $type->controloRegistro($valores, 0);
        $valores['usuario_id'] = $this->getUser()->getId();
        $repository->createEgreso($valores);
        return $this->json([], 201);
    }

    /**
     * @param int $id
     * @param GetRequestValidator $requestValidator
     * @param EgresoType $type
     * @param EgresoRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/{id}", name: "update", methods:["PUT"])]
    public function update(int $id,
                           GetRequestValidator $requestValidator,
                           EgresoType $type,
                           EgresoRepository $repository): JsonResponse
    {
        $repository->checkIdExiste($id);
        $valores = $requestValidator->getRestBody();
        $type->controloRegistro($valores, $id);
        $repository->updateRegistro($valores, $id);
        return $this->json([], 204);
    }


    /**
     * @param int $id
     * @param EgresoRepository $productoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/{id}", name: "del", requirements: ["id" => "\d+"], methods:["DELETE"])]
    public function deleteRegistro(int $id,
                                   EgresoRepository $productoRepository): JsonResponse
    {
        $productoRepository->checkIdExiste($id);
        $productoRepository->deleteRegistro($id);
        return $this->json([]);
    }
}
