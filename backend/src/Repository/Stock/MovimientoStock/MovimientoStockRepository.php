<?php

namespace App\Repository\Stock\MovimientoStock;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class MovimientoStockRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'movimiento_stock');
    }

    /**
     * @param array $postValues
     * @return void
     * @throws Exception
     */
    public function insertaMovimiento(array $postValues): void
    {
        $this->connection->insert($this->nombreTabla, $postValues);
    }

}