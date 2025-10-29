<?php

namespace App\Security\User;

use App\Repository\Configuracion\UsuarioRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;


class WebserviceUserProvider implements UserProviderInterface
{

    public function __construct(private readonly Connection $conn, private Security $security)
    {
    }

    /**
     * @param $username
     * @return WebserviceUser|UserInterface
     * @throws Exception
     */
    public function loadUserByUsername($username): UserInterface|WebserviceUser
    {
        return $this->fetchUser($username);
    }

    /**
     * @param string $identifier
     * @return UserInterface
     * @throws Exception
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->fetchUser($identifier);
    }

    /**
     * @param UserInterface $user
     * @return WebserviceUser|UserInterface
     * @throws Exception
     */
    public function refreshUser(UserInterface $user): UserInterface|WebserviceUser
    {
        if (!$user instanceof WebserviceUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }
        $username = $user->getUsername();
        return $this->fetchUser($username);
    }

    /**
     * @param $class
     * @return bool
     */
    public function supportsClass($class): bool
    {
        return WebserviceUser::class === $class;
    }

    /**
     * @param $username
     * @return WebserviceUser
     * @throws Exception
     */
    private function fetchUser($username): WebserviceUser
    {
        $usuario = (new UsuarioRepository($this->conn, $this->security))->getByEmail($username, null);
        if ($usuario) {
            $username = $usuario['email'];
            $password = $usuario['password'];
            $userId = (int)$usuario['id'];
            $empresaId = (int)$usuario['empresa_id'];
            $roles = array($usuario['roles']);
            return new WebserviceUser($username, $password, $userId, $roles, $empresaId);
        }

        throw new UserNotFoundException(
            sprintf('Usuario "%s" Inexistente!.', $username)
        );
    }
}
