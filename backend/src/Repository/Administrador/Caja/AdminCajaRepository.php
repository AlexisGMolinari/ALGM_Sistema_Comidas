<?php

namespace App\Repository\Administrador\Caja;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class AdminCajaRepository extends TablasSimplesAbstract
{
    private const SQLBROWSE = "SELECT c.*, u.nombre AS nombreUsuario FROM caja c
                                INNER JOIN usuarios u ON u.id = c.abierta_usuario_id";

    public function __construct(Connection $connection, Security $security)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        parent::__construct($connection, $security, 'caja');
    }

    public function obtenerDatosDeCaja(array $caja): array
    {
        // Fechas de filtro: desde apertura hasta cierre o ahora si está abierta
        $fechaDesde = $caja['abierta_fecha'];
        $fechaHasta = $caja['cerrada_fecha'] ?? date('Y-m-d H:i:s');

        $sql = "
        SELECT 
            COUNT(*) AS total_pedidos,
            SUM(total) AS total_ventas,
            SUM(CASE WHEN mp.nombre = 'efectivo' THEN total ELSE 0 END) AS ventas_efectivo,
            SUM(CASE WHEN mp.nombre = 'transferencia' THEN total ELSE 0 END) AS ventas_transferencia
        FROM pedidos p
        INNER JOIN metodo_pago mp ON p.metodo_pago_id = mp.id
        WHERE p.estado_id = 2 -- suponiendo que 1 = pedido completado
          AND p.fecha_creado BETWEEN :fechaDesde AND :fechaHasta
    ";

        $result = $this->connection->fetchAssociative($sql, [
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
        ]);

        return $result ?: [
            'total_pedidos' => 0,
            'total_ventas' => 0,
            'ventas_efectivo' => 0,
            'ventas_transferencia' => 0,
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCajaActual(): array
    {
        $sql = self::SQLBROWSE . "
        WHERE c.cerrada_fecha IS NULL
        ORDER BY c.abierta_fecha DESC
        LIMIT 1";
        $caja = $this->connection->fetchAssociative($sql);

        if (!$caja) {
            return [];
        }

        $ventas = $this->obtenerDatosDeCaja($caja);

        $montoInicial = (float) $caja['monto_inicial'];
        $totalVentas = (float) $ventas['total_ventas']; // usar valor calculado desde pedidos
        $totalGastos = $this->obtenerEgresosDeCaja((int)$caja['id']);
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
            'salesCount' => (int)$ventas['total_pedidos'],
            'salesBreakdown' => [
                'efectivo' => (float)$ventas['ventas_efectivo'],
                'transferencia' => (float)$ventas['ventas_transferencia'],
            ],
        ];
    }

    /**
     * @param int $idCaja
     * @return float
     * @throws Exception
     */
    private function obtenerEgresosDeCaja(int $idCaja): float
    {
        $sql = "SELECT SUM(e.monto) FROM egresos e
                INNER JOIN caja c ON c.id = e.caja_id
                WHERE e.caja_id = ?";
        return (float) $this->connection->fetchOne($sql, [$idCaja]) ?? 0.0;
    }


}