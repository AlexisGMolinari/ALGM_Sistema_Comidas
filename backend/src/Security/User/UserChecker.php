<?php

namespace App\Security\User;

use App\Repository\Configuracion\UsuarioRepository;
use App\Security\User\WebserviceUser as AppUser;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{

    private Connection $conn;
    private Security $security;

    public function __construct(Connection $conn, Security $security)
    {
        $this->conn = $conn;
        $this->security = $security;
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }
    }

    /**
     * @throws Exception
     */
    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        // verifico que la cuenta no esté suspendida (activo == 1)
        if (!$this->buscoUsuarioSuspendido($user->getId())) {
            throw new CustomUserMessageAuthenticationException('Cuenta suspendida; comuníquese con el vendedor');

        }
    }

    /**
     * función que busca el estado del usuario
     * @param $id integer
     * @return bool
     * @throws Exception
     */
    public function buscoUsuarioSuspendido(int $id): bool
    {
        $usuario    = (new UsuarioRepository($this->conn, $this->security))->getById($id);
        $estado     = (int)$usuario['activo'];
        $devo       = true;
        if ($estado === 0){
            $devo = false;
        }
        return $devo;
    }
}