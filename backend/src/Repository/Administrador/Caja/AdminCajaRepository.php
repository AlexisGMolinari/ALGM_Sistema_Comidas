<?php

namespace App\Repository\Administrador\Caja;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class AdminCajaRepository extends TablasSimplesAbstract
{
    private const SQLBROWSE = "SELECT c.*, u.nombre AS nombreUsuario FROM caja c
                INNER JOIN usuarios u ON u.id = c.abierta_usuario_id AND u.empresa_id = c.empresa_id ";

    public function __construct(Connection $connection, Security $security)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        parent::__construct($connection, $security, 'caja');
    }

    /**
     * @param array $caja
     * @param int $empresa_id
     * @return int[]
     * @throws Exception
     */
    public function obtenerDatosDeCaja(array $caja, int $empresa_id): array
    {
        // Fechas de filtro: desde apertura hasta cierre o ahora si está abierta
        $fechaDesde = $caja['abierta_fecha'];
        $fechaHasta = $caja['cerrada_fecha'] ?? date('Y-m-d H:i:s');

        $sql = "SELECT 
            COUNT(p.id) AS total_pedidos,
            COALESCE(SUM(p.total), 0) AS total_ventas,
            COALESCE(SUM(CASE WHEN mp.nombre = 'efectivo' THEN p.total ELSE 0 END), 0) AS ventas_efectivo,
            COALESCE(SUM(CASE WHEN mp.nombre = 'transferencia' THEN p.total ELSE 0 END), 0) AS ventas_transferencia
        FROM pedidos p
        INNER JOIN metodo_pago mp ON mp.id = p.metodo_pago_id
        WHERE p.estado_id = 2
          AND p.empresa_id = :empresa_id
          AND p.fecha_creado BETWEEN :fechaDesde AND :fechaHasta
          AND p.caja_id = :caja_id ";

        $result = $this->connection->fetchAssociative($sql, [
            'empresa_id' => $empresa_id,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'caja_id'    => (int) $caja['id'],
        ]);

        return $result ?: [
            'total_pedidos' => 0,
            'total_ventas' => 0,
            'ventas_efectivo' => 0,
            'ventas_transferencia' => 0,
        ];
    }

    /**
     * @param int $empresa_id
     * @return array
     * @throws Exception
     */
    public function getCajaActual(int $empresa_id): array
    {
        $sql = self::SQLBROWSE . "
        WHERE c.cerrada_fecha IS NULL AND c.empresa_id = :empresa_id
        ORDER BY c.abierta_fecha DESC
        LIMIT 1";
        $caja = $this->connection->fetchAssociative($sql, [
            'empresa_id' => $empresa_id
        ]);

        if (!$caja) {
            return [];
        }

        $ventas = $this->obtenerDatosDeCaja($caja, $empresa_id);

        $montoInicial = (float) $caja['monto_inicial'];
        $totalVentas = (float) $ventas['total_ventas']; // usar valor calculado desde pedidos
        $totalGastos = $this->obtenerEgresosDeCaja((int)$caja['id'], $empresa_id);
        $montoFinal = isset($caja['monto_final']) ? (float)$caja['monto_final'] : null;

        return [
            'id' => (int) $caja['id'],
            'isOpen' => $caja['abierta'] == 1,
            'openedAt' => $caja['abierta_fecha'],
            'openedByUserId' => (int) $caja['abierta_usuario_id'],
            'openedBy' => $caja['nombreUsuario'],
            'closedAt' => $caja['cerrada_fecha'],
            'closedByUserId' => isset($caja['cerrada_usuario_id']) ? (int) $caja['cerrada_usuario_id'] : null,
            'initialAmount' => $montoInicial,
            'finalAmount' => $montoFinal,
            'sales' => $totalVentas,
            'expenses' => $totalGastos,
            'currentAmount' => $montoInicial + $totalVentas - $totalGastos,
            'notes' => $caja['observaciones'],
            'salesCount' => $ventas['total_pedidos'],
            'salesBreakdown' => [
                'efectivo' => (float)$ventas['ventas_efectivo'],
                'transferencia' => (float)$ventas['ventas_transferencia'],
            ],
        ];
    }

    /**
     * @param int $cajaId
     * @param int $empresaId
     * @return float
     * @throws Exception
     */
    private function obtenerEgresosDeCaja(int $cajaId, int $empresaId): float
    {
        $sql = "SELECT COALESCE(SUM(e.monto), 0)
        FROM egresos e
        WHERE e.caja_id = :caja_id
          AND e.empresa_id = :empresa_id ";

        return (float) $this->connection->fetchOne($sql, [
            'caja_id'   => $cajaId,
            'empresa_id'=> $empresaId,
        ]);
    }



}