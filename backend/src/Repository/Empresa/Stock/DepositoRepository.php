<?php

namespace App\Repository\Empresa\Stock;

use App\Repository\Paginador;
use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

class DepositoRepository extends TablasSimplesAbstract
{
	public function __construct(Connection $connection, Security $security)
	{
		parent::__construct($connection, $security, 'deposito', true);
	}

	/**
	 * @param Request $request
	 * @return array
	 * @throws Exception
	 */
	public function getAllPaginados(Request $request): array
	{
		$camposRequest = $request->query->all();

		$sql = "SELECT f.id, f.nombre, f.activo FROM deposito f WHERE f.empresa_id = $this->empresaId";

		$arrParam = [ 'f.nombre'];

		$paginador = new Paginador();
		$paginador->setConnection($this->connection)
			->setServerSideParams($camposRequest)
			->setCampoActivo('f.activo')
			->setSql($sql)
			->setContinuaWhere(true)
			->setCamposAFiltrar($arrParam);

		return $paginador->getServerSideRegistros();
	}

	/**
	 * Devuelve todos los depósitos de una empresa (usado en el módulo contador)
	 * @param int $empresaId
	 * @return array
	 * @throws Exception
	 */
	public function getAllPorEmpresa(int $empresaId): array
	{
		$sql = "SELECT f.id, f.nombre, f.activo FROM deposito f WHERE f.empresa_id = ? and f.activo = ?";
		return $this->connection->fetchAllAssociative($sql, [$empresaId, 1]);
	}
}
