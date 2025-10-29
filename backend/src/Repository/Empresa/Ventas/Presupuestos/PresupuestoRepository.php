<?php

namespace App\Repository\Empresa\Ventas\Presupuestos;

use App\Form\Empresa\Clientes\ClienteType;
use App\Repository\Empresa\Clientes\ClienteRepository;
use App\Repository\Paginador;
use App\Repository\TablasSimplesAbstract;
use App\Service\Comprobantes\ConfiguracionComprobante;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PresupuestoRepository extends TablasSimplesAbstract
{
	public function __construct(Connection $connection, Security $security)
	{
		parent::__construct($connection, $security, 'presupuesto', true);
	}

	/**
	 * @param Request $request
	 * @return array
	 * @throws Exception
	 */
	public function getAllPaginados(Request $request): array
	{
		$camposRequest = $request->query->all();

		$sql = "SELECT p.id, DATE_FORMAT(p.fecha,'%d/%m/%Y') AS fecha, LPAD(p.id,8,'0') as numero, "
			. "cl.nombre, p.total_final, p.codigo, p.estado, DATE_FORMAT(p.fecha_estado,'%d/%m/%Y') AS fecha_estado, "
			. "concat(LPAD(fc.punto_venta,4,'0'),'-',LPAD(fc.numero,8,'0')) as factura_numero, fc.codigo as factura_codigo, p.moneda "
			. "FROM presupuesto p inner join cliente cl on p.cliente_id = cl.id "
			. "left join factura fc on fc.presupuesto_id = p.id "
			. "where p.empresa_id = $this->empresaId ";

		$arrParam = [ 'p.id','p.fecha', 'cl.nombre', 'fc.numero'];

		$paginador = new Paginador();
		$paginador->setConnection($this->connection)
			->setServerSideParams($camposRequest)
			->setSql($sql)
			->setContinuaWhere(true)
			->setCamposAFiltrar($arrParam);

		return $paginador->getServerSideRegistros();
	}

	/**
	 * Trae todos los datos de un presupuesto por su código
	 *
	 * @param string $codigo
	 * @return array
	 * @throws Exception
	 */
	public function getByCodigoCompleto(string $codigo): array
	{
		$sql = "SELECT  cl.nombre, cl.email, cl.numero_documento,cl.domicilio, ci.nombre as categoriaIVA, "
			. "cl.categoria_iva_id, ci2.nombre as categoriaIVACliente, pr.*, "
			. "lo.nombre as localidad, lo.codigo_postal, prov.nombre as provincia "
			. "from presupuesto pr inner join cliente cl on pr.cliente_id = cl.id "
			. "inner join empresa em on pr.empresa_id = em.id "
			. "inner join categorias_iva ci on em.categoria_iva_id = ci.id "
			. "inner join categorias_iva ci2 on cl.categoria_iva_id = ci2.id "
			. "inner join localidad lo on cl.localidad_id = lo.id "
			. "inner join provincia prov on lo.provincia_afip = prov.codigo_afip "
			. "and pr.codigo = ?;";
		$cabecera =  $this->connection->fetchAssociative($sql, [$codigo]);
		if ($cabecera){
			$sql = "select prm.*, p.codigo as producto_codigo, u.nombre as producto_unidad, prm.porcentaje_descuento "
				. "from presupuesto_movimiento prm left join producto p on prm.producto_id =  p.id "
				. "left join unidades_medida u on p.unidad_id = u.id "
				. "where prm.presupuesto_id = ? ";
			$movim  = $this->connection->fetchAllAssociative($sql, [(int)$cabecera['id']]);

			$devo['cabecera']    = $cabecera;
			$devo['movimientos'] = $movim;
			return $devo;
		}else{
			throw new HttpException(400, 'No se encuentra el presupuesto Solicitado');
		}
	}

	/**
	 * Trae todos los movimientos, agrupados por tasa de IVA (usado en pié de factura)
	 *
	 * @param int $idFactura
	 * @return array
	 * @throws Exception
	 */
	public function getMovimientosIVA(int $idFactura): array
	{
		$sql = "SELECT m.tasa_iva_id as tasaDeIVA, sum(m.monto_iva) as montoIva, sum(m.monto_neto) as montoNeto, t.nombre "
			. "FROM presupuesto_movimiento m LEFT JOIN tasa_iva t on m.tasa_iva_id = t.id "
			. "WHERE m.presupuesto_id = ? AND t.codigo_afip <> 2 "
			. "GROUP BY m.tasa_iva_id ";
		return $this->connection->fetchAllAssociative($sql, [$idFactura]);
	}

	/**
	 * Configura la cabecera y los movimientos con el servicio ConfiguracionComprobante; luego procesa el cliente,
	 * calcula totales y finalmente inserta cabecera y movimientos
	 *
	 * @param array $postValues
	 * @param ConfiguracionComprobante $configuracionComprobante
	 * @param ClienteRepository $clienteRepository
	 * @param ClienteType $clienteType
	 * @return string
	 * @throws Exception
	 */
	public function createPresupuesto(array $postValues,
									  ConfiguracionComprobante $configuracionComprobante,
									  ClienteRepository $clienteRepository,
									  ClienteType $clienteType): string
	{

		$this->connection->beginTransaction();

		$cliente = $clienteRepository->procesoClientesNuevos($postValues['cliente'], $clienteType);

		$configuracionComprobante->procesarItems($postValues, true);

		$totalNeto = $configuracionComprobante->getTotalNeto();
		$totalFinal = $configuracionComprobante->getTotalFinal();
		$totalIva = round($totalFinal - $totalNeto, 2);
		$itemsProcesados = $configuracionComprobante->getItemsProcesados();
		$itemsPresup = $configuracionComprobante->armoMovimFactura($itemsProcesados);

		$cabeceraPresu = [
			'cliente_id' => (int)$cliente['id'],
			'fecha'  => $postValues['presupuesto']['fecha'],
			'total_exento' => 0,
			'total_neto' => $totalNeto,
			'total_iva' => $totalIva,
			'total_final' => $totalFinal,
			'estado' => 10,
			'fecha_estado' => date('Y-m-d H:i:s'),
			'moneda' => $postValues['presupuesto']['moneda'],
			'cotizacion' => $postValues['presupuesto']['cotizacion'],
			'empresa_id' => $this->empresaId
		];

		$lastInsert = $this->createRegistro($cabeceraPresu);

		(new PresupuestoMovimientoRepository($this->connection, $this->security))
			->saveMovimientos($itemsPresup, $lastInsert, false);

		$this->connection->commit();

		$presupuesto = $this->getById($lastInsert);
		return $presupuesto['codigo'];
	}


	/**
	 * Actualiza un presupuesto
	 *
	 * @param array $postValues
	 * @param ConfiguracionComprobante $configuracionComprobante
	 * @param ClienteRepository $clienteRepository
	 * @param ClienteType $clienteType
	 * @return string
	 * @throws Exception
	 */
	public function updatePresupuesto(array $postValues,
									  ConfiguracionComprobante $configuracionComprobante,
									  ClienteRepository $clienteRepository,
									  ClienteType $clienteType): string
	{

		$this->connection->beginTransaction();

		$cliente = $clienteRepository->procesoClientesNuevos($postValues['cliente'], $clienteType);

		$configuracionComprobante->procesarItems($postValues, true);

		$totalNeto = $configuracionComprobante->getTotalNeto();
		$totalFinal = $configuracionComprobante->getTotalFinal();
		$totalIva = round($totalFinal - $totalNeto, 2);
		$itemsProcesados = $configuracionComprobante->getItemsProcesados();

		$itemsPresup = $configuracionComprobante->armoMovimFactura($itemsProcesados);

		$cabeceraPresu = [
			'cliente_id' => (int)$cliente['id'],
			'fecha'  => $postValues['presupuesto']['fecha'],
			'total_exento' => 0,
			'total_neto' => $totalNeto,
			'total_iva' => $totalIva,
			'total_final' => $totalFinal,
			'estado' => (int)$postValues['presupuesto']['estado'],
			'fecha_estado' => date('Y-m-d H:i:s'),
			'moneda' => $postValues['presupuesto']['moneda'],
			'cotizacion' => $postValues['presupuesto']['cotizacion'],
			'empresa_id' => $this->empresaId,
			'observaciones' => $postValues['presupuesto']['observaciones']
		];

		$presupuestoId = $postValues['presupuesto']['id'];
		$this->updateRegistro($cabeceraPresu, $presupuestoId);

		(new PresupuestoMovimientoRepository($this->connection, $this->security))
			->saveMovimientos($itemsPresup, $presupuestoId, true);

		$this->connection->commit();

		$presupuesto = $this->getById($presupuestoId);
		return $presupuesto['codigo'];
	}

}
