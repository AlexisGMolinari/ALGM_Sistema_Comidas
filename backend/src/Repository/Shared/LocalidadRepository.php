<?php

namespace App\Repository\Shared;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class LocalidadRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'localidad');
    }


    /**
     * función que trae una localidad por codigo postal y nombre - viene de AFIP
     * @throws Exception
     */
    public function getByNombreCodPostalyPcia(string $nombre, string $codPostal, int $pcia): array|bool
    {
        $sql = "select * from localidad l  
                where l.nombre = ? 
                and l.codigo_postal = ? 
                and l.provincia_afip = ?";
        return $this->connection->fetchAssociative($sql, [$nombre, $codPostal, $pcia]);
    }

    /**
     * función que controla si existe la localidad la actualiza sino la da de alta
     * @throws Exception
     */
    public function guardoLocalidad(array $postValues): int
    {
        $localidad = $this->getByNombreCodPostalyPcia($postValues['localidad'], $postValues['codigo_postal'], $postValues['provincia_afip']);
        if (!$localidad){
            $locaArr = array('provincia_afip' =>(int)$postValues['provincia_afip'],
                'nombre' => $postValues['localidad'],
                'codigo_postal' => $postValues['codigo_postal']
            );
            $localidadId = $this->createRegistro($locaArr);
        }else {
            $localidadId = $localidad['id'];
        }
        return $localidadId;
    }
}