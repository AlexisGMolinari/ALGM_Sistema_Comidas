<?php

namespace App\Repository\Administrador\Pedidos;


use App\Repository\Administrador\Auxiliares\AdminDetallePedidoRepository;
use App\Repository\Paginador;
use App\Repository\Stock\MovimientoStock\MovimientoStockRepository;
use App\Repository\Stock\Producto\ProductoRepository;
use App\Repository\TablasSimplesAbstract;
use App\Utils\ImageUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class AdminPedidoRepository extends TablasSimplesAbstract
{
    public const PATH_COMPROBANTES = 'imagenes/comprobantes';
    private const BROWSE_SQL = "SELECT p.*, mp.nombre AS metodoPago, ep.nombre AS estadoPedido, u.nombre AS nombreUsuario
                                FROM pedidos p
                                INNER JOIN metodo_pago mp ON p.metodo_pago_id = mp.id
                                INNER JOIN estado_pedido ep ON p.estado_id = ep.id
                                INNER JOIN usuarios u ON p.usuario_id = u.id ";
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'pedidos', true);
    }

    /**
     * @param Request $request
     * @param bool $all_pedidos
     * @return array
     * @throws Exception
     */
    public function getAllPaginados(Request $request, bool $all_pedidos = false): array
    {
        $camposRequest = $request->query->all();
        $usuarioId = $this->security->getUser()->getId();
        $empresaId = $this->security->getUser()->getEmpresa();

        $sql = self::BROWSE_SQL . " WHERE p.empresa_id = $empresaId";

        if (!$all_pedidos) {
            $caja = $this->getCajaAbierta($usuarioId, $empresaId);
            $sql .= " AND p.caja_id = " . (int) $caja['id'];
        }

        $arrParam = [ 'p.id', 'p.nombre_cliente', 'mp.nombre', 'u.nombre', 'ep.nombre', 'p.total', 'p.fecha_creado'];
        $paginador = new Paginador();
        $paginador->setConnection($this->connection)
            ->setServerSideParams($camposRequest)
            ->setSql($sql)
            ->setContinuaWhere(true)
            ->setCamposAFiltrar($arrParam);  //pasar campos con alias de tabla

        return $paginador->getServerSideRegistros();
    }

    /**
     * Pasa estado a Completado
     * @param int $pedidoId
     * @return void
     * @throws Exception
     */
    public function cambioEstado(int $pedidoId): void
    {
        $postValues['estado_id'] = 2;
        $this->updateRegistro($postValues, $pedidoId);
    }

    /**
     * @param int $idPedido
     * @param int $empresa_id
     * @return array
     * @throws Exception
     */
    public function getByIdPedido(int $idPedido, int $empresa_id): array
    {
        $usuarioId = $this->security->getUser()->getId();
        $caja = $this->getCajaAbierta($usuarioId, $empresa_id);

        $sql = self::BROWSE_SQL . " WHERE p.id = :id AND p.caja_id = :caja_id";
        $pedido = $this->connection->fetchAssociative($sql, [
            'id' => $idPedido,
            'caja_id' => $caja['id']
        ]);
        if (!$pedido) {
            throw new HttpException(404, "No se encontró el pedido o no pertenece a su caja.");
        }
        return $pedido;
    }

    /**
     * @param int $usuarioId
     * @param int $empresaId
     * @return array
     * @throws Exception
     */
    private function getCajaAbierta(int $usuarioId, int $empresaId): array
    {
        $caja = $this->connection->fetchAssociative(
            "SELECT * FROM caja
                    WHERE abierta_usuario_id = :usuario AND empresa_id = :empresa
                    AND abierta = 1 LIMIT 1", ['usuario' => $usuarioId, 'empresa' => $empresaId]
        );
        if (!$caja) {
            throw new HttpException(400, "No hay caja abierta para este usuario.");
        }
        return $caja;
    }


    /**
     * @param array $postValues
     * @param array $items
     * @param int $empresa_id
     * @return int
     * @throws Exception
     */
    public function createPedido(array $postValues, array $items, int $empresa_id): int
    {
        $this->connection->beginTransaction();
        $postValues['caja_id'] = $this->getCajaAbierta($postValues['usuario_id'], $empresa_id)['id'];

        // Insertar Pedido
        $pedidoId = $this->createRegistro($postValues);

        $productoRepo = new ProductoRepository($this->connection, $this->security);
        $movimientoRepo = new MovimientoStockRepository($this->connection, $this->security);

        // Insertar Items
        foreach ($items as $item) {
            $arrItems = [
                'pedido_id' => $pedidoId,
                'producto_id' => $item['producto_id'],
                'precio' => $item['precio'],
                'cantidad' => $item['cantidad'],
                'empresa_id' => $empresa_id
            ];
            $productoId = $arrItems['producto_id'];
            $cantidad = $arrItems['cantidad'];

            // Insertar detalle del pedido
            $this->connection->insert('detalle_pedidos', $arrItems);

            // Verificamos si el producto es un combo
            if ($productoRepo->esCombo($productoId)) {
                // Descontar componentes del combo
                $productoRepo->descontarStockCombo($productoId, $cantidad, 2, $pedidoId);
                $productoRepo->actualizoStock($productoId, 2, $cantidad, $empresa_id);
            } else {
                // Producto individual: actualizar stock y registrar movimiento
                $productoRepo->actualizoStock($productoId, 2, $cantidad, $empresa_id);

                $arrMov = [
                    'pedido_id' => $pedidoId,
                    'producto_id' => $item['producto_id'],
                    'tipo_movimiento_id' => 2,
                    'cantidad' => $item['cantidad'],
                    'empresa_id' => $empresa_id
                ];
                $movimientoRepo->insertaMovimiento($arrMov);

            }
        }
        // asienta historial
        (new AdminPedidoHistorialRepository($this->connection, $this->security))
            ->agregoHistorialPedido($pedidoId, 10, $empresa_id);

        $this->connection->commit();
        return $pedidoId;
    }

    /**
     * @param int $idPedido
     * @param array $items
     * @param int $empresa_id
     * @return void
     * @throws Exception
     * @throws Throwable
     */
    public function actualizaPedido(int $idPedido, array $items, int $empresa_id): void
    {
        $pedido = $this->getByIdPedido($idPedido, $empresa_id);
        if($pedido['estado_id'] == 2){
            throw new HttpException(400, "El pedido ya se encuentra completado, no puede modificarlo.");
        }

        $detallePedidoRepository = (new AdminDetallePedidoRepository($this->connection, $this->security));
        $detallePedido = $detallePedidoRepository->getDetalleByPedidoId($idPedido, $empresa_id);

        //Map de lo que existe por producto_id
        $actualPorProducto = [];
        foreach ($detallePedido as $d) {
            $actualPorProducto[(int)$d['producto_id']] = [
                'cantidad' => (int)$d['cantidad'],
                'precio'   => (float)$d['precio'],
            ];
        }
        // Normalizar entrada (por si vienen productos repetidos en el payload)
        $nuevoPorProducto = [];
        foreach ($items as $it) {
            $pid = (int)$it['producto_id'];
            $cant = (int)$it['cantidad'];
            $precio = (float)$it['precio'];
            if (!isset($nuevoPorProducto[$pid])) {
                $nuevoPorProducto[$pid] = ['cantidad' => 0, 'precio' => $precio];
            }
            $nuevoPorProducto[$pid]['cantidad'] += $cant; // merge si vinieran duplicados
            // si llega precio distinto en duplicados, puedes decidir último gana o validar
            $nuevoPorProducto[$pid]['precio'] = $precio;
        }

        $this->connection->beginTransaction();
        try {
            $productoRepo   = new ProductoRepository($this->connection, $this->security);
            $movRepo        = new MovimientoStockRepository($this->connection, $this->security);

            $todosIds = array_unique(array_merge(array_keys($actualPorProducto), array_keys($nuevoPorProducto)));
            $nuevoTotal = 0.0;

            foreach ($todosIds as $pid) {
                $oldQty = $actualPorProducto[$pid]['cantidad'] ?? 0;
                $oldPrice = $actualPorProducto[$pid]['precio'] ?? 0.0;

                $newQty = $nuevoPorProducto[$pid]['cantidad'] ?? 0;
                $newPrice = $nuevoPorProducto[$pid]['precio'] ?? $oldPrice;

                $delta = $newQty - $oldQty;

                if ($oldQty > 0 && $newQty === 0) {
                    // eliminar línea
                    $this->connection->delete('detalle_pedidos', [
                        'pedido_id'   => $idPedido,
                        'producto_id' => $pid
                    ]);
                    // devolver stock por la cantidad que quitaste
                    if ($delta < 0) {
                        $productoRepo->actualizoStock($pid, 1, -$delta, $empresa_id); // 1 = entrada
                        $movRepo->insertaMovimiento([
                            'pedido_id' => $idPedido,
                            'producto_id' => $pid,
                            'tipo_movimiento_id' => 1,
                            'cantidad' => -$delta
                        ]);
                    }
                } elseif ($oldQty === 0 && $newQty > 0) {
                    // insertar
                    $this->connection->insert('detalle_pedidos', [
                        'pedido_id'   => $idPedido,
                        'producto_id' => $pid,
                        'precio'      => $newPrice,
                        'cantidad'    => $newQty
                    ]);
                    // descontar stock por lo nuevo
                    $productoRepo->actualizoStock($pid, 2, $newQty, $empresa_id); // 2 = salida
                    $movRepo->insertaMovimiento([
                        'pedido_id' => $idPedido,
                        'producto_id' => $pid,
                        'tipo_movimiento_id' => 2,
                        'cantidad' => $newQty
                    ]);
                } else {
                    // actualizar cantidad y/o precio
                    $this->connection->update('detalle_pedidos', [
                        'cantidad' => $newQty,
                        'precio'   => $newPrice
                    ], [
                        'pedido_id' => $idPedido,
                        'producto_id' => $pid
                    ]);

                    if ($delta > 0) {
                        // aumentaste cantidad => salida
                        $productoRepo->actualizoStock($pid, 2, $delta, $empresa_id);
                        $movRepo->insertaMovimiento([
                            'pedido_id' => $idPedido,
                            'producto_id' => $pid,
                            'tipo_movimiento_id' => 2,
                            'cantidad' => $delta
                        ]);
                    } elseif ($delta < 0) {
                        // bajaste cantidad => entrada
                        $productoRepo->actualizoStock($pid, 1, -$delta, $empresa_id);
                        $movRepo->insertaMovimiento([
                            'pedido_id' => $idPedido,
                            'producto_id' => $pid,
                            'tipo_movimiento_id' => 1,
                            'cantidad' => -$delta
                        ]);
                    }
                }

                // acumular total final (cantidad final * precio final)
                $nuevoTotal += $newQty * $newPrice;
            }

            // setear total final (no sumarlo sobre el actual)
            $this->connection->update('pedidos', ['total' => $nuevoTotal], ['id' => $idPedido]);

            (new AdminPedidoHistorialRepository($this->connection, $this->security))
                ->agregoHistorialPedido($idPedido, 15, $empresa_id);

            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * @param array $postValues
     * @param int $id
     * @return void
     * @throws Exception
     * @throws \Exception
     */
    public function actualizarComprobante(array $postValues, int $id): void
    {
        // Verificamos si viene archivo de imagen
        if (isset($postValues['comprobante_img']) && $postValues['comprobante_img'] instanceof UploadedFile) {
            $archivo = $postValues['comprobante_img'];

            // Guardamos la imagen físicamente
            $nuevoNombre = ImageUtils::PutImageOnPhysicalPath($archivo, self::PATH_COMPROBANTES);

            // Actualizamos el nombre en la base de datos
            $this->connection->update('pedidos', [
                'comprobante_img' => $nuevoNombre,
                'estado_id' => 2,
            ], ['id' => $id]);
        } else {
            throw new \Exception("No se recibió archivo válido para el comprobante.");
        }
    }

    /**
     * @param int $idPedido
     * @param int $empresa_id
     * @return void
     * @throws Exception
     */
    public function eliminarPedido(int $idPedido, int $empresa_id): void
    {
        $registro = $this->getByIdPedido($idPedido, $empresa_id);
        if($registro['estado_id'] != 3) {
            throw new HttpException(400, "El pedido debe ser anulado para eliminarlo.");
        }

        $this->connection->beginTransaction();

        // 1. Obtener los detalles del pedido
        $detallePedidoRepository = new AdminDetallePedidoRepository($this->connection, $this->security);
        $detalles = $detallePedidoRepository->getDetalleByPedidoId($idPedido, $empresa_id);

        // 2. Reestablecer stock por cada producto
        $productoRepository = new ProductoRepository($this->connection, $this->security);
        foreach ($detalles as $detalle) {
            $productoRepository->actualizoStock($detalle['producto_id'], 1, $detalle['cantidad'], $empresa_id); // tipo_movimiento_id 1 = ingreso

            (new MovimientoStockRepository($this->connection, $this->security))
                ->insertaMovimiento([
                    'pedido_id' => $idPedido,
                    'producto_id' => $detalle['producto_id'],
                    'tipo_movimiento_id' => 1, // Reposición
                    'cantidad' => $detalle['cantidad']
                ]);
        }

        // 3. Eliminar detalles del pedido
        $this->connection->delete('detalle_pedidos', ['pedido_id' => $idPedido]);

        // 4. Cambia el estado a Eliminado
        $this->updateRegistro(['estado_id' => 4], $idPedido);

        $this->connection->commit();
        // 5. Registrar historial de cambio de estado
        (new AdminPedidoHistorialRepository($this->connection, $this->security))
            ->agregoHistorialPedido($idPedido, 35, $empresa_id);
    }

    /**
     * @param array $postValues
     * @param int $idPedido
     * @param int $empresa_id
     * @return void
     * @throws Exception
     */
    public function anularPedido(array $postValues, int $idPedido, int $empresa_id): void
    {
        $registro = $this->getByIdPedido($idPedido, $empresa_id);
        if ($registro['estado_id'] === 3) {
            throw new \InvalidArgumentException("El pedido ya está anulado.");
        }
        $this->connection->beginTransaction();

        $detallePedidoRepo = new AdminDetallePedidoRepository($this->connection, $this->security);
        $productoRepo = new ProductoRepository($this->connection, $this->security);
        $movimientoRepo = new MovimientoStockRepository($this->connection, $this->security);

        $detalles = $detallePedidoRepo->getDetalleByPedidoId($idPedido, $empresa_id);

        foreach ($detalles as $detalle) {
            $productoId = $detalle['producto_id'];
            $cantidad = $detalle['cantidad'];

            if ($productoRepo->esCombo($productoId)) {
                // Es un combo → reponer componentes
                $productoRepo->descontarStockCombo($productoId, $cantidad, 1, $idPedido);
                $productoRepo->actualizoStock($productoId, 1, $cantidad, $empresa_id);
            } else {
                // Es producto individual → reponer stock directamente
                $productoRepo->actualizoStock($productoId, 1, $cantidad, $empresa_id); // 1 = ingreso

                $movimientoRepo->insertaMovimiento([
                    'pedido_id' => $idPedido,
                    'producto_id' => $productoId,
                    'tipo_movimiento_id' => 1, // Reposición
                    'cantidad' => $cantidad,
                    'empresa_id' => $empresa_id,
                ]);
            }
        }

        $this->updateRegistro($postValues, $idPedido);

        // 5. Registrar historial de cambio de estado
        (new AdminPedidoHistorialRepository($this->connection, $this->security))
            ->agregoHistorialPedido($idPedido, 35, $empresa_id);

        $this->connection->commit();
    }


    /**
     * Devuelve los reportes de los últimos 7 días
     * @return array
     * @throws Exception
     */
    public function getPedidosUltimaSemana(): array
    {
        $empresaId = $this->security->getUser()->getEmpresa();

        $sql = "
        SELECT
            DATE(p.fecha_creado) AS date,
            SUM(p.total) AS totalSales,
            (
                SELECT IFNULL(SUM(e.monto), 0)
                FROM egresos e
                WHERE DATE(e.fecha) = DATE(p.fecha_creado)
                  AND e.empresa_id = :empresa_id
            ) AS totalExpenses,
            COUNT(p.id) AS ordersCount
        FROM pedidos p
        WHERE p.fecha_creado >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
          AND p.estado_id != 3
          AND p.empresa_id = :empresa_id
        GROUP BY DATE(p.fecha_creado)
        ORDER BY DATE(p.fecha_creado) DESC
    ";

        return $this->connection->fetchAllAssociative($sql, [
            'empresa_id' => $empresaId
        ]);
    }


    /**
     * Devuelve los reportes del mes
     * @param int $empresaId
     * @param string|null $month
     * @return array
     * @throws Exception
     */
    public function getPedidosMensual(int $empresaId, ?string $month = null): array
    {
        // --- Determinar mes actual y anterior ---
        $year = date('Y');
        if ($month) {
            // Si el frontend envía solo el número del mes (ej: "10")
            if (strlen($month) === 1 || strlen($month) === 2) {
                $mesActual = sprintf('%s-%02d', $year, (int)$month);
            } else {
                // Si viene con año (ej: "2025-10")
                $mesActual = $month;
            }
        } else {
            $mesActual = date('Y-m');
        }

        // Mes anterior (YYYY-MM)
        $mesAnterior = date('Y-m', strtotime("$mesActual-01 -1 month"));

        // --- Consultas ---
        // 1. Ventas y pedidos
        $sqlVentas = "SELECT 
        DATE_FORMAT(p.fecha_creado, '%Y-%m') AS month, 
        SUM(p.total) AS totalSales, 
        COUNT(p.id) AS ordersCount
        FROM pedidos p 
        WHERE DATE_FORMAT(p.fecha_creado, '%Y-%m') = :mes
          AND p.empresa_id = :empresa
          AND p.estado_id != 3
        GROUP BY month";

        $paramsActual = ['mes' => $mesActual, 'empresa' => $empresaId];
        $paramsAnterior = ['mes' => $mesAnterior, 'empresa' => $empresaId];

        $ventasActual = $this->connection->fetchAssociative($sqlVentas, $paramsActual)
            ?: ['totalSales' => 0, 'ordersCount' => 0];

        $ventasPrevias = $this->connection->fetchAssociative($sqlVentas, $paramsAnterior)
            ?: ['totalSales' => 0, 'ordersCount' => 0];

        // 2. Egresos
        $sqlEgresos = "SELECT SUM(monto) FROM egresos 
                        WHERE DATE_FORMAT(fecha, '%Y-%m') = :mes AND empresa_id = :empresa";
        $egresosActual = (float)($this->connection->fetchOne($sqlEgresos, $paramsActual) ?? 0);
        $egresosPrevios = (float)($this->connection->fetchOne($sqlEgresos, $paramsAnterior) ?? 0);


        // 3. Egresos por categoría
        $sqlEgresosPorCat = "SELECT c.nombre AS name, SUM(e.monto) AS value 
                            FROM egresos e
                            INNER JOIN categoria_egreso_expensas c ON e.categoria_id = c.id
                            WHERE DATE_FORMAT(e.fecha, '%Y-%m') = :mes AND e.empresa_id = :empresa
                            GROUP BY c.nombre";
        $egresosPorCategoria = $this->connection->fetchAllAssociative(
            $sqlEgresosPorCat,
            $paramsActual
        );

        // --- Procesar resultados ---
        $ventasTotales = (float)$ventasActual['totalSales'];
        $pedidosTotales = (int)$ventasActual['ordersCount'];
        $balance = $ventasTotales - $egresosActual;

        // --- Variaciones porcentuales ---
        $variacionVentas = $this->calcVariation($ventasPrevias['totalSales'] ?? 0, $ventasTotales);
        $variacionEgresos = $this->calcVariation($egresosPrevios, $egresosActual);
        $variacionBalance = $this->calcVariation(
            ($ventasPrevias['totalSales'] ?? 0) - $egresosPrevios,
            $balance
        );

        return [
            'month' => $mesActual,
            'previousMonth' => $mesAnterior,
            'totalSales' => $ventasTotales,
            'totalExpenses' => $egresosActual,
            'balance' => $balance,
            'ordersCount' => $pedidosTotales,
            'salesChange' => $variacionVentas,
            'expensesChange' => $variacionEgresos,
            'balanceChange' => $variacionBalance,
            'salesByCategory' => [
                ['name' => 'General', 'value' => $ventasTotales]
            ],
            'expensesByCategory' => array_map(fn($item) => [
                'name' => ucfirst($item['name']),
                'value' => (float)$item['value']
            ], $egresosPorCategoria)
        ];
    }

    /**
     * Calcula el porcentaje de variación entre dos valores
     */
    private function calcVariation(float $prev, float $current): float
    {
        if ($prev == 0 && $current == 0) return 0;
        if ($prev == 0) return 100;
        return round((($current - $prev) / $prev) * 100, 2, PHP_ROUND_HALF_UP);
    }

    /**
     * Contador de pedidos pendientes
     * @param array $caja
     * @param int $empresaId
     * @return int
     * @throws Exception
     */
    public function contarPedidosPendientesEnCajaAbierta(array $caja, int $empresaId): int
    {
        if (!$caja) {
            return 0;
        }

        $fechaDesde = $caja['openedAt'];
        $fechaHasta = $caja['closedAt'] ?? date('Y-m-d H:i:s');
        $usuarioId = $this->security->getUser()->getId();
        $sql = "
        SELECT COUNT(*) FROM pedidos 
        WHERE estado_id = 1
          AND fecha_creado BETWEEN :desde AND :hasta
          AND usuario_id = :usuarioId
            AND empresa_id = :empresaId
    ";

        return (int) $this->connection->fetchOne($sql, [
            'desde' => $fechaDesde,
            'hasta' => $fechaHasta,
            'usuarioId' => $usuarioId,
            'empresaId' => $empresaId
        ]);
    }


}