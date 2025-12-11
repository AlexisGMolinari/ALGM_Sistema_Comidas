<?php

declare(strict_types=1);

namespace App\Controller\Administrador\Dashboard;

use App\Repository\Dashboard\DashboardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route("api/dashboard", name: "api_dashboard_")]
class DashboardController extends AbstractController
{
    #[Route("/resumen", name: "api_dashboard_resumen", methods: ["GET"])]
    public function getDashboard(DashboardRepository $repository): JsonResponse
    {
        $empresa_id = $this->getUser()->getEmpresa();

        $registro = $repository->datosInicio($empresa_id);
        if (empty($registro)) {
            return $this->json([
                'status' => 'error',
                'message' => 'No hay registros'
            ], 404);
        }

        return $this->json($registro);
    }
}
