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
     * @param int $idPedido
     * @return array
     * @throws Exception
     */
    public function getDetalleByPedidoId(int $idPedido): array
    {
        $sql = "SELECT dp.*, prod.nombre as nombre_producto FROM " . $this->nombreTabla . " dp
                INNER JOIN producto prod ON prod.id = dp.producto_id";
        $where = " WHERE dp.pedido_id = ? ";
        $sql .= $where;
        return $this->connection->fetchAllAssociative($sql, [$idPedido]);
    }

}