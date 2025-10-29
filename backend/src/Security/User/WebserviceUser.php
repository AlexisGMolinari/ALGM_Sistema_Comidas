<?php


namespace App\Security\User;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

class WebserviceUser implements UserInterface, EquatableInterface, PasswordAuthenticatedUserInterface
{

    public function __construct(private readonly string $username,
								private readonly string $password,
								private readonly int    $id,
								private readonly array  $roles,
                                private readonly int    $empresa_id)
    {
    }

	/**
	 * @return int
	 */
    public function getId(): int
	{
        return $this->id;
    }

	/**
	 * @return array|string[]
	 */
    public function getRoles(): array
    {
        return $this->roles;
    }

	/**
	 * @return string
	 */
    public function getPassword(): string
    {
        return $this->password;
    }


	/**
	 * @return string
	 */
    public function getUsername(): string
    {
        return $this->username;
    }

	/**
	 * @return string
	 */
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function eraseCredentials():void
    {
    }

    public function getEmpresa(): int
    {
        return $this->empresa_id;
    }

	/**
	 * @param UserInterface $user
	 * @return bool
	 */
    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof WebserviceUser) {
            return false;
        }
        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }
        return true;
    }

}
