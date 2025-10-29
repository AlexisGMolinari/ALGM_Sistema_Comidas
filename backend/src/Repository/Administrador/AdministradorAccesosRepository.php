<?php

namespace App\Repository\Administrador;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class AdministradorAccesosRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'acceso_acceso', false);
    }


    /**
     * Devuelve todos los accesos comunes que tiene cualquier empresa
     * @return array[]
     * @throws Exception
     */
    public function getAccesosComunes(): array
    {
        $sql = "select acc.* from acceso_acceso acc where id not in (2, 6)";
        return $this->connection->fetchAllAssociative($sql);
    }
}