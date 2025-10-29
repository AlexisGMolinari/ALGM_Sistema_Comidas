<?php

declare(strict_types=1);

namespace App\Controller\Administrador\Reportes;

use App\Repository\Administrador\Pedidos\AdminPedidoRepository;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route("api/admin/reportes", name: "api_admin_pedidos_")]
class ReportePedidosController extends AbstractController
{
    /**
     * @param AdminPedidoRepository $repo
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/semanal', name: 'api_reports_daily', methods: ['GET'])]
    public function reporteSemanal(AdminPedidoRepository $repo): JsonResponse
    {
        $data = $repo->getPedidosUltimaSemana();
        return $this->json($data);
    }

    /**
     * @param Request $request
     * @param AdminPedidoRepository $repo
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/mensual', name: 'api_reports_monthly', methods: ['GET'])]
    public function reporteMensual(Request $request, AdminPedidoRepository $repo): JsonResponse
    {
        $month = $request->query->get('month'); // por ejemplo '10' o '2025-10'
        $data = $repo->getPedidosMensual($month);
        return $this->json($data);
    }
}
