<?php

namespace App\Repository\Empresa\Soporte;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class SoporteRepository
{
	private Connection $connection;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Devuelve todos los videos
	 * @return array
	 * @throws Exception
	 */
	public function getAllVideos(): array
	{
		$sql = "SELECT v.* from ayuda_videos v order by v.orden";
		return $this->connection->fetchAllAssociative($sql);
	}
}
