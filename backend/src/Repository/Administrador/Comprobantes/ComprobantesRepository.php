<?php

namespace App\Repository\Administrador\Comprobantes;

use App\Repository\Paginador;
use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpFoundation\Request;

class ComprobantesRepository extends TablasSimplesAbstract
{
    private string $table = "pedidos";
    /**
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function getAllPaginados(Request $request): array
    {
        $camposRequest = $request->query->all();
        $sql = "SELECT p.id, p.nombre_cliente, p.total, p.estado_id, ep.nombre, p.comprobante_img, p.fecha_creado 
                FROM " . $this->table . " p
                INNER JOIN estado_pedido ep ON p.estado_id = ep.id
                WHERE p.metodo_pago_id = 2";

        $arrParam = [ 'p.id', 'p.nombre_cliente', 'ep.nombre', 'p.total', 'p.fecha_creado'];
        $paginador = new Paginador();
        $paginador->setConnection($this->connection)
            ->setServerSideParams($camposRequest)
            ->setSql($sql)
            ->setContinuaWhere(true)
            ->setCamposAFiltrar($arrParam);  //pasar campos con alias de tabla

        return $paginador->getServerSideRegistros();
    }

    /**
     * @param string $desde
     * @param string $hasta
     * @return array
     * @throws Exception
     */
    public function getComprobantesEntreFechas(string $desde, string $hasta): array
    {
        // Validar formato de fechas (Y-m-d)
        $fechaDesde = \DateTime::createFromFormat('Y-m-d', $desde);
        $fechaHasta = \DateTime::createFromFormat('Y-m-d', $hasta);

        $errores = [];

        if (!$fechaDesde || $fechaDesde->format('Y-m-d') !== $desde) {
            $errores[] = 'La fecha "desde" no tiene un formato válido (Y-m-d)';
        }

        if (!$fechaHasta || $fechaHasta->format('Y-m-d') !== $hasta) {
            $errores[] = 'La fecha "hasta" no tiene un formato válido (Y-m-d)';
        }

        // Validar que desde <= hasta
        if ($fechaDesde && $fechaHasta && $fechaDesde > $fechaHasta) {
            $errores[] = 'La fecha "desde" no puede ser mayor que "hasta"';
        }

        if (!empty($errores)) {
            return ['errores' => $errores];
        }
        // Ajustar horas
        $fechaDesde->setTime(0, 0, 00);
        $fechaHasta->setTime(23, 59, 59);

        $sql = "SELECT p.id, p.nombre_cliente, p.total, p.estado_id, ep.nombre, p.comprobante_img, p.fecha_creado
            FROM {$this->table} p
            INNER JOIN estado_pedido ep ON p.estado_id = ep.id
            WHERE p.fecha_creado BETWEEN :desde AND :hasta AND p.metodo_pago_id = 2
            ORDER BY p.fecha_creado DESC";

        return $this->connection->fetchAllAssociative($sql, [
            'desde' => $fechaDesde->format('Y-m-d H:i:s'),
            'hasta' => $fechaHasta->format('Y-m-d H:i:s')
        ]);
    }
}