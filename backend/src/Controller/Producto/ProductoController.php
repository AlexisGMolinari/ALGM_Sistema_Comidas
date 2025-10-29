<?php

declare(strict_types=1);

namespace App\Controller\Producto;

use App\Form\Stock\Producto\ProductoType;
use App\Repository\Stock\Categoria\CategoriaRepository;
use App\Repository\Stock\Producto\ProductoRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route("api/productos", name: "api_productos_")]
class ProductoController extends AbstractController
{
    /**
     * @param GetRequestValidator $requestValidator
     * @param ProductoRepository $productoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/", name: "index", methods: ["GET"])]
    public function index(GetRequestValidator $requestValidator,
                          ProductoRepository   $productoRepository): JsonResponse
    {
        $registros = $productoRepository->getAllPaginados($requestValidator->getRequest());
        return $this->json($registros);
    }

    /**
     * @param int $id
     * @param ProductoRepository $productoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/{id}", name: "getOne", requirements: ["id" => "\d+"], methods: ["GET"])]
    public function getById(int $id,
                            ProductoRepository $productoRepository): JsonResponse
    {
        $productoRepository->checkIdExiste($id);
        $registro = $productoRepository->getProdById($id);
        return $this->json($registro);
    }

    /**
     * @param int $categoriaId
     * @param ProductoRepository $productoRepository
     * @param CategoriaRepository $categoriaRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/categoria/{categoriaId}", name: "getProds_by_categoria", requirements: ["id" => "\d+"], methods: ["GET"])]
    public function getProdByCategoria(int $categoriaId,
                                       ProductoRepository $productoRepository,
                                       CategoriaRepository $categoriaRepository): JsonResponse
    {
        $categoriaRepository->checkIdExiste($categoriaId);
        $registros = $productoRepository->getProductosByCategoria($categoriaId);
        return $this->json($registros);
    }

    /**
     * Devuelve todos los productos que son combos (categoría = 'Combos')
     *
     * @param ProductoRepository $productoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/combos", name: "listar_combos", methods: ["GET"])]
    public function getCombos(ProductoRepository $productoRepository): JsonResponse
    {
        $combos = $productoRepository->getCombos();
        return $this->json($combos);
    }

    /**
     * @param GetRequestValidator $requestValidator
     * @param ProductoType $productoType
     * @param ProductoRepository $productoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/", name:"add", methods:["POST"])]
    public function create(GetRequestValidator $requestValidator,
                                   ProductoType $productoType,
                                   ProductoRepository $productoRepository): JsonResponse {
        $valores = $requestValidator->getRestBody();
        $productoType->controloRegistro($valores);
        $productoRepository->createRegistro($valores);
        return $this->json([], 201);
    }

    /**
     * @param int $id
     * @param GetRequestValidator $requestValidator
     * @param ProductoType $productoType
     * @param ProductoRepository $productoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/{id}", name:"edit", requirements: ["id" => "\d+"], methods: ["PUT"])]
    public function update(int $id,
                           GetRequestValidator $requestValidator,
                           ProductoType $productoType,
                           ProductoRepository $productoRepository): JsonResponse {
        $productoRepository->checkIdExiste($id);
        $valores = $requestValidator->getRestBody();
        $productoType->controloRegistro($valores, $id);
        $productoRepository->updateRegistro($valores, $id);
        return $this->json([]);
    }

    /**
     * @param int $id
     * @param ProductoRepository $productoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/desactivar/{id}", name:"inactivar_prod", requirements: ["id" => "\d+"], methods: ["PUT"])]
    public function desactivarProducto(int $id,
                           ProductoRepository $productoRepository): JsonResponse {
        $productoRepository->checkIdExiste($id);
        $productoRepository->deshabilitarProducto($id);
        return $this->json([]);
    }

    /**
     * @param int $productoId
     * @param GetRequestValidator $requestValidator
     * @param ProductoType $productoType
     * @param ProductoRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/{productoId}/stock", name:"agregado_stock", requirements: ["id" => "\d+"], methods: ["POST"])]
    public function actualizarCantidad(int $productoId,
                                       GetRequestValidator $requestValidator,
                                       ProductoType $productoType,
                                       ProductoRepository $repository): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $repository->checkIdExiste($productoId);
        $productoType->controloStock($postValues, $productoId);
        $repository->actualizaStockProducto($postValues);
        return $this->json([]);
    }

    /**
     * @param GetRequestValidator $requestValidator
     * @param ProductoType $productoType
     * @param ProductoRepository $productoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/combos", name:"crear_combo", methods: ["POST"])]
    public function crearCombo(GetRequestValidator $requestValidator,
                               ProductoType $productoType,
                               ProductoRepository $productoRepository): JsonResponse
    {
        $valores = $requestValidator->getRestBody();
        $productoType->controloCombo($valores);

        if (!isset($valores['componentes']) || !is_array($valores['componentes'])) {
            throw new \InvalidArgumentException("Se requieren los productos componentes del combo.");
        }
        $componentes = $valores['componentes'];
        unset($valores['componentes']);

        $comboId = $productoRepository->createRegistro($valores);
        $productoRepository->vincularComponentesCombo($comboId, $componentes);
        return $this->json(['id' => $comboId], 201);
    }

    /**
     * @param GetRequestValidator $requestValidator
     * @param ProductoRepository $productoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/combos/descontar", name: "descontar_combo", methods: ["POST"])]
    public function descontarCombo(GetRequestValidator $requestValidator,
                                   ProductoRepository   $productoRepository): JsonResponse
    {
        $valores = $requestValidator->getRestBody();
        if (!isset($valores['combo_id'], $valores['cantidad'])) {
            throw new \InvalidArgumentException("Se requieren los campos 'combo_id' y 'cantidad'");
        }
        $comboId = (int)$valores['combo_id'];
        $cantidad = (int)$valores['cantidad'];

        $productoRepository->checkIdExiste($comboId);
        $productoRepository->descontarStockCombo($comboId, $cantidad, 2);

        return $this->json(['message' => 'Stock descontado correctamente']);
    }

    /**
     * @param int $id
     * @param ProductoRepository $productoRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/{id}", name: "del", requirements: ["id" => "\d+"], methods:["DELETE"])]
    public function deleteRegistro(int $id,
                                   ProductoRepository $productoRepository): JsonResponse
    {
        $productoRepository->checkIdExiste($id);
        $productoRepository->deleteRegistro($id);
        return $this->json([]);
    }
}
