<?php

namespace App\Repository\Empresa\Informes;

use App\Repository\Empresa\EmpresaPuntoDeVentaRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class CostosRepository
{

    protected int $empresaId = 0;
    public function __construct(protected Connection $connection,
                                protected Security $security)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->empresaId = ($this->security->getUser())? $this->security->getUser()->getEmpresa(): 0;
    }

    /**
     * @param string $desde
     * @param string $hasta
     * @param string $puntoVta
     * @return array
     * @throws Exception
     */
    public function getResumenCostosDeLasVentas(string $desde, string $hasta, string $puntoVta): array
    {
        if (trim($puntoVta) === 'T') {
            // tomo todos los puntos de ventas
            $sqlPunto = "";
        } else {
            //filtro por un punto de venta en particular
            $punto = (new EmpresaPuntoDeVentaRepository($this->connection, $this->security))->getById(intval($puntoVta));
            $nroPunto = (int)$punto['numero'];
            $sqlPunto = " and fa.punto_venta = $nroPunto ";
        }

        $sql="SELECT fm.producto_nombre, "
            ."SUM(CASE tc.concepto WHEN 2 THEN fm.cantidad ELSE (fm.cantidad * -1) END) as cantidad, "
            ."SUM(CASE tc.concepto "
            ."  WHEN 2 THEN (fm.costo * fm.cantidad) ELSE ((fm.costo * fm.cantidad) * -1) "
            ."  END) as costo "
            . "FROM factura_movimiento fm inner join factura fa on fm.factura_id =  fa.id "
            . "inner join tipo_comprobante tc on fa.tipo_comprobante_id = tc.id "
            . "where fa.fecha between :desde and :hasta and fa.empresa_id = :empre  $sqlPunto "
            . "group by fm.producto_nombre";

        $params = [
            'desde' => $desde,
            'hasta' => $hasta . ' 23:59',
            'empre' => $this->empresaId
        ];
        return $this->connection->fetchAllAssociative($sql, $params);
    }
}