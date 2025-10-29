<?php

namespace App\Repository\Administrador\Egreso;

use App\Repository\Administrador\Caja\AdminCajaRepository;
use App\Repository\Paginador;
use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

class EgresoRepository extends TablasSimplesAbstract
{
    private const SQLBROWSE = "SELECT e.*, cat.nombre AS nombreCategoria, u.nombre AS nombreUsuario FROM egresos e
                                INNER JOIN categoria_egreso_expensas cat ON e.categoria_id = cat.id
                                INNER JOIN usuarios u ON e.usuario_id = u.id";

    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'egresos');
    }

    /**
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function getAllPaginados(Request $request): array
    {
        $camposRequest = $request->query->all();

        $sql = self::SQLBROWSE;

        $arrParam = [ 'e.id','e.monto', 'cat.nombre', 'u.nombre', 'e.descripcion', 'e.fecha'];

        $paginador = new Paginador();
        $paginador->setConnection($this->connection)
            ->setServerSideParams($camposRequest)
            ->setSql($sql)
            ->setContinuaWhere(false)
            ->setCamposAFiltrar($arrParam);  //pasar campos con alias de tabla

        return $paginador->getServerSideRegistros();
    }

    /**
     * @param int $idCategoria
     * @return array
     * @throws Exception
     */
    public function getByCategoria(int $idCategoria): array
    {
        $sql = self::SQLBROWSE;
        $where = " WHERE cat.id = ?";
        $sql .= $where;
        return $this->connection->fetchAllAssociative($sql, [$idCategoria]);
    }

    /**
     * Inserta el egreso de la Caja
     * @param array $postValues
     * @return void
     * @throws Exception
     */
    public function createEgreso(array $postValues): void
    {
        $caja = (new AdminCajaRepository($this->connection, $this->security))->getCajaActual();
        $postValues['caja_id'] = (int)$caja['id'];

        $this->createRegistro($postValues);
    }

}