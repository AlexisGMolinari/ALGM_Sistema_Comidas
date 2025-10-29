<?php

namespace App\Controller\Empresa\Stock;

use App\Form\Empresa\Stock\Producto\Precios\ProductoPreciosType;
use App\Reportes\Empresa\Productos\EtiquetasPreciosPdf;
use App\Repository\Empresa\Stock\ProductoRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/productos/precios',  name: 'api_productos_precios_')]
class ProductoPreciosController extends AbstractController
{

	/**
	 * @param GetRequestValidator $requestValidator
	 * @param ProductoPreciosType $productoType
	 * @param ProductoRepository $productoRepository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route('/actualizo-precios', name : 'actualizar_precios', methods:["POST"])]
	public function ActualizarPrecios(GetRequestValidator $requestValidator,
									  ProductoPreciosType $productoType,
									  ProductoRepository $productoRepository
	): JsonResponse
	{
		$postValues = $requestValidator->getRestBody();
		$productoType->controloActualizacionDePrecios($postValues);
		$cantidad = $productoRepository->actualizoPrecios($postValues);
		return $this->json(['CantidadProductosActualizados' => $cantidad]);
	}


	/**
	 * @param Request $request
	 * @param ProductoPreciosType $type
	 * @param ProductoRepository $repository
	 * @param EtiquetasPreciosPdf $pdf
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route('/impresion-etiquetas', name : 'impresion_etiquetas', methods:["GET"])]
	public function ImpresionEtiquetas(Request $request,
									   ProductoPreciosType $type,
									   ProductoRepository $repository,
									   EtiquetasPreciosPdf $pdf): Response
	{
		$getValues = $request->query->all();
		$type->controloImpresionEtiquetas($getValues);
		$productos = $repository->getProductosActualizadosPorFecha($getValues['fechaDesde'], $getValues['fechaHasta']);
		$pdf->setProductos($productos);

		return new Response($pdf->GenerarPdf(), 200, ['Content-Type' => 'application/pdf']);
	}
}
