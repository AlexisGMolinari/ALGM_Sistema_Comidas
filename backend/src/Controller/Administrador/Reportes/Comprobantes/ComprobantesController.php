<?php

declare(strict_types=1);

namespace App\Controller\Administrador\Reportes\Comprobantes;

use App\Repository\Administrador\Comprobantes\ComprobantesRepository;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route("api/reportes/comprobantes", name: "api_reportes_comprobantes_")]
class ComprobantesController extends AbstractController
{
    /**
     * @param Request $request
     * @param ComprobantesRepository $comprobantesRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/", name: "index", methods: ["GET"])]
    public function index(Request $request,
                          ComprobantesRepository   $comprobantesRepository): JsonResponse
    {
        $registros = $comprobantesRepository->getAllPaginados($request);
        return $this->json($registros);
    }

    /**
     * @param string $desde
     * @param string $hasta
     * @param ComprobantesRepository $comprobantesRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/{desde}/{hasta}", name: "por_fechas", methods: ["GET"])]
    public function comprobantesDesdeFechas(string $desde,
                                            string $hasta,
                                            ComprobantesRepository   $comprobantesRepository): JsonResponse
    {
        $registros = $comprobantesRepository->getComprobantesEntreFechas($desde, $hasta);
        if (isset($registros['errores'])) {
            return $this->json($registros, 400);
        }
        return $this->json($registros);
    }
}
