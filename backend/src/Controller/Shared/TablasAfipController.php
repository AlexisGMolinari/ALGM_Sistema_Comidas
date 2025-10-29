<?php

namespace App\Controller\Shared;

use App\Repository\Contador\ContadorEmpresaRepository;
use App\Repository\Shared\TablasAFIPRepository;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tablas',  name: 'app_tablas_')]
class TablasAfipController extends AbstractController
{
	/**
	 * @param TablasAFIPRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route('/tasas-iva', name: 'get_tasas_iva', methods: ['GET'])]
	public function getTasas(TablasAFIPRepository $repository): JsonResponse
	{
		$tasas = $repository->getAllTasasIva();
		return $this->json($tasas);
	}


	/**
	 * @param TablasAFIPRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route('/categorias-iva', name: 'get_categorias_iva', methods: ['GET'])]
	public function getCategoriasIda(TablasAFIPRepository $repository): JsonResponse
	{
		$categorias = $repository->getAllCategoriasIVA();
		return $this->json($categorias);
	}

	/**
	 * @param TablasAFIPRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route('/unidades-medidas', name: 'get_unidades_medidas', methods: ['GET'])]
	public function getUnidadesMedida(TablasAFIPRepository $repository): JsonResponse
	{
		$unidades = $repository->getAllUnidadesMedidas();
		return $this->json($unidades);
	}

	/**
	 * @param TablasAFIPRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route('/conceptos-incuidos', name: 'get_conceptos_incuidos', methods: ['GET'])]
	public function getConceptosIncluidos(TablasAFIPRepository $repository): JsonResponse
	{
		$unidades = $repository->getAllConceptosIncluidos();
		return $this->json($unidades);
	}

	/**
	 * Devuelve los tipos de comprobantes de acuerdo a la empresa logueada y la categoría de IVA del cliente
	 * @param int $cateIvaCliente
	 * @param ContadorEmpresaRepository $empresaRepository
	 * @param TablasAFIPRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route('/tipo_comprobantes/{cateIvaCliente}', name: 'get_tipo_comprobantes_por_cate_cliente', methods: ['GET'])]
	public function getTipoComprobantePorCatCliente(int                       $cateIvaCliente,
                                                    ContadorEmpresaRepository $empresaRepository,
                                                    TablasAFIPRepository      $repository): JsonResponse
	{
		$empresaActual = $empresaRepository->getById($this->getUser()->getEmpresa());
		$tipoComprobantes = $repository->getTipoComprobantes($empresaActual['categoria_iva_id'], $cateIvaCliente);
		return $this->json($tipoComprobantes);
	}

	/**
	 * @param TablasAFIPRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route('/provincias', name: 'get_provincias', methods: ['GET'])]
	public function getProvincias(TablasAFIPRepository $repository): JsonResponse
	{
		$provincias = $repository->getProvincias();
		return $this->json($provincias);
	}

	/**
	 * @param TablasAFIPRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route('/condiciones-ventas', name: 'get_condiciones_ventas', methods: ['GET'])]
	public function getCondicionesVenta(TablasAFIPRepository $repository): JsonResponse
	{
		$condiciones = $repository->getCondicionesVentas();
		return $this->json($condiciones);
	}

}
