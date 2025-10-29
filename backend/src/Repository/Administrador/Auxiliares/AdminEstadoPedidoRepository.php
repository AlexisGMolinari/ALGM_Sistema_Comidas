<?php

namespace App\Repository\Administrador\Auxiliares;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;

class AdminEstadoPedidoRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'estado_pedido');
    }
}