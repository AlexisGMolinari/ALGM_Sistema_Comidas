<?php

namespace App\Repository\Empresa;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class EmpresaGraficosTempRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'graficos_temp', true);
    }

    /**
     * Muestra el gráfico si la empresa cuenta con los accesos necesarios
     * Caso contrario, devuelve los campos vacíos
     * @param int $empresaId
     * @return array
     * @throws Exception
     */
    public function getGraficoByEmpresa(int $empresaId): array
    {
        $empresaConAcceso = (new EmpresaRepository($this->connection, $this->security))->getEmpresasConAcceso();
        $idPermitods = array_column($empresaConAcceso, 'id');

        if(!in_array($empresaId, $idPermitods, false)){
            return [];
        }

        $sql = "SELECT mes_1, mes_2, mes_3, mes_4, mes_5, mes_6 
                FROM graficos_temp 
                WHERE empresa_id = ?";
        $grafico = $this->connection->fetchAssociative($sql, [$empresaId]);
        return $grafico ?: [];
    }

}