<?php

namespace App\Repository\Empresa\Ventas\Presupuestos;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class PresupuestoMovimientoRepository extends TablasSimplesAbstract
{

	public function __construct(Connection $connection, Security $security)
	{
		parent::__construct($connection, $security, 'presupuesto_movimiento', false);
	}

	/**
	 * Trae todos los movimientos de un presupuesto
	 * @param int $idPresupuesto
	 * @return array|bool
	 * @throws Exception
	 */
	public function getMovimientosByPresupuesto(int $idPresupuesto): array|bool
	{
		$sql = "select pm.*, p.codigo as producto_codigo, u.nombre as producto_unidad, ti.nombre as tasaIva 
				from presupuesto_movimiento pm inner join producto p on pm.producto_id =  p.id 
				inner join unidades_medida u on p.unidad_id = u.id 
				inner join tasa_iva ti on pm.tasa_iva_id = ti.id 
				where pm.presupuesto_id = ? ";
		return $this->connection->fetchAllAssociative($sql, [$idPresupuesto]);
	}

	/**
	 * Guarda los movimientos de un presupuesto
	 * @param array $items
	 * @param int $lastInsert
	 * @param bool $borroAntes
	 * @return void
	 * @throws Exception
	 */
	public function saveMovimientos(array $items, int $lastInsert, bool $borroAntes):void
	{
		if ($borroAntes)
			$this->connection->delete('presupuesto_movimiento', ['presupuesto_id' => $lastInsert]);

		foreach ($items as $item) {
			$item['presupuesto_id'] = $lastInsert;
			unset($item['impuesto_interno'], $item['monto_no_gravado']);
			$this->connection->insert('presupuesto_movimiento', $item);
		}
	}
}
