<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TablasSimplesAbstract
{

    public function __construct(protected Connection $connection,
                                protected Security $security,
                                protected string $nombreTabla = '')
	{
		date_default_timezone_set('America/Argentina/Cordoba');
	}

	/**
	 * Trae todos los registros de una tabla
	 *
	 * @param bool $soloActivos
	 * @param bool $ordenados
	 * @param bool $alfabeticamente
	 * @return array
	 * @throws Exception
	 */
    public function getAll(bool $soloActivos = false,
                           bool $ordenados = false,
                           bool $alfabeticamente = false): array
    {
        $sql = "select t.* from " . $this->nombreTabla . ' t ';
        if ($soloActivos) {
            $sql .= ' where t.activo = 1 ';
        }
        if ($ordenados) {
            if ($alfabeticamente){
                $sql .= ' order by t.nombre';
            }else{
                $sql .= ' order by t.orden';
            }

        }
        return $this->connection->fetchAllAssociative($sql);
    }

	/**
	 * Método que permite paginar, filtrar y ordenar una sentencia Sql - No tiene filtro por empresa
	 * @param Request $request
	 * @param string $sql
	 * @param array $camposFiltro
	 * @param string $campoActivo
	 * @return array
	 * @throws Exception
	 */
    public function getAllPaginadosOrdenadosFiltrados(Request $request,
                                                      string $sql,
                                                      array $camposFiltro,
                                                      string $campoActivo = ''): array
    {
        $camposRequest = $request->query->all();
        $paginadorXion = new Paginador();
        $paginadorXion->setConnection($this->connection)
            ->setServerSideParams($camposRequest)
            ->setCampoActivo($campoActivo)
            ->setSql($sql)
            ->setContinuaWhere(false)
            ->setCamposAFiltrar($camposFiltro);
        return $paginadorXion->getServerSideRegistros();
    }

    /**
     * Trae un registro de una tabla por su ID
     * @param int $id
     * @return false|array
     * @throws Exception
     */
    public function getById(int $id): bool|array
    {
        $sql = "select t.* from " . $this->nombreTabla . ' t where t.id  = ?';
        return $this->connection->fetchAssociative($sql, [$id]);
    }

    /**
     * Chequea si existe el registro por su ID
     * @param int $id
     * @return array
     * @throws Exception
     */
    public function checkIdExiste(int $id): array {
        $registro = $this->getById($id);
        if (!$registro){
            throw new HttpException(404, 'No se encontró el registro ('.$this->nombreTabla . ')');
        }
        return $registro;
    }

    /**
     * Busca un registro por el campo código
     * @param string $codigo
     * @return false|array
     * @throws Exception
     */
    public function getByCodigo(string $codigo): bool|array
    {
        $sql = "select t.* from " . $this->nombreTabla . ' t where t.codigo  = ?';
        return $this->connection->fetchAssociative($sql, [$codigo]);
    }

    /**
     * @param string $nombre
     * @return false|array
     * @throws Exception
     */
    public function getByNombre(string $nombre): bool|array
    {
        $sql = "select t.* from " . $this->nombreTabla . ' t where t.nombre  = ?';
        return $this->connection->fetchAssociative($sql, [$nombre]);
    }

    /**
     * Actualiza (update) un registro
     *
     * @param array $registro
     * @param int $recordId
     * @return int|string
     * @throws Exception
     */
    public function updateRegistro(array $registro, int $recordId): int|string
    {
        unset($registro['id']);
        return $this->connection->update($this->nombreTabla, $registro, ['id' => $recordId]);
    }

    /**
     * Crea un registro nuevo (insert) en la tabla y devuelve el id nuevo
     *
     * @param array $registroValores
     * @return int
     * @throws Exception
     */
    public function createRegistro(array $registroValores): int
    {
        $this->connection->insert($this->nombreTabla, $registroValores);
        return $this->connection->lastInsertId();
    }

    /**
     * Crea un
     * @param array $registroValores
     * @param int $idUsuario
     * @return int
     * @throws Exception
     */
    public function createRegistroConUsuario(array $registroValores, int $idUsuario): int
    {
        $registroValores['usuario_id'] = $idUsuario;
        $this->connection->insert($this->nombreTabla, $registroValores);
        return $this->connection->lastInsertId();
    }

    /**
     * Borra un registro (delete) de la tabla
     * @param int $recordId
     * @return int|string
     * @throws Exception
     */
    public function deleteRegistro(int $recordId): int|string
    {
        return $this->connection->delete($this->nombreTabla, ['id' => $recordId]);
    }


}
