<?php

namespace App\Repository\Dashboard;

use App\Repository\Administrador\Caja\AdminCajaRepository;
use App\Repository\Administrador\Pedidos\AdminPedidoRepository;
use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Exception;

class DashboardRepository extends TablasSimplesAbstract
{
    /**
     * @return array
     * @throws Exception
     */
    public function datosInicio(): array
    {
        $pedidoRepository = (new AdminPedidoRepository($this->connection, $this->security));

        $cajaRepository = (new AdminCajaRepository($this->connection, $this->security));
        $estadoCaja = $cajaRepository->getCajaActual();
        // Si no hay caja abierta, retornamos sólo eso
        if (empty($estadoCaja)) {
            return [
                'salesTotal' => 0,
                'pendingOrders' => 0,
                'completedOrdersToday' => 0,
                'cashRegisterStatus' => 'closed',
                'dailyExpenses' => 0,
                'dailyBalance' => 0,
            ];
        }
        // Pedidos pendientes (por ejemplo, estado_id = 1)
        $pedidosPendientes = $pedidoRepository->contarPedidosPendientesEnCajaAbierta($estadoCaja);

        return [
            'salesTotal' => $estadoCaja['sales'],
            'pendingOrders' => $pedidosPendientes,
            'completedOrdersToday' => $estadoCaja['salesCount'],
            'cashRegisterStatus' => $estadoCaja['isOpen'] ? 'open' : 'closed',
            'dailyExpenses' => $estadoCaja['expenses'],
            'dailyBalance' => $estadoCaja['sales'] - $estadoCaja['expenses'],
        ];
    }

}