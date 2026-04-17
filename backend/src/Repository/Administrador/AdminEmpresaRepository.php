<?php

namespace App\Repository\Administrador;

use App\Repository\Administrador\Configuracion\ConfiguracionRepository;
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
		$sql = "SELECT em.id, em.nombre, em.url_sitioweb, ua.nombre AS nombre_usuario, em.activa
                FROM empresa em
                LEFT JOIN (SELECT u.empresa_id,
                        MIN(u.id) AS usuario_id,
                        SUBSTRING_INDEX(GROUP_CONCAT(u.nombre ORDER BY u.id ASC),',', 1) AS nombre
                    FROM usuarios u
                    WHERE u.roles = 'ROLE_ADMIN' OR u.roles = 'ROLE_ADMIN, ROLE_SUPERADMIN'
                    GROUP BY u.empresa_id
                ) ua ON ua.empresa_id = em.id";
		$arrParam = [ 'em.id', 'em.nombre', 'us.nombre', 'em.url_sitioweb'];

        $paginador = new Paginador();
        $paginador->setConnection($this->connection)
            ->setServerSideParams($camposRequest)
            ->setSql($sql)
            ->setContinuaWhere(true)
            ->setCamposAFiltrar($arrParam);

        return $paginador->getServerSideRegistros();
	}

    /**
     * Devuelve las empresas que no tienen asignado un usuario
     * @return array
     * @throws Exception
     */
    public function getEmpresasSinUsuario(): array
    {
        $sql = 'SELECT e.* FROM empresa e WHERE NOT EXISTS (SELECT 1 FROM usuarios u WHERE u.empresa_id = e.id)';
        return $this->connection->fetchAllAssociative($sql);
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

    /**
     * @param int $id
     * @return void
     * @throws Exception
     */
    public function eliminarEmpresa(int $id): void
    {
        $sql = 'SELECT COUNT(1) FROM usuarios WHERE empresa_id = :empresa_id';
        $cantidadUsuarios = (int) $this->connection->fetchOne($sql, [
            'empresa_id' => $id
        ]);
        if ($cantidadUsuarios > 0) {
            throw new HttpException(400, "La empresa no puede ser eliminada porque tiene usuarios asociados.");
        }
        $this->deleteRegistro($id);
    }

    /**
     * @param array $postValues
     * @return void
     * @throws Exception
     */
    public function creoEmpresa(array $postValues): void
    {
        $this->connection->beginTransaction();
        $lastId = $this->createRegistro($postValues);
        $arr = [
            'empresa_id' => $lastId,
            'imprime_ticket' => 0,
            'formato_ticket' => null
        ];
        (new ConfiguracionRepository($this->connection, $this->security))->create($arr);
        $this->connection->commit();
    }
}
