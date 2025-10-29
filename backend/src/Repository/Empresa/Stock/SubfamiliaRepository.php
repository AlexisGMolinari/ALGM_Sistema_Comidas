<?php

namespace App\Repository\Empresa\Stock;

use App\Repository\Paginador;
use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

class SubfamiliaRepository extends TablasSimplesAbstract
{
    const SQL = "SELECT sf.*, f.nombre as familia from subfamilia sf 
            inner join familia f on sf.familia_id = f.id";

    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'subfamilia');
        $this->empresaId = $this->security->getUser()->getEmpresa();
    }

    /**
     * @throws Exception
     */
    public function getAllPaginados(Request $request): array
    {
        $camposRequest = $request->query->all();

        $sql = self::SQL . " where f.empresa_id = $this->empresaId";

        $arrParam = [ 'sf.nombre','f.nombre'];

        $paginador = new Paginador();
        $paginador->setConnection($this->connection)
            ->setServerSideParams($camposRequest)
            ->setCampoActivo('sf.activo')
            ->setSql($sql)
            ->setContinuaWhere(true)
            ->setCamposAFiltrar($arrParam);  //pasar campos con alias de tabla

        return $paginador->getServerSideRegistros();
    }


	/**
	 * Obtiene las subfamilias relacionadas con el id de una de ellas
	 * @param int $subFliaId
	 * @return array
	 * @throws Exception
	 */
	public function getASubFliasRelacionadas(int $subFliaId): array
	{
		// traigo la subflia para obtener el id de la Flia
		$subfamilia = $this->getById($subFliaId);
		return (new FamiliaRepository($this->connection, $this->security))->getSubFliasByFliaId($subfamilia['familia_id']);
	}
}
