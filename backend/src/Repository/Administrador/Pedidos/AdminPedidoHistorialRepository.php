<?php

namespace App\Repository\Administrador\Pedidos;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class AdminPedidoHistorialRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'pedido_historial');
    }

    /**
     * Devuelve el historial del pedido discriminado por Dealers y para le admin
     * @param int $idPedido
     * @return array
     * @throws Exception
     */
    public function getHistorial(int $idPedido): array
    {
        $where = ' where h.pedido_id = ? ';
        $sql = "select h.*, u.nombre as usuario from pedido_historial h 
                    inner join usuarios u on h.usuario_id = u.id 
                $where
                order by h.fecha";
        return $this->connection->fetchAllAssociative($sql, [$idPedido]);
    }

    /**
     * Agrega un registro al historial para el movimiento del pedido
     * @param int $idPedido
     * @param int $codigo
     * @return void
     * @throws Exception
     */
    public function agregoHistorialPedido(int $idPedido,
                                              int $codigo): void
    {
        $arrHistorial = [
            'pedido_id' => $idPedido,
            'codigo' => $codigo,
            'usuario_id' => $this->security->getUser()->getId(),
        ];
        $this->createRegistro($arrHistorial);
    }



    /**
     * Códigos del historial
     * 10: Pedido Pendiente (Creado))
     * 15: Edicion de Pedido
     * 20: Pedido Completado
     * 30: Pedido Anulado
     * 35: Pedido Eliminado
     */
}