<?php

namespace App\Repository\Administrador;

use App\Repository\Configuracion\UsuarioRepository;
use App\Repository\Empresa\Clientes\ClienteRepository;
use App\Repository\Paginador;
use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminEmpresaRepository extends TablasSimplesAbstract
{

	public function __construct(Connection $connection, Security $security)
	{
		parent::__construct($connection, $security, 'empresa');
	}

	/**
	 * @param Request $request
	 * @return array
	 * @throws Exception
	 */
	public function getAllPaginados(Request $request): array {
        $camposRequest = $request->query->all();
		$sql = "SELECT em.id, em.nombre_fantasia, us.nombre, em.controla_stock, us.activo
            FROM empresa em
            inner join usuarios us on em.id = us.empresa_id
            where us.roles = 'ROLE_USER'";
		$arrParam = [ 'em.id', 'em.nombre_fantasia', 'us.nombre'];

        $paginador = new Paginador();
        $paginador->setConnection($this->connection)
            ->setServerSideParams($camposRequest)
            ->setSql($sql)
            ->setContinuaWhere(true)
            ->setCamposAFiltrar($arrParam)
            ->setGroupBy(' GROUP BY em.id');

        return $paginador->getServerSideRegistros();
	}

    /**
     * Actualiza el Usuario permitiéndole ingresar o no, a la empresa
     * @param array $putValues
     * @param int $idEmpresa
     * @return void
     * @throws Exception
     */
	public function updateEmpresa(array $putValues, int $idEmpresa): void {
		$usuarioRepository = new UsuarioRepository($this->connection, $this->security);
		$usuario = $usuarioRepository->getByEmpresa($idEmpresa);

		$this->connection->beginTransaction();

		$usuarioRepository->updateRegistro(['activo' => $putValues['activo']], $usuario['id']);

		$this->connection->commit();
	}

    /**
     * Busca por cliente Id, sin validar la empresa; solo en la tabla cliente
     * @param int $idCliente
     * @return array
     * @throws Exception
     */
    public function checkClienteExiste(int $idCliente): array {
        $sql = "select cl.* 
                from cliente cl 
                where cl.id = ?";

        $registro = $this->connection->fetchAssociative($sql, [$idCliente]);
        if (!$registro){
            throw new HttpException(400, 'No se encontró el cliente (ID: ' . $idCliente . ')');
        }
        return $registro;
    }
}
