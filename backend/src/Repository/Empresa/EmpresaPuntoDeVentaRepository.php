<?php

namespace App\Repository\Empresa;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class EmpresaPuntoDeVentaRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'punto_venta', true);
    }


    /**
     * Función que trae todos los puntos de ventas del usuario (empresa) FE o no
     * @throws Exception
     */
    public function getPuntosDeLaEmpresa(bool $soloFE): array
    {
        $sqlSoloFE = '';
        if ($soloFE) {
            $sqlSoloFE .= ' and p.tienefe = 1 ';
        }
        $sql = "SELECT p.id, LPAD(p.numero,4,0) as numero, p.descripcion, p.tienefe, p.predeterminado, p.activo 
            from punto_venta p left join deposito de on p.deposito_id = de.id 
            where p.empresa_id = ? and p.activo = 1 $sqlSoloFE
            order by p.numero";
        return $this->connection->fetchAllAssociative($sql, [$this->empresaId]);
    }

    /**
     * Busca el punto de venta de la empresa por el Número
     * @throws Exception
     */
    public function getByNumero(int $nroPuntoVenta, int $empresaId = 0): array|bool
    {
        $sql = "SELECT p.id, LPAD(p.numero,4,0) as numero, p.descripcion, p.tienefe, p.predeterminado, p.activo, pr.nombre as provincia,
                p.razon_social, p.direccion, p.codigo_postal, p.localidad, p.provincia_id, p.telefono, p.email, p.deposito_id
                from punto_venta p left join provincia pr on p.provincia_id = pr.id 
                where p.numero = ? and p.empresa_id = ?";
        return $this->connection->fetchAssociative($sql, [$nroPuntoVenta, ($empresaId === 0 ? $this->empresaId : $empresaId)]);
    }

    /**
     * busca si el punto de venta tiene FE sino devuelve cero
     * @throws Exception
     */
    public function getTieneFEPuntoVenta(int $nroPuntoVenta, int $empresaId = 0): int
    {
        $sql = "SELECT p.tienefe from punto_venta p where p.numero = ? and p.empresa_id = ?";
        $PVTieneFE = $this->connection->fetchAssociative($sql, [$nroPuntoVenta, ($empresaId === 0 ? $this->empresaId : $empresaId)]);
        if ($PVTieneFE)
            return (int)$PVTieneFE['tienefe'];
        else
            return 0;
    }


}
