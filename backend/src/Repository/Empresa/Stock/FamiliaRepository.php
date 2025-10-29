<?php

namespace App\Repository\Empresa\Stock;

use App\Repository\Paginador;
use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

class FamiliaRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'familia', true);
    }

    /**
     * @throws Exception
     */
    public function getAllPaginados(Request $request): array
    {
        $camposRequest = $request->query->all();

        $sql = "SELECT f.id, f.nombre, f.activo FROM familia f WHERE f.empresa_id = $this->empresaId";

        $arrParam = [ 'f.nombre'];

        $paginador = new Paginador();
        $paginador->setConnection($this->connection)
            ->setServerSideParams($camposRequest)
            ->setCampoActivo('f.activo')
            ->setSql($sql)
            ->setContinuaWhere(true)
            ->setCamposAFiltrar($arrParam);  //pasar campos con alias de tabla

        return $paginador->getServerSideRegistros();
    }

	/**
	 * @param int $fliaId
	 * @return array
	 * @throws Exception
	 */
	public function getSubFliasByFliaId(int $fliaId):array
	{
		$sql = "SELECT s.* 
				from subfamilia s 
           		where s.familia_id = ? and s.activo = 1
           		order by s.nombre";
		return $this->connection->fetchAllAssociative($sql, [$fliaId]);
	}

}
