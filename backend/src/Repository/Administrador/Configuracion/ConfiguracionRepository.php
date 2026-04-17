<?php

namespace App\Repository\Administrador\Configuracion;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class ConfiguracionRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'configuracion', true);
    }

    /**
     * Devuelve la configuración por empresa
     *
     * @param int $empresa_id
     * @return array
     * @throws Exception
     */
    public function getConfiguracion(int $empresa_id): array
    {
         $sql = "SELECT * FROM " . $this->nombreTabla . " WHERE empresa_id = :empresa_id";
         return $this->connection->fetchAssociative($sql, ['empresa_id' => $empresa_id]);
    }

    /**
     * @param array $postValues
     * @param int $empresa_id
     * @return void
     * @throws Exception
     */
    public function guardoConfig(array $postValues, int $empresa_id): void
    {
        $arr = [
            'empresa_id' => $empresa_id,
            'imprime_ticket' => $postValues['imprime_ticket'],
            'formato_ticket' => $postValues['formato_ticket'],
        ];
        $this->connection->update($this->nombreTabla, $arr, ['empresa_id' => $empresa_id]);
    }
}