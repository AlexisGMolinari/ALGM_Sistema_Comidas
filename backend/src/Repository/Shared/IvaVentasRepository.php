<?php

namespace App\Repository\Shared;

use App\Repository\Contador\ContadorPuntoDeVentaRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class IvaVentasRepository
{
    public function __construct(protected Connection $connection,
                                protected Security   $security,
                                protected int        $empresaId = 0
    )
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        if ($this->empresaId === 0)
            $this->empresaId = ($this->security->getUser()) ? $this->security->getUser()->getEmpresa() : 0;
    }


    /**
     * Obtengo todos los registros entre ambas fechas para el iva venta
     *
     * @param $desde string 'yyyy-mm-dd'
     * @param $hasta string 'yyyy-mm-dd'
     * @param $puntoVta string Id del punto de venta
     * @throws Exception
     */
    public function getIvaVentas(string $desde, string $hasta, string $puntoVta): array
    {
        $params = [
            'desde' => $desde,
            'hasta' => $hasta . ' 23:59',
            'empre' => $this->empresaId
        ];
        if ($puntoVta === '**') {    // busco todos los puntos de venta de la empresa y filtro por FE
            $puntos = (new ContadorPuntoDeVentaRepository($this->connection, $this->security))->getPuntosDeLaEmpresa($this->empresaId);
            $sqlPto = 'and fa.punto_venta in (';
            foreach ($puntos as $punto) {
                if ((int)$punto['tienefe'] === 1) {
                    $sqlPto .= $punto['numero'] . ',';
                }
            }
            $sqlPto = substr($sqlPto, 0, -1) . ') ';
        } else {
            $sqlPto = 'and fa.punto_venta = :ptoVta ';
            $punto = (new ContadorPuntoDeVentaRepository($this->connection, $this->security))->getById($puntoVta);
            $nroPunto = (int)$punto['numero'];
            $params['ptoVta'] = $nroPunto;
        }

        $sql = "select tc.nombre,cl.nombre as nombre_fantasia, cl.numero_documento, DATE_FORMAT(fa.fecha,'%d/%m/%Y') as fecha, tc.concepto, "
            . "cl.categoria_iva_id, fa.total_neto, fa.total_exento, fa.total_iva, fa.impuesto_interno, fa.total_final, fa.total_no_gravado, "
            . "fa.punto_venta, fa.numero, ca.id as categoriaIvaId, ca.nombre as categoriaIva "
            . "from factura fa inner join tipo_comprobante tc on fa.tipo_comprobante_id = tc.id "
            . "inner join cliente cl on fa.cliente_id = cl.id "
            . "inner join categorias_iva ca on cl.categoria_iva_id = ca.id "
            . "where fa.fecha between :desde and :hasta and fa.empresa_id = :empre "
            . $sqlPto
            . " order by date(fa.fecha), fa.punto_venta, fa.numero";
        return $this->connection->fetchAllAssociative($sql, $params);
    }

    /**
     * Genera subtotales por categorías de IVA y por TASA de IVA
     * @param string $desde
     * @param string $hasta
     * @param string $puntoVta
     * @return mixed[]
     * @throws Exception
     */
    public function getTotalesPorCategoriaYTasa(string $desde,string $hasta,string $puntoVta): array
    {
        $params = [
            'desde' => $desde,
            'hasta' => $hasta . ' 23:59',
            'empre' => $this->empresaId
        ];
        if ($puntoVta === '**'){    // busco todos los puntos de venta de la empresa y filtro por FE
            $puntos = (new ContadorPuntoDeVentaRepository($this->connection, $this->security))->getPuntosDeLaEmpresa($this->empresaId);
            $sqlPto = 'and d.punto_venta in (';
            foreach ($puntos as $punto) {
                if ((int)$punto['tienefe'] === 1){
                    $sqlPto .= $punto['numero'] . ',';
                }
            }
            $sqlPto = substr($sqlPto,0,-1) . ') ';
        }else{
            $sqlPto = 'and d.punto_venta = :ptoVta ';
            $punto = (new ContadorPuntoDeVentaRepository($this->connection, $this->security))->getById($puntoVta);
            $nroPunto = (int)$punto['numero'];
            $params['ptoVta'] = $nroPunto;
        }
        $sql = "SELECT f.id, f.nombre,c.tasa, c.nombre as tasaNombre, "
            . "SUM(CASE  "
            . "  WHEN tc.concepto = 2 THEN b.monto_neto "
            . "  ELSE b.monto_neto * -1 "
            . "END) AS neto, "
            . "SUM(CASE "
            . "  WHEN tc.concepto = 2 THEN b.monto_iva "
            . "  ELSE b.monto_iva  * -1 "
            . "END) AS iva, "
            . "SUM(CASE "
            . "  WHEN tc.concepto = 2 THEN b.cantidad *  b.precio_unitario_civa "
            . "  ELSE b.cantidad *  b.precio_unitario_civa * -1 "
            . "END) AS total "
            . "FROM producto a "
            . "left join factura_movimiento b on a.id = b.producto_id "
            . "LEFT join tasa_iva c on c.id = b.tasa_iva_id "
            . "left join factura d on b.factura_id = d.id "
            . "left join cliente e on e.id = d.cliente_id "
            . "left join categorias_iva f on f.id = e.categoria_iva_id "
            . "INNER JOIN tipo_comprobante tc ON d.tipo_comprobante_id = tc.id "
            . "WHERE a.empresa_id = :empre and d.fecha between :desde and :hasta "
            . $sqlPto
            . " GROUP BY f.id,c.tasa";
        return $this->connection->fetchAllAssociative($sql, $params);
    }
}