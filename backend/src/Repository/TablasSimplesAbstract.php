<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TablasSimplesAbstract
{
    protected int $empresaId = 0;
    public function __construct(protected Connection $connection,
                                protected Security $security,
                                protected string $nombreTabla = '',
                                protected bool $tieneEmpresa = false)
	{
		date_default_timezone_set('America/Argentina/Cordoba');
        if ($this->tieneEmpresa)
            $this->empresaId = ($this->security->getUser())? $this->security->getUser()->getEmpresa(): 0;
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
        $sql = "select * from " . $this->nombreTabla;
        $tieneWhere = false;
        if ($this->tieneEmpresa) {
            $tieneWhere = true;
            $sql .= ' where empresa_id = ' . $this->empresaId;
        }
        if ($soloActivos) {
            $sql .= ($tieneWhere ? ' and ' : ' where ') . ' activo = 1 ';
        }
        if ($ordenados) {
            if ($alfabeticamente){
                $sql .= ' order by nombre';
            }else{
                $sql .= ' order by orden';
            }

        }
        $registros = $this->connection->fetchAllAssociative($sql);

        //quito el empresa_id del array devuelto
        if ($this->tieneEmpresa)
            foreach ($registros as &$registro)
                unset($registro['empresa_id']);

        return $registros;
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
                                                      string $campoActivo = '',
                                                      bool $continuaWhere = false): array
    {
        $camposRequest = $request->query->all();
        $paginadorXion = new Paginador();
        $paginadorXion->setConnection($this->connection)
            ->setServerSideParams($camposRequest)
            ->setCampoActivo($campoActivo)
            ->setSql($sql)
            ->setContinuaWhere($continuaWhere)
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

        $sql = "select * from " . $this->nombreTabla . ' where id  = ?';
        if ($this->tieneEmpresa){
            $sql .= ' and empresa_id = ' . $this->empresaId;
        }
        $registro = $this->connection->fetchAssociative($sql, [$id]);
        if (isset($registro['empresa_id']))
            unset($registro['empresa_id']);
        return $registro;
    }

    /**
     * Chequea si existe el registro por su ID
     * @param int $id
     * @return array
     * @throws Exception
     */
    public function checkIdExiste(int $id): array{
        $registro = $this->getById($id);
        if (!$registro){
            throw new HttpException(400, 'No se encontró el registro ('.$this->nombreTabla . ')');
        }
        return $registro;
    }

    /**
     * Busca un registro por el campo código
     * @param string $codigo
     * @param bool $conControl
     * @return false|array
     * @throws Exception
     */
    public function getByCodigo(string $codigo, bool $conControl = false): bool|array
    {
        $sql = "select * from " . $this->nombreTabla . ' where codigo  = ?';
        if ($this->tieneEmpresa){
            $sql .= ' and empresa_id = ' . $this->empresaId;
        }
        $registro = $this->connection->fetchAssociative($sql, [$codigo]);

        if (!$registro && $conControl){
            throw new HttpException(404, 'No se encontró el registro ('.$this->nombreTabla . ')');
        }
        if (isset($registro['empresa_id']))
            unset($registro['empresa_id']);
        return $registro;

    }

    /**
     * @param string $nombre
     * @return false|array
     * @throws Exception
     */
    public function getByNombre(string $nombre): bool|array
    {
        $sql = "select * from " . $this->nombreTabla . ' where nombre  = ?';
        if ($this->tieneEmpresa){
            $sql .= ' and empresa_id = ' . $this->security->getUser()->getEmpresa();
        }
        $registro = $this->connection->fetchAssociative($sql, [$nombre]);
        if (isset($registro['empresa_id']))
            unset($registro['empresa_id']);
        return $registro;
    }

    /**
     * Actualiza (update) un registro
     *
     * @param array $registro
     * @param int $recordId
     * @return int|string
     * @throws Exception
     */
    public function updateRegistro(array $registro, int $recordId): void
    {
        if ($this->tieneEmpresa)
            $this->connection->update($this->nombreTabla, $registro, ['id' => $recordId, 'empresa_id' => $this->security->getUser()->getEmpresa()]);
        else
            $this->connection->update($this->nombreTabla, $registro, ['id' => $recordId]);
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
        if ($this->tieneEmpresa) {
            $registroValores['empresa_id'] = $this->security->getUser()->getEmpresa();
            $this->connection->insert($this->nombreTabla, $registroValores);
        }
        else
            $this->connection->insert($this->nombreTabla, $registroValores);
        return $this->connection->lastInsertId();
    }

    /**
     * @param array $registroValores
     * @return int
     * @throws Exception
     */
    public function create(array $registroValores): int
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
    public function deleteRegistro(int $recordId): void
    {
        if ($this->tieneEmpresa)
            $this->connection->delete($this->nombreTabla, ['id' => $recordId, 'empresa_id' => $this->security->getUser()->getEmpresa() ]);
        else
            $this->connection->delete($this->nombreTabla, ['id' => $recordId]);
    }


}
