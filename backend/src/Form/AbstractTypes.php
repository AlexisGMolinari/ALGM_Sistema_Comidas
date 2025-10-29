<?php
namespace App\Form;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbstractTypes
{

    public function __construct(protected ValidatorInterface $validation,
                                protected TranslatorInterface $translator,
                                protected Connection $connection,
                                protected Security $security
    )
    {
    }

    /**
     * Arma el texto del mensaje de error para pasarlo al traductor
     * @param ConstraintViolation $constraintViolation
     * @return string
     */
    public function traduccionError(ConstraintViolation $constraintViolation): string {
        $arrParametrosError = $constraintViolation->getParameters();
        $arrParametros = [];
        if (array_key_exists("{{ value }}", $arrParametrosError)) {
            $arrParametros['value'] = $arrParametrosError["{{ value }}"];
        }
        if (array_key_exists("{{ limit }}", $arrParametrosError)) {
            $arrParametros['limit'] = $arrParametrosError["{{ limit }}"];
        }
        if (array_key_exists("{{ min }}", $arrParametrosError)) {
            $arrParametros['min'] = $arrParametrosError["{{ min }}"];
        }
        if (array_key_exists("{{ max }}", $arrParametrosError)) {
            $arrParametros['max'] = $arrParametrosError["{{ max }}"];
        }
        if (array_key_exists("{{ type }}", $arrParametrosError)) {
            $arrParametros['type'] = $arrParametrosError["{{ type }}"];
        }

        // var_dump($constraintViolation->getMessageTemplate()); // <- para ver si un mensaje no se tradujo bien
        // var_dump($arrParametros); // <- para ver si un mensaje no se tradujo bien
        $mensajes = explode('|', $constraintViolation->getMessageTemplate());
        return 'Error en el campo ' . $constraintViolation->getPropertyPath()
            . ": <br>"
            . $this->translator->trans($mensajes[0], $arrParametros);
    }

    /**
     * Función que controla la existencia de un registro por Foreign Key
     * @throws Exception
     */
    public function controlFK(string $nombreRegistroTabla, ?int $fk_ID, bool $requerido, TablasSimplesAbstract $repository): bool|array
    {
        $registroExiste = false;
        if ((isset($fk_ID) && $fk_ID != null) || $requerido) {
            $registroExiste = $repository->getById($fk_ID);
            if (!$registroExiste)
                throw new HttpException(404, 'No se encuentra el registro de ' . $nombreRegistroTabla . ' ingresado.');
        }
        return $registroExiste;
    }


}
