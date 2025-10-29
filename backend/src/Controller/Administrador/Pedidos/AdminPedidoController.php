<?php

declare(strict_types=1);

namespace App\Controller\Administrador\Pedidos;

use App\Form\Administrador\Pedidos\AdminPedidoType;
use App\Repository\Administrador\Auxiliares\AdminDetallePedidoRepository;
use App\Repository\Administrador\Pedidos\AdminPedidoHistorialRepository;
use App\Repository\Administrador\Pedidos\AdminPedidoRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route("api/admin/pedidos", name: "api_admin_pedidos_")]
class AdminPedidoController extends AbstractController
{
    /**
     * @param Request $request
     * @param AdminPedidoRepository $pedidoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/", name: "index", methods: ["GET"])]
    #[Route("/all-pedidos", name: "all_pedidos", methods: ["GET"])]
    public function index(Request $request,
                          AdminPedidoRepository   $pedidoRepository): JsonResponse
    {
        $all_pedidos = false;
        if(str_contains($request->getPathInfo(), "all-pedidos") !== false){
            $all_pedidos = true;
        }
        $registros = $pedidoRepository->getAllPaginados($request, $all_pedidos);
        return $this->json($registros);
    }

    /**
     * @param int $id
     * @param AdminPedidoRepository $pedidoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/{id}", name: "getOne", requirements: ["id" => "\d+"], methods: ["GET"])]
    public function getPedidoById(int $id, AdminPedidoRepository $pedidoRepository): JsonResponse
    {
        $pedidoRepository->checkIdExiste($id);
        $registro = $pedidoRepository->getByIdPedido($id);
        return $this->json($registro);
    }

    /**
     * @param int $pedidoId
     * @param AdminPedidoRepository $pedidoRepository
     * @param AdminDetallePedidoRepository $detalleRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/detalle/{pedidoId}", name: "get_detalle_pedido", requirements: ["pedidoId" => "\d+"], methods: ["GET"])]
    public function getDetallePedido(int $pedidoId,
                                     AdminPedidoRepository $pedidoRepository,
                                     AdminDetallePedidoRepository $detalleRepository): JsonResponse
    {
        $pedidoRepository->checkIdExiste($pedidoId);
        $registro = $detalleRepository->getDetalleByPedidoId($pedidoId);
        return $this->json($registro);
    }

    /**
     * @param int $id
     * @param AdminPedidoHistorialRepository $historialRepository
     * @param AdminPedidoRepository $pedidoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/{id}/historial", name: "get_historial", requirements: ["id" => "\d+"], methods: ["GET"])]
    public function getHistorialByPedidoId(int $id,
                                           AdminPedidoHistorialRepository $historialRepository,
                                           AdminPedidoRepository $pedidoRepository): JsonResponse
    {
        $pedidoRepository->checkIdExiste($id);
        $historial = $historialRepository->getHistorial($id);
        return $this->json($historial);
    }

    /**
     * @param GetRequestValidator $requestValidator
     * @param AdminPedidoType $type
     * @param AdminPedidoRepository $pedidoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/", name:"add", methods:["POST"])]
    public function createPedido(GetRequestValidator $requestValidator,
                                 AdminPedidoType $type,
                                 AdminPedidoRepository $pedidoRepository): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $items = $postValues['items'] ?? [];
        unset($postValues['items']);
        $type->controloRegistro($postValues, 0);
        $postValues['usuario_id'] = $this->getUser()->getId();
        $pedidoId = $pedidoRepository->createPedido($postValues, $items);

        return $this->json(['message' => 'Pedido creado con éxito', 'id' => $pedidoId], 201);

    }

    /**
     * @param int $pedidoId
     * @param AdminPedidoRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/{pedidoId}/completar", name:"cambio_estado", methods:["PUT"])]
    public function estadoCompletado(int $pedidoId,
                                     AdminPedidoRepository $repository): JsonResponse
    {
        $repository->checkIdExiste($pedidoId);
        $repository->cambioEstado($pedidoId);
        return $this->json([]);
    }

    /**
     * @param int $id
     * @param AdminPedidoType $type
     * @param GetRequestValidator $requestValidator
     * @param AdminPedidoRepository $repository
     * @return JsonResponse
     * @throws Exception|Throwable
     */
    #[Route("/{id}", name:"edit", requirements: ["id" => "\d+"], methods: ["PUT"])]
    public function updatePedido(int $id,
                                 AdminPedidoType $type,
                                 GetRequestValidator $requestValidator,
                                 AdminPedidoRepository $repository): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $repository->checkIdExiste($id);
        $items = $postValues['items'] ?? [];
        unset($postValues['items']);
        $type->controloRegistroEdit($postValues, $id);
        $repository->actualizaPedido($id, $items);
        return $this->json([]);
    }

    /**
     * @param int $id
     * @param AdminPedidoType $type
     * @param Request $request
     * @param AdminPedidoRepository $repository
     * @return JsonResponse
     * @throws Exception
     * @throws \Exception
     */
    #[Route("/comprobante/{id}", name:"asigna_imagen", requirements: ["id" => "\d+"], methods: ["POST"])]
    public function agregoImgComprobante(int $id,
                                 AdminPedidoType $type,
                                 Request $request,
                                 AdminPedidoRepository $repository): JsonResponse
    {
        $repository->checkIdExiste($id);

        $file = $request->files->get('comprobante_img');
        if (!$file) {
            throw new \Exception("Archivo de comprobante no recibido.");
        }
        $postValues = [
            'id' => $id,
            'comprobante_img' => $file
        ];

        $type->controloImg($postValues, $id);
        $repository->actualizarComprobante($postValues, $id);
        return $this->json(['message' => 'Comprobante subido correctamente']);
    }

    /**
     * @param int $id
     * @param AdminPedidoRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/anulo-pedido/{id}", name:"anulo_pedido", requirements: ["id" => "\d+"], methods: ["PUT"])]
    public function anuloPedido(int $id,
                                AdminPedidoRepository $repository): JsonResponse
    {
        $repository->checkIdExiste($id);
        $postValues['estado_id'] = 3;
        $repository->anularPedido($postValues, $id);
        return $this->json('Pedido anulado', 201);
    }

    /**
     * @param int $id
     * @param AdminPedidoRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/elimino-pedido/{id}", name:"elimino_pedido", requirements: ["id" => "\d+"], methods: ["PUT"])]
    public function eliminarPedido(int $id,
                                   AdminPedidoRepository $repository): JsonResponse
    {
        $repository->checkIdExiste($id);
        $repository->eliminarPedido($id);
        return $this->json('Pedido eliminado', 201);
    }



}
