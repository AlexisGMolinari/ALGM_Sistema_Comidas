<?php

namespace App\Repository\Empresa\Perfil;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

class CajerosRepository extends TablasSimplesAbstract
{

    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'cajero', true);
    }

    /**
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function getAllCajeros(Request $request): array {
        $sql = "select caj.id, caj.nombre, caj.activo, caj.codigo 
                from cajero caj where caj.empresa_id = ". $this->empresaId;
        $arrParam = [ 'caj.nombre', 'caj.codigo'];
        return $this->getAllPaginadosOrdenadosFiltrados($request, $sql, $arrParam, 'caj.activo', true);
    }

}