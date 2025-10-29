<?php

namespace App\Controller\Empresa\Stock;

use App\Form\Empresa\Stock\MovimientoStockType;
use App\Repository\Empresa\Stock\MovimientoStockRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route("api/empresa/inventario/movimiento-stock", name: "api_empresa_inventario_movimiento_stock_")]
class MovimientoStockController extends AbstractController
{

	/**
	 * @param GetRequestValidator $requestValidator
	 * @param MovimientoStockRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/", name: "index", methods: ["GET"])]
	public function getRegistros(GetRequestValidator $requestValidator,
								 MovimientoStockRepository $repository): JsonResponse
	{
		$registros = $repository->getAllPaginados($requestValidator->getRequest());
		return $this->json($registros);
	}

	/**
	 * Crea un movimiento de stock
	 * @param GetRequestValidator $requestValidator
	 * @param MovimientoStockType $type
	 * @param MovimientoStockRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/", name: "create", methods: ["POST"])]
	public function createMovStock(GetRequestValidator $requestValidator,
									 MovimientoStockType $type,
								 	MovimientoStockRepository $repository): JsonResponse
	{
		$postValues = $requestValidator->getRestBody();
		$type->controloRegistro($postValues);
		$repository->crearMovimientosDeStock($postValues);
		return $this->json([]);
	}

	/**
	 * @param GetRequestValidator $requestValidator
	 * @param MovimientoStockRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/stock-por-depositos", name: "stock_depositos", methods: ["GET"])]
	public function getStockPorDepositos(GetRequestValidator $requestValidator,
								 		MovimientoStockRepository $repository): JsonResponse
	{
		$registros = $repository->getAllStockPorDeposito($requestValidator->getRequest());
		return $this->json($registros);
	}

    /**
     * Eliminará todos los movimientos de stock de la empresa y coloca en 0 el stock actual de la misma
     * @param GetRequestValidator $requestValidator
     * @param MovimientoStockRepository $repository
     * @param MovimientoStockType $type
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/blanqueo/", name: "blanqueo_stock", methods: ["POST"])]
    public function blanquearStock(GetRequestValidator $requestValidator,
                                   MovimientoStockRepository $repository,
                                   MovimientoStockType $type): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $type->controloEmpresa($postValues);
        $repository->blanqueoDeStock($postValues);
        return $this->json([]);
    }
}
