<?php

namespace App\Repository\Configuracion;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Esta Clase sirve para las categorías, subcategorías y accesos
 */
class AccesoRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'acceso_acceso', false);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getAllAccesosCompleto():array
    {
        $sql = 'select ac.id, ac.nombre from acceso_categoria ac order by ac.orden';
        $categorias = $this->connection->fetchAllAssociative($sql);
        $sql = 'select acc.* from acceso_acceso acc order by acc.id';
        $accesos = $this->connection->fetchAllAssociative($sql);
        return [
            'categorias' => $categorias,
            'accesos' => $accesos
        ];
    }

    /**
     * Borra todos los accesos que tenga un usuario
     * @param int $usuarioID
     * @throws Exception
     */
    public function deleteAccesosUsuario(int $usuarioID): void
    {
        $this->connection->delete('usuario_accesos', ['usuario_id' => $usuarioID]);
    }
}