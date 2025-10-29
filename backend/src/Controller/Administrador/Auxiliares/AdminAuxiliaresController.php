<?php

declare(strict_types=1);

namespace App\Controller\Administrador\Auxiliares;

use App\Repository\Administrador\Auxiliares\AdminEstadoPedidoRepository;
use App\Repository\Administrador\Auxiliares\AdminMetodoPagoRepository;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route("api/auxiliares", name: "api_auxiliares_")]
class AdminAuxiliaresController extends AbstractController
{
    /**
     * @param AdminMetodoPagoRepository $metodoPagoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/metodos-pago", name: "metodos_pago", methods: ["GET"])]
    public function getMetodosPago(AdminMetodoPagoRepository $metodoPagoRepository): JsonResponse
    {
        $metodos = $metodoPagoRepository->getAll(false);
        return $this->json($metodos);
    }

    /**
     * @param AdminEstadoPedidoRepository $estadosPedidoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/estados-pedido", name: "estados_pedido", methods: ["GET"])]
    public function getEstadosDelPedido(AdminEstadoPedidoRepository $estadosPedidoRepository): JsonResponse
    {
        $estados = $estadosPedidoRepository->getAll(false);
        return $this->json($estados);
    }
}
