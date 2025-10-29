<?php


namespace App\Repository\Empresa\Informes;



use App\Repository\Empresa\EmpresaPuntoDeVentaRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class ResumenDeVentaRepository
{

    protected int $empresaId = 0;
    public function __construct(protected Connection $connection,
                                protected Security $security)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->empresaId = ($this->security->getUser())? $this->security->getUser()->getEmpresa(): 0;
    }


    /**
     * Obtengo todas las ventas de la empresa agrupadas por detalle de fact mov
     *
     * @param $desde string 'yyyy-mm-dd'
     * @param $hasta string 'yyyy-mm-dd'
     * @param $puntoVta string puede ser el id de un punto o una 'T' para todos
     * @throws Exception
     */
    public function getResumenVentas(string $desde, string $hasta, string $puntoVta, int $tipo, ?int $cajeroId = null): array
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
        $sqlCajero = ($cajeroId !== null) ? " AND fa.cajero_id = :cajeroId " : "";
        $sql="SELECT fm.producto_nombre, "
            ."SUM(CASE tc.concepto WHEN 2 THEN fm.cantidad ELSE (fm.cantidad * -1) END) as cantidad, "
            ."SUM(CASE tc.concepto "
            ."  WHEN 2 THEN (fm.precio_unitario_civa * fm.cantidad) ELSE ((fm.precio_unitario_civa * fm.cantidad) * -1) "
            ."  END) as importe "
            . "FROM factura_movimiento fm inner join factura fa on fm.factura_id =  fa.id "
            . "inner join tipo_comprobante tc on fa.tipo_comprobante_id = tc.id "
            . "where fa.fecha between :desde and :hasta and fa.empresa_id = :empre  $sqlPunto $sqlCajero"
            . "group by fm.producto_nombre";

        if ($tipo === 2) {
            $sql="SELECT sub.nombre,fam.id, fam.nombre AS familia,"
                ."SUM(CASE tc.concepto WHEN 2 THEN fm.cantidad ELSE (fm.cantidad * -1) END) as cantidad, "
                ."SUM(CASE tc.concepto "
                ."  WHEN 2 THEN (fm.precio_unitario_civa * fm.cantidad) ELSE ((fm.precio_unitario_civa * fm.cantidad) * -1) "
                ."  END) as importe "
                . "FROM factura_movimiento fm inner join factura fa on fm.factura_id =  fa.id "
                . "inner join tipo_comprobante tc on fa.tipo_comprobante_id = tc.id "
                . "INNER JOIN producto pro ON fm.producto_id = pro.id "
                . "INNER JOIN subfamilia sub ON pro.subfamilia_id = sub.id "
                . "INNER JOIN familia fam ON sub.familia_id = fam.id "
                . "where fa.fecha between :desde and :hasta and fa.empresa_id = :empre  $sqlPunto $sqlCajero"
                . "group by sub.id "
                . "ORDER BY trim(fam.nombre)";
        }
        $connection = $this->connection;
        $statement = $connection->prepare($sql);
        $statement->bindValue('desde', $desde);
        $hasta = $hasta . ' 23:59';
        $statement->bindValue('hasta', $hasta);
        $statement->bindValue('empre', $this->empresaId);
        if ($cajeroId !== null) {
            $statement->bindValue('cajeroId', $cajeroId);
        }
        return $statement->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param string $desde
     * @param string $hasta
     * @param string $puntoVta
     * @param int|null $cajeroId
     * @return array
     * @throws Exception
     */
    public function getResumenVentasMediosPagos(string $desde, string $hasta, string $puntoVta, ?int $cajeroId = null): array
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
        $sqlCajero = ($cajeroId !== null) ? " AND fa.cajero_id = $cajeroId " : "";
        $sql = "SELECT cv.nombre, "
            . "SUM(CASE tc.concepto WHEN 2 THEN fa.total_final ELSE (fa.total_final * -1) END) as totalMP  "
            . "FROM factura fa inner JOIN condicion_de_venta cv  on cv.id = fa.condicion_venta_id "
            . "inner join tipo_comprobante tc on fa.tipo_comprobante_id = tc.id  "
            . "where fa.fecha between :desde and :hasta and fa.empresa_id = :empre  $sqlPunto $sqlCajero "
            . "group by fa.condicion_venta_id";
        $connection = $this->connection;
        $statement = $connection->prepare($sql);
        $statement->bindValue('desde', $desde);
        $hasta = $hasta . ' 23:59';
        $statement->bindValue('hasta', $hasta);
        $statement->bindValue('empre', $this->empresaId);
        $registros = $statement->executeQuery()->fetchAllAssociative();
        return ($registros) ?: [];
    }
}