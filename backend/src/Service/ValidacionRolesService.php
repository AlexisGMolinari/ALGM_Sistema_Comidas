<?php

namespace App\Service;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ValidacionRolesService
{
    public static function ValidarContador(Security $security): void
    {
        $roles = $security->getUser()->getRoles();
        if ($roles[0] !== 'ROLE_CONTA')
            throw new HttpException(400, 'El usuario no tiene rol de contador. No se puede ejecutar esta operación.');
    }
}