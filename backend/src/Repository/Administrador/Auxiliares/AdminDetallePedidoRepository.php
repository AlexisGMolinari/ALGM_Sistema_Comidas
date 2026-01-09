<?php

namespace App\Repository\Administrador\Auxiliares;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class AdminDetallePedidoRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'detalle_pedidos');
    }

    /**
     * @param int $pedidoId
     * @param int $empresaId
     * @return array
     * @throws Exception
     */
    public function getDetalleByPedidoId(int $pedidoId, int $empresaId): array
    {
        $sql = "SELECT dp.*, prod.nombre AS nombre_producto FROM detalle_pedidos dp
        INNER JOIN pedidos p ON p.id = dp.pedido_id
        INNER JOIN producto prod ON prod.id = dp.producto_id
        WHERE dp.pedido_id = ? AND p.empresa_id = ? AND prod.empresa_id = ?";
        return $this->connection->fetchAllAssociative($sql, [
            $pedidoId,
            $empresaId,
            $empresaId,
        ]);
    }

}