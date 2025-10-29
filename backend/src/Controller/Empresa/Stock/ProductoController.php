<?php

namespace App\Controller\Empresa\Stock;

use App\Form\Empresa\Stock\ProductoType;
use App\Repository\Empresa\Stock\ProductoRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route("api/productos", name:"api_productos_")]
class ProductoController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route("/", name:"index",  methods:["GET"])]
    public function getRegistros(GetRequestValidator $requestValidator,
                                 ProductoRepository $productoRepository): JsonResponse
    {
        $registros = $productoRepository->getAllPaginados($requestValidator->getRequest());
        return $this->json($registros);
    }


    /**
     * @throws Exception
     */
    #[Route("/autocompletar/{texto}",  name:"index_autocompletar",  methods:["GET"])]
    public function getRegistrosAutocompletar(string $texto,
                                 ProductoRepository $productoRepository): JsonResponse
    {
        $registros = $productoRepository->getAutocompletar($texto);
        return $this->json($registros);
    }

    /**
     * @throws Exception
     */
    #[Route("/productos-bajo-stock-minimo",  name:"bajo_stock_minimo",  methods:["GET"])]
    public function getRegistrosBajoStockMinimo(ProductoRepository $productoRepository): JsonResponse
    {
        $registros = $productoRepository->getProductosBajoStockMinimo();
        return $this->json($registros);
    }

    /**
     * @throws Exception
     */
    #[Route("/{id}", name:"getOne", requirements:["id" => Requirement::POSITIVE_INT], methods:["GET"])]
    public function getRegistroById(int $id, ProductoRepository $productoRepository): JsonResponse
    {
        $registro = $productoRepository->checkIdExiste($id);
        return $this->json($registro);
    }


	/**
	 * @param string $codigoProducto
	 * @param ProductoRepository $productoRepository
	 * @return JsonResponse
	 * @throws Exception
	 */
    #[Route("/{codigoProducto}/codigo", name:"getOneByCodigo", requirements:["codigoProducto" => Requirement::ASCII_SLUG], methods:["GET"])]
    public function GetProductoByCodigo(string $codigoProducto, ProductoRepository $productoRepository): JsonResponse
    {
        $registro = $productoRepository->getByCodigo($codigoProducto);
        return $this->json($registro);
    }


	/**
	 * @param GetRequestValidator $requestValidator
	 * @param ProductoType $productoType
	 * @param ProductoRepository $productoRepository
	 * @return JsonResponse
	 * @throws Exception
	 */
    #[Route("/", name:"add", methods:["POST"])]
    public function createRegistro(GetRequestValidator $requestValidator,
								   ProductoType $productoType,
								   ProductoRepository $productoRepository): JsonResponse {
        $valores = $requestValidator->getRestBody();
        $productoType->controloRegistro($valores);
        $productoRepository->saveProducto($valores);
        return $this->json([], 201);
    }

	/**
	 * @param GetRequestValidator $requestValidator
	 * @param int $id
	 * @param ProductoType $productoType
	 * @param ProductoRepository $productoRepository
	 * @return JsonResponse
	 * @throws Exception
	 */
    #[Route("/{id}", name:"edit", requirements: ["id" => "\d+"], methods: ["PUT"])]
    public function updateRegistro(GetRequestValidator $requestValidator, int $id,
								   ProductoType $productoType,
								   ProductoRepository $productoRepository): JsonResponse {
		$productoRepository->checkIdExiste($id);
        $valores = $requestValidator->getRestBody();
        $productoType->controloRegistro($valores, $id);
        $productoRepository->updateProducto($valores);
        return $this->json([]);
    }

	/**
	 * @param int $id
	 * @param ProductoRepository $productoRepository
	 * @return JsonResponse
	 * @throws Exception
	 */
    #[Route("/{id}", name: "del", requirements: ["id" => "\d+"], methods:["DELETE"])]
    public function deleteRegistro(int $id, ProductoRepository $productoRepository): JsonResponse
    {
        $productoRepository->checkIdExiste($id);
        $productoRepository->deleteRegistro($id);
        return $this->json([]);
    }

}
