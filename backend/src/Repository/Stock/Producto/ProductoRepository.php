<?php

namespace App\Repository\Stock\Producto;

use App\Repository\Paginador;
use App\Repository\Stock\MovimientoStock\MovimientoStockRepository;
use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProductoRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'producto');
    }

    /**
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function getAllPaginados(Request $request): array
    {
        $camposRequest = $request->query->all();

        $sql = "SELECT prod.*, cat.nombre AS nombre_producto FROM " . $this->nombreTabla . " prod
            inner join categoria_producto cat on prod.categoria_prod_id = cat.id";

        $arrParam = [ 'prod.id','prod.nombre', 'prod.precio', 'prod.stock_actual', 'cat.nombre'];

        $paginador = new Paginador();
        $paginador->setConnection($this->connection)
            ->setServerSideParams($camposRequest)
            ->setCampoActivo('prod.activo')
            ->setSql($sql)
            ->setContinuaWhere(false)
            ->setCamposAFiltrar($arrParam);  //pasar campos con alias de tabla

        return $paginador->getServerSideRegistros();
    }

    /**
     * @param int $id
     * @return array
     * @throws Exception
     */
    public function getProdById(int $id): array
    {
        $sql = "SELECT prod.*, cat.nombre AS nombreCategoria FROM " . $this->nombreTabla . " prod
                INNER JOIN categoria_producto cat ON prod.categoria_prod_id = cat.id
                WHERE prod.id = ?";
        return $this->connection->fetchAssociative($sql, [$id]);
    }

    /**
     * @param int $categoriaId
     * @return array
     * @throws Exception
     */
    public function getProductosByCategoria(int $categoriaId): array
    {
        $where = " WHERE cat.id = ? AND prod.activo = 1";
        $sql = "SELECT prod.*, cat.nombre AS nombreCategoria FROM " . $this->nombreTabla . " prod
                INNER JOIN categoria_producto cat ON prod.categoria_prod_id = cat.id ";
        $sql .= $where;
        return $this->connection->fetchAllAssociative($sql, [$categoriaId]);
    }

    /**
     * @param int $id
     * @return void
     * @throws Exception
     */
    public function deshabilitarProducto(int $id): void
    {
        $this->connection->update($this->nombreTabla, ['activo' => 0], ['id' => $id]);
    }

    /**
     * @param int $id
     * @param int $tipoMov
     * @param float $cantidad
     * @return void
     * @throws Exception
     */
    public function actualizoStock (int $id, int $tipoMov, float $cantidad): void
    {
        // Obtener stock actual y nombre del producto
        $sqlProducto = "SELECT stock_actual, nombre FROM producto WHERE id = ?";
        $producto = $this->connection->fetchAssociative($sqlProducto, [$id]);

        $stockActual = isset($producto['stock_actual']) ? (float) $producto['stock_actual'] : 0;
        $nombreProducto = $producto['nombre'] ?? 'Producto desconocido';

        if ($tipoMov === 2) { // salida
            if ($stockActual < $cantidad) {
                throw new \RuntimeException(
                    "No hay suficiente stock para el producto '$nombreProducto'. Stock disponible: $stockActual, cantidad solicitada: $cantidad"
                );
            }
            $signo = '-';
        } else { // entrada
            $signo = '+';
        }

        $sql = "UPDATE producto SET stock_actual = ifnull(stock_actual,0) $signo $cantidad WHERE id = ?";
        $this->connection->executeStatement($sql, [$id]);
    }

    /**
     * @param array $postValues
     * @return void
     * @throws Exception
     */
    public function actualizaStockProducto(array $postValues): void
    {
        $this->connection->beginTransaction();

        $this->actualizoStock($postValues['id'], $postValues['tipo_movimiento'], $postValues['cantidad']);

        $arrMov = [
            'producto_id' => $postValues['id'],
            'tipo_movimiento_id' => $postValues['tipo_movimiento'],
            'cantidad' => $postValues['cantidad']
        ];
        (new MovimientoStockRepository($this->connection, $this->security))
            ->insertaMovimiento($arrMov);
        $this->connection->commit();
    }

    /**
     * @param int $comboId
     * @param int $cantidadCombo
     * @param int $tipoMov
     * @param int|null $pedidoId
     * @return void
     * @throws Exception
     */
    public function descontarStockCombo(int $comboId, int $cantidadCombo, int $tipoMov, ?int $pedidoId = null): void
    {
        $sql = "SELECT producto_id, cantidad 
            FROM combo_producto 
            WHERE combo_id = ?";
        $componentes = $this->connection->fetchAllAssociative($sql, [$comboId]);

        $movimientoRepo = new MovimientoStockRepository($this->connection, $this->security);
        $this->connection->beginTransaction();
        // Validación previa antes de descontar
        foreach ($componentes as $comp) {
            $productoId = (int)$comp['producto_id'];
            $cantidadNecesaria = (int)$comp['cantidad'] * $cantidadCombo;

            $stock = $this->getProdById($productoId);
            $nombreProd = $stock['nombre'];
            if ($stock['stock_actual'] < $cantidadNecesaria) {
                throw new HttpException(400,"No hay stock suficiente para el producto ID $nombreProd");
            }
        }
        foreach ($componentes as $comp) {
            $productoId = (int)$comp['producto_id'];
            $cantidadDescontar = (int)$comp['cantidad'] * $cantidadCombo;

            $this->actualizoStock($productoId, $tipoMov, $cantidadDescontar); // 2 = salida
            $movimientoRepo->insertaMovimiento([
                'producto_id' => $productoId,
                'tipo_movimiento_id' => $tipoMov,
                'cantidad' => $cantidadDescontar,
                'pedido_id' => $pedidoId
            ]);
        }
        $this->connection->commit();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCombos(): array
    {
        $sql = "SELECT p.* 
            FROM producto p
            INNER JOIN categoria_producto cp ON p.categoria_prod_id = cp.id
            WHERE cp.nombre = 'Combos' AND p.activo = 1
            ORDER BY p.nombre";

        return $this->connection->fetchAllAssociative($sql);
    }

    /**
     * Carga los productos dentro del combo
     *
     * @param int $comboId
     * @param array $componentes
     * @return void
     * @throws Exception
     */
    public function vincularComponentesCombo(int $comboId, array $componentes): void
    {
        foreach ($componentes as $item) {
            if (!isset($item['producto_id'], $item['cantidad'])) {
                throw new \InvalidArgumentException("Faltan datos de producto o cantidad en un componente.");
            }

            $this->connection->insert('combo_producto', [
                'combo_id' => $comboId,
                'producto_id' => $item['producto_id'],
                'cantidad' => $item['cantidad']
            ]);
        }
    }

    /**
     * Identifica si dentro del Carrito del Pedido existe un Combo
     * @param int $productoId
     * @return bool
     * @throws Exception
     */
    public function esCombo(int $productoId): bool
    {
        $sql = "SELECT COUNT(*) FROM combo_producto WHERE combo_id = ?";
        return $this->connection->fetchOne($sql, [$productoId]) > 0;
    }


}