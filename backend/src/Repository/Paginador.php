<?php
namespace App\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class Paginador
{
	/**
	 * @var array campos del request
	 */
	private array $serverSideParams;

	/**
	 * @var Connection
	 */
	private Connection $connection;

	/**
	 * @var array nombre de los campos que se deberían filtrar
	 */
	private array $camposAFiltrar;

	/**
	 * @var bool si la sentencia tiene un where setear en true para continuar con las condiciones
	 */
	private bool $continuaWhere;

	/**
	 * @var string sentencia original de SQL
	 */
	private string $sql;

    /**
     * @var string nombre del campo (con alias) que contiene si es activo si o no el registro (puede ser activo o estado)
     */
    private string $campoActivo = '';

	/**
	 * @var string comandos del group by
	 */
	private string $groupBy = '';

	/**
	 * Genera la sentencia SQL, pagina, ordena y filtra
	 * @return array
	 * @throws Exception
	 */
	public function  getServerSideRegistros(): array
	{
		$binding = [];
		$query = $this->agregoCalFoundRows($this->sql)
            . $this->getSoloActivos()
            . $this->filtro($binding);

		$query2 = $query
			. $this->agrupo()
			. $this->order()
			. $this->limit();
		// file_put_contents('../var/log/sql.txt', $query2);
		$registros = $this->connection->fetchAllAssociative($query2, $binding);

		// calculo la cantidad de registros que devuelve la consulta
		// $totalRegistros = $this->connection->fetchAssociative($this->procesoCount($query));

        // calculo la cantidad de registros que devuelve la consulta
        $foundRows = $this->connection->executeQuery('SELECT FOUND_ROWS() AS cantidadRegistros')->fetchAssociative();
        return [
            'registros' => $registros,
            'totalRegistros' => (int)$foundRows['cantidadRegistros']
        ];
	}

    /**
     * Agrega la función SQL_CALC_FOUND_ROWS, luego de la primer sentencia SELECT
     * @param $sql
     * @return string
     */
    private function agregoCalFoundRows($sql): string
    {
        $newSql = '';
        $pos = strpos(strtoupper($sql), 'SELECT');
        if ($pos !== false) {
            $newSql = substr_replace($sql,'SELECT SQL_CALC_FOUND_ROWS ',$pos,strlen('SELECT'));
        }
        return $newSql;
    }

	/**
	 * @return string
	 */
	private function agrupo(): string
	{
		$groupBy = ' ';
		if (strlen($this->groupBy) > 0) {
			$groupBy = $this->groupBy . ' ';
		}
		return $groupBy;
	}


	/**
	 * @param $binding
	 * @return string
	 */
	private function filtro(&$binding): string
	{
		$filtro = '';
		if (isset($this->serverSideParams['filter']) && strlen($this->serverSideParams['filter']) > 2) {
			if ($this->continuaWhere) {
				$filtro .= ' AND (';
			}else{
				$filtro .= ' where ';
			}

			foreach ($this->camposAFiltrar as $campo) {
				$filtro .= $campo . ' like ? or ';
				$binding [] = "%" . $this->serverSideParams['filter'] . "%";
			}

			$filtro = substr($filtro, 0, -4);
			if ($this->continuaWhere) {
				$filtro .= ' ) ';
			}
		}

		return $filtro;
	}

	/**
	 * arma el string del limit
	 * @return string
	 */
	private function limit (): string
	{
		$limit = '';
		if (isset($this->serverSideParams['pageNumber']) && isset($this->serverSideParams['pageSize'])) {
			$paginaActual = (int)$this->serverSideParams['pageNumber'];
			$cantRegisXpagina = (int)$this->serverSideParams['pageSize'];
			if ($paginaActual !== 0) {
				$paginaActual = ($paginaActual * $cantRegisXpagina);
			}
			$limit = " LIMIT $paginaActual, $cantRegisXpagina";
		}

		return $limit;
	}

	/**
	 * @return string
	 */
	private function order (): string
	{
		$order = '';
		if (isset($this->serverSideParams['orderColumn'])) {
			$orderDir = 'asc';
			if (isset($this->serverSideParams['orderDir'])) {
				$orderDir = $this->serverSideParams['orderDir'];
			}
			$order = " Order by " . trim($this->serverSideParams['orderColumn']) . " $orderDir";
		}
		return $order;
	}


    /**
     * Función que filtra la consulta por campos activo o estado
     * @return string
     */
    private function getSoloActivos(): string
    {
        $filtro = '';
        if (isset($this->serverSideParams['soloActivos']) && $this->campoActivo !== '') {
            $valor = filter_var($this->serverSideParams['soloActivos'], FILTER_VALIDATE_BOOLEAN);
            if ($valor) {
                if ($this->continuaWhere) {
                    $filtro .= ' AND ' . $this->campoActivo . ' =  1 ';
                }else{
                    $filtro .= ' where ' . $this->campoActivo . ' =  1 ';
                }
            }
        }
        return $filtro;
    }

	/**
	 * @param array $serverSideParams
	 * @return Paginador
	 */
	public function setServerSideParams(array $serverSideParams): Paginador
	{
		$this->serverSideParams = $serverSideParams;
		return $this;
	}

	/**
	 * @param Connection $connection
	 * @return Paginador
	 */
	public function setConnection(Connection $connection): Paginador
	{
		$this->connection = $connection;
		return $this;
	}


	/**
	 * Array con los campos a filtrar - Pasar los campos con alias de tabla por ejemplo: p.nombre
	 * @param array $camposAFiltrar
	 * @return Paginador
	 */
	public function setCamposAFiltrar(array $camposAFiltrar): Paginador
	{
		$this->camposAFiltrar = $camposAFiltrar;
		return $this;
	}

	/**
	 * @param bool $continuaWhere
	 * @return Paginador
	 */
	public function setContinuaWhere(bool $continuaWhere): Paginador
	{
		$this->continuaWhere = $continuaWhere;
		return $this;
	}

	/**
	 * @param string $sql
	 * @return Paginador
	 */
	public function setSql(string $sql): Paginador
	{
		$this->sql = $sql;
		return $this;
	}

    /**
     * @param string $campoActivo
     * @return Paginador
     */
    public function setCampoActivo(string $campoActivo): Paginador
    {
        $this->campoActivo = $campoActivo;
        return $this;
    }

	/**
	 * @param string $groupBy
	 * @return $this
	 */
	public function setGroupBy(string $groupBy): Paginador
	{
		$this->groupBy = $groupBy;
		return $this;
	}
}
