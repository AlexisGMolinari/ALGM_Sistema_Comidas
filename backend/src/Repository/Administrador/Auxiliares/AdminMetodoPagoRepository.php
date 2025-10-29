<?php

namespace App\Repository\Administrador\Auxiliares;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;

class AdminMetodoPagoRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'metodo_pago');
    }

}