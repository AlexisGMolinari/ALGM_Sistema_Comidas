<?php

namespace App\Repository\Configuracion;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

class UsuarioRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security,  'usuarios');
    }


    /**
     * Devuelve el registro SIN la clave
     * @param int $id
     * @return array|bool
     * @throws Exception
     */
    public function getByIdSinPass(int $id): array|bool
    {
		$sql = 'select u.id, u.nombre, u.email, u.roles, u.activo '
			. ' from ' . $this->nombreTabla . ' u'
			. ' where u.id  = ?';
		return $this->connection->fetchAssociative($sql, [$id]);
    }

    /**
     * @param string $email
     * @param null $estado
     * @return array|bool
     * @throws Exception
     */
    public function getByEmail(string $email, $estado = null): bool|array
    {
        if($estado) {
            $sql = 'select u.* ' . ' from ' . $this->nombreTabla . ' u' . ' where u.email  = ? and u.activo = ?';
            $param = array($email, (int)$estado);
        } else {
            $sql = 'select u.* ' . ' from ' . $this->nombreTabla . ' u' . ' where u.email  = ?';
            $param = array($email);
        }
        return $this->connection->fetchAssociative($sql, $param);
    }

	/**
	 * @param int $id
	 * @return array|bool
	 * @throws Exception
	 */
	public function getUsuarioActual(int $id): array|bool
	{
		$sql = 'SELECT  us.nombre, us.email, us.roles FROM usuarios us 
				WHERE us.id = ?';
		return $this->connection->fetchAssociative($sql, [$id]);
	}

    /**
     * Devuelve todos los usuarios de una empresa
     * @param Request $request
     * @param int $idEmp
     * @return array
     * @throws Exception
     */
    public function getAllByEmpresa(Request $request, int $idEmp): array {
        $sql = "select us.id, us.activo, us.nombre, us.email, us.empresa_id, us.roles 
                from usuarios us where us.empresa_id = $idEmp ";
        $arrParam = [ 'us.nombre', 'us.email', 'us.empresa_id'];
        return $this->getAllPaginadosOrdenadosFiltrados($request, $sql, $arrParam, 'us.activo', true);
    }

    /**
     * Busca el usuario por el id de empresa - usado para traer el nombre de la empresa
     * @throws Exception
     */
    public function getByEmpresa(int $idEmp): array|bool
    {
        $sql = "select * from usuarios us where us.empresa_id = ?";
        return $this->connection->fetchAssociative($sql, [$idEmp]);
    }

	/**
	 * Guarda la nueva clave
	 * @param array $postValues
	 * @param int $usuarioId
	 * @return void
	 * @throws Exception
	 */
	public function savePasswords(array $postValues, int $usuarioId): void
	{
		$clave = password_hash( $postValues['segundaClave'], PASSWORD_BCRYPT);
		$this->updateRegistro(['password' => $clave], $usuarioId);
	}

    /**
     * Trae todos los accesos del usuario por su ID
     * @param int $idUsuario
     * @return array[]
     * @throws Exception
     */
    public function getUsuarioAccesos(int $idUsuario): array
    {
        $sql = 'select ac.* '
            . 'from usuario_accesos ua inner join acceso_acceso ac on ua.acceso_id = ac.id '
            . 'where ua.usuario_id = ? '
            . 'order by ac.id';
        return $this->connection->fetchAllAssociative($sql, [$idUsuario]);
    }

    /**
     * Guarda el nuevo usuario, los accesos y devuelve el id del nuevo usuario
     * @param array $postUsuario
     * @return int
     * @throws Exception
     */
    public function createUsuarioCompleto(array $postUsuario): int
    {
        $usuario = $postUsuario['usuario'];
        $usuario['password'] = password_hash( $usuario['password'], PASSWORD_BCRYPT);
        $this->connection->beginTransaction();
        $usuarioId = $this->createRegistro($usuario);
        $this->connection->commit();
        return $usuarioId;
    }

    /**
     * Actualiza los datos del usuario y sus accesos
     * @param array $postUsuario
     * @param int $usuarioId
     * @throws Exception
     */
    public function updateUsuario(array $postUsuario, int $usuarioId): void
    {
        $usuario = $postUsuario['usuario'];
        if (isset($usuario['password'])){
            $usuario['password'] = password_hash( $usuario['password'], PASSWORD_BCRYPT);
        }
        $this->connection->beginTransaction();
        $this->updateRegistro($usuario, $usuarioId);
        $this->connection->commit();
    }

    /**
     * @param array $accesos
     * @param int $usuarioId
     * @return void
     * @throws Exception
     */
    public function agregoAccesos(array $accesos, int $usuarioId): void
    {
        if ($usuarioId !== 0){
            (new AccesoRepository($this->connection, $this->security))->deleteAccesosUsuario($usuarioId);
        }
        foreach ($accesos as $acceso) {
            $this->connection->insert('usuario_accesos', ['usuario_id' => $usuarioId, 'acceso_id' => (int)$acceso]);
        }
    }
}
