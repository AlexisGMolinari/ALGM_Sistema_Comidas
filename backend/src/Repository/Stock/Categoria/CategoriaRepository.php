<?php

namespace App\Repository\Stock\Categoria;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;

class CategoriaRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'categoria_producto');
    }


}