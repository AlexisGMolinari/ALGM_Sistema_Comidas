<?php

namespace App\Controller\Empresa;

use App\Repository\Empresa\EmpresaPuntoDeVentaRepository;
use App\Repository\Shared\TablasAFIPRepository;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/api/empresa/varios', name: 'app_empresa_varios_')]
class EmpresaVariosController extends AbstractController
{

    /**
     * Ctrl que trae todos los puntos de ventas de la empresa que está trabajando (no por contador)
     * @param EmpresaPuntoDeVentaRepository $repository
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
	#[Route("/puntos-ventas", name:"get_puntos_ventas", methods:["GET"])]
	#[Route("/puntos-ventas/solo-fe", name:"get_puntos_ventas_fe", methods:["GET"])]
	public function getPuntosVentas(EmpresaPuntoDeVentaRepository $repository,
                                    Request $request): JsonResponse
	{
        $soloFE = false;
        if (str_contains($request->getPathInfo(), 'solo-fe')) {
            $soloFE = true;
        }
		$ptosVtas = $repository->getPuntosDeLaEmpresa($soloFE);
		if (!$ptosVtas) {
			throw new HttpException(400,'Debe generar los puntos de ventas para poder Operar');
		}
		return $this->json($ptosVtas);
	}

	/**
	 * Trae todas las condiciones de ventas
	 * @param TablasAFIPRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/condiciones-ventas", name:"get_condiciones_vta", methods:["GET"])]
	public function getCondicionesVentas(TablasAFIPRepository $repository): JsonResponse
	{
		$condiciones = $repository->getCondicionesVentas();
		return $this->json($condiciones);
	}
}
