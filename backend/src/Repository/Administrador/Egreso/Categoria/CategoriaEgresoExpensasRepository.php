<?php

namespace App\Repository\Administrador\Egreso\Categoria;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;

class CategoriaEgresoExpensasRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'categoria_egreso_expensas');
    }

}