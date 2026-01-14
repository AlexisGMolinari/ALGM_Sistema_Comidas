<?php

namespace App\Repository\Administrador\Egreso;

use App\Repository\Administrador\Caja\AdminCajaRepository;
use App\Repository\Paginador;
use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EgresoRepository extends TablasSimplesAbstract
{
    private const SQLBROWSE = "SELECT e.*, cat.nombre AS nombreCategoria, u.nombre AS nombreUsuario
    FROM egresos e
    INNER JOIN categoria_egreso_expensas cat ON e.categoria_id = cat.id
    INNER JOIN usuarios u ON u.id = e.usuario_id AND u.empresa_id = e.empresa_id ";


    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'egresos');
    }

    /**
     * @param Request $request
     * @param int $empresa_id
     * @return array
     * @throws Exception
     */
    public function getAllPaginados(Request $request, int $empresa_id): array
    {
        $camposRequest = $request->query->all();

        $sql = self::SQLBROWSE . " WHERE e.empresa_id = $empresa_id";

        $arrParam = [ 'e.id','e.monto', 'cat.nombre', 'u.nombre', 'e.descripcion', 'e.fecha'];

        $paginador = new Paginador();
        $paginador->setConnection($this->connection)
            ->setServerSideParams($camposRequest)
            ->setSql($sql)
            ->setContinuaWhere(true)
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
     * @param int $empresa_id
     * @return void
     * @throws Exception
     */
    public function createEgreso(array $postValues, int $empresa_id): void
    {
        $caja = (new AdminCajaRepository($this->connection, $this->security))->getCajaActual($empresa_id);
        if (!$caja) {
            throw new HttpException(400, "Se necesita abrir una caja para poder realizar un egreso.");
        }
        $postValues['caja_id'] = (int)$caja['id'];
        $postValues['empresa_id'] = $empresa_id;

        $this->createRegistro($postValues);
    }

}