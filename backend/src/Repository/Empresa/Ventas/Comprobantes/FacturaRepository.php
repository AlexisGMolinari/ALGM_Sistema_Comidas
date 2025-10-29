<?php

namespace App\Repository\Empresa\Ventas\Comprobantes;

use App\Form\Empresa\Clientes\ClienteType;
use App\Repository\Contador\ContadorPuntoDeVentaRepository;
use App\Repository\Empresa\Clientes\ClienteRepository;
use App\Repository\Empresa\Cobranzas\ReciboRepository;
use App\Repository\Empresa\EmpresaRepository;
use App\Repository\Empresa\Stock\MovimientoStockRepository;
use App\Repository\Empresa\Stock\ProductoRepository;
use App\Repository\Empresa\Ventas\Presupuestos\PresupuestoRepository;
use App\Repository\Paginador;
use App\Repository\Shared\TablasAFIPRepository;
use App\Repository\TablasSimplesAbstract;
use App\Service\Comprobantes\ConfiguracionComprobante;
use App\Utils\FE\ComprobanteFE;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use SoapFault;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FacturaRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'factura', true);
    }

    /**
     * @throws Exception
     */
    public function getAllPaginados(Request $request): array
    {
        $camposRequest = $request->query->all();

        $sql = "SELECT  tc.nombre as tipoComprobanteNombre, concat(LPAD(f.punto_venta,4,'0'),'-',LPAD(f.numero,8,'0')) as numero, 
            cl.nombre,cl.numero_documento, f.fecha, f.total_final, f.id, cv.nombre as condicion, f.codigo 
            from factura f inner join cliente cl on f.cliente_id = cl.id 
            inner join tipo_comprobante tp on f.tipo_comprobante_id = tp.id 
            inner join tipo_comprobante tc on tipo_comprobante_id = tc.id 
            inner join condicion_de_venta cv on f.condicion_venta_id = cv.id 
            where f.empresa_id = $this->empresaId";

        $arrParam = [ 'tc.nombre','tc.nombre', 'cl.nombre', 'f.fecha',
            "LPAD(f.punto_venta,4,'0')", "LPAD(f.numero,8,'0')", 'f.total_final'];

        $paginador = new Paginador();
        $paginador->setConnection($this->connection)
            ->setServerSideParams($camposRequest)
            ->setSql($sql)
            ->setContinuaWhere(true)
            ->setCamposAFiltrar($arrParam);  //pasar campos con alias de tabla

        return $paginador->getServerSideRegistros();
    }

	/**
	 * Hago una doble revisión si existe el comprobante por la cae y sino por ptoVta, numero
	 *
	 * @param array $comprobantePost
	 * @throws Exception
	 */
    public function checkSiElComprobanteExiste(array $comprobantePost): void
    {
        $cae = trim($comprobantePost['CodAutorizacion']);
        $ptoVenta = (int)$comprobantePost['PtoVta'];
        $nroComp = (int)$comprobantePost['CbteDesde'];
        $tipoComp = (int)$comprobantePost['CbteTipo'];
		$empresaId = (int)$comprobantePost['empresaId'];

        $sql = "select * from factura where cae = ? and empresa_id = ?";
        $factura = $this->connection->fetchAssociative($sql, [$cae, $empresaId]);

        if (!$factura){
            $sql = "select * from factura where tipo_comprobante_id = ? and punto_venta = ? and numero = ? and empresa_id = ?";
            $factura = $this->connection->fetchAssociative($sql, [$tipoComp, $ptoVenta, $nroComp, $empresaId]);
        }

		if ($factura){
			$compNro = str_pad($comprobantePost['PtoVta'],4,'0', STR_PAD_LEFT)
				. '-' .  str_pad($comprobantePost['CbteDesde'],8,'0', STR_PAD_LEFT);
			throw new HttpException(400, 'Existe un comprobante con ese Nro: ' . $compNro);
		}
    }


	/**
	 * Función que trae todos los datos del comprobante y si tiene un recibo parcial
	 * @param string $codigo
	 * @param bool $controlaEmpresa
	 * @return array
	 * @throws Exception
	 */
    public function getByCodigoFactura(string $codigo, bool $controlaEmpresa = true): array
    {
        $sql = "SELECT  cl.nombre, cl.email, cl.numero_documento,cl.domicilio,tp.letra as letra_factura, tc.nombre as tipoComprobanteNombre, 
            tc.tipo_comprobante_afip, ci.nombre as categoriaIVA, cv.nombre as condicionVenta, f.*, cl.categoria_iva_id, ci2.nombre as categoriaIVACliente, 
            lo.nombre as localidad, lo.codigo_postal, prov.nombre as provincia, presu.moneda, presu.cotizacion 
            from factura f inner join cliente cl on f.cliente_id = cl.id 
            inner join empresa em on f.empresa_id = em.id 
            inner join tipo_comprobante tp on f.tipo_comprobante_id = tp.id 
            inner join categorias_iva ci on em.categoria_iva_id = ci.id 
            inner join categorias_iva ci2 on cl.categoria_iva_id = ci2.id 
            inner join tipo_comprobante tc on f.tipo_comprobante_id = tc.id 
            inner join condicion_de_venta cv on f.condicion_venta_id = cv.id 
            inner join localidad lo on cl.localidad_id = lo.id 
            inner join provincia prov on lo.provincia_afip = prov.codigo_afip 
            left join presupuesto presu on f.presupuesto_id = presu.id 
            where f.codigo = ?" . ($controlaEmpresa ? " and f.empresa_id = $this->empresaId" : "");
        $cabecera =  $this->connection->fetchAssociative($sql, array($codigo));
        if ($cabecera){
            $sql = "select f.*, p.codigo as producto_codigo, u.nombre as producto_unidad, f.porcentaje_descuento 
                from factura_movimiento f 
                left join producto p on f.producto_id =  p.id 
                left join unidades_medida u on p.unidad_id = u.id 
                where f.factura_id = ?";
            $movim  = $this->connection->fetchAllAssociative($sql, array((int)$cabecera['id']));

            // busco si tiene un recibo inputado a esa fc, parcial, para poner el texto Su entrega en la factura y si es cta cte
            $montoEntrega = 0;
            if ((int)$cabecera['condicion_venta_id'] === 2){
                $sql        = "SELECT r.importe FROM recibo r where r.codigo = ?;";
                $entrega    =  $this->connection->fetchAssociative($sql, array($codigo));
                if ($entrega && floatval($entrega['importe'] < floatval($cabecera['total_final']))){
                    $montoEntrega = $entrega['importe'];
                }
            }
            $devo['cabecera']    = $cabecera;
            $devo['movimientos'] = $movim;
            $devo['entrega']     = $montoEntrega;
            return $devo;
        }else{
			throw new HttpException(404, 'No se encontró el comprobante: ' . $codigo);
        }
    }

    /**
     * función que trae todos los movim, agrupados por tasa de IVA (usado en pié de factura)
     *
     * @throws Exception
     */
    public function getMovimientosIVA(int $idFactura): array
    {
        $sql = "SELECT m.tasa_iva_id as tasaDeIVA, sum(m.monto_iva) as montoIva, sum(m.monto_neto) as montoNeto, t.nombre 
            FROM factura_movimiento m 
            LEFT JOIN tasa_iva t on m.tasa_iva_id = t.id 
            WHERE m.factura_id = ? AND t.codigo_afip not in (1,2) 
            GROUP BY m.tasa_iva_id ";
        return $this->connection->fetchAllAssociative($sql, [$idFactura]);
    }


    /**
     * función que trae todos los comprobantes y los que NO se saldaron (pagaron) y tienen condición =2 cta cte
     * @throws Exception
     */
    public function getComprobantesAdeudados(int $idCliente): array
    {
        $sql = "SELECT  f.id,f.codigo, tp.nombre as tipoComprobanteNombre, concat(LPAD(f.punto_venta,4,'0'),'-',LPAD(f.numero,8,'0')) as numero, 
                DATE_FORMAT(f.fecha,'%d/%m/%Y') AS fecha ,  cv.id as condicion, cv.abreviatura as condicionNombre, tp.concepto,  
                (select Ifnull(ROUND(sum(rf.importe),2),0) from facturas_recibos rf where rf.factura_id = f.id) as pagado, DATE_FORMAT(r.fecha,'%d/%m/%Y') as fechaPago, 
                CASE 
                 WHEN tp.concepto = 1 then (f.total_final) * (-1) 
                 ELSE f.total_final 
                END as total_final 
                from factura f left join facturas_recibos fr on f.id = fr.factura_id 
                left join recibo r on fr.recibo_id = r.id 
                inner join tipo_comprobante tp on f.tipo_comprobante_id = tp.id 
                inner join condicion_de_venta cv on f.condicion_venta_id = cv.id 
                where f.empresa_id = $this->empresaId and f.fecha > '2019-04-13'
                and f.cliente_id = ?
                group by f.id 
                order by f.id ";
        return $this->connection->fetchAllAssociative($sql, [$idCliente]);
    }

    /**
     * Función que suma todas las facturas y resta las NC hechas en el mes para obtener el total facturado
     * del punto de venta electrónico
     *
     * @throws Exception
     */
    public function getFacturacionPeriodo(): array
    {
        $mesesN = array( 1 => "Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio",
            "Agosto","Septiembre","Octubre","Noviembre","Diciembre");
        $fecha = date("Y-m-") . '01';

        $sql = "SELECT ifnull(sum(total_final),0) as totalFC 
            FROM factura f 
            inner join empresa e on f.empresa_id = e.id 
            inner join punto_venta pv on f.punto_venta = pv.numero and pv.empresa_id = e.id 
            inner join tipo_comprobante tc on f.tipo_comprobante_id = tc.id 
            where e.id = ? and fecha between  ? and now() and pv.tienefe = 1 and tc.concepto = 2";

        $mesCurso   = $mesesN[date('n')] ;
        $sumFactu   = $this->connection->fetchAssociative($sql, [$this->empresaId, $fecha]);

        $sql = "SELECT ifnull(sum(total_final),0) as totalNC 
            FROM factura f 
            inner join empresa e on f.empresa_id = e.id 
            inner join punto_venta pv on f.punto_venta = pv.numero and pv.empresa_id = e.id 
            inner join tipo_comprobante tc on f.tipo_comprobante_id = tc.id 
            where e.id = ? and fecha between ? and now() and pv.tienefe = 1 and tc.concepto = 1";
        $sumNC      = $this->connection->fetchAssociative($sql, [$this->empresaId, $fecha]);

        $impoTotal = floatval($sumFactu['totalFC'] - $sumNC['totalNC']);

        return array('total' => $impoTotal, 'mes' => $mesCurso);
    }

    /**
     * función que suma todas las facturas y resta las NC hechas en el mes para obtener el total facturado
     * del punto de venta electrónico
     *
     * @throws Exception
     */
    public function getFacturacionDia(): array
    {

        $fechaInicio = date("Y-m-d") . ' 00:00:00' ;
        $fechaFin = date("Y-m-d") . ' 23:59:59' ;

        $sql = "SELECT ifnull(sum(total_final),0) as totalFC 
            FROM factura f 
            inner join empresa e on f.empresa_id = e.id 
            inner join punto_venta pv on f.punto_venta = pv.numero and pv.empresa_id = e.id 
            inner join tipo_comprobante tc on f.tipo_comprobante_id = tc.id 
            where e.id = ? and fecha between  ? and ? and pv.tienefe = 1 and tc.concepto = 2";


        $sumFactu   = $this->connection->fetchAssociative($sql, [$this->empresaId, $fechaInicio, $fechaFin]);

        $sql = "SELECT ifnull(sum(total_final),0) as totalNC 
            FROM factura f 
            inner join empresa e on f.empresa_id = e.id 
            inner join punto_venta pv on f.punto_venta = pv.numero and pv.empresa_id = e.id 
            inner join tipo_comprobante tc on f.tipo_comprobante_id = tc.id 
            where e.id = ? and fecha between ? and ? and pv.tienefe = 1 and tc.concepto = 1";
        $sumNC      = $this->connection->fetchAssociative($sql, [$this->empresaId, $fechaInicio, $fechaFin]);

        $impoTotal = floatval($sumFactu['totalFC'] - $sumNC['totalNC']);

        return array('total' => $impoTotal);
    }

	/**
	 * Crea un comprobante (fc/nc/nd)
	 * @param array $postValues
	 * @param ClienteType $clienteType
	 * @param ConfiguracionComprobante $configuracionComprobante
	 * @return string
	 * @throws Exception|SoapFault
	 */
	public function createComprobante(array $postValues,
									  ClienteType $clienteType,
									  ConfiguracionComprobante $configuracionComprobante): string
	{
        $tablaAfipRepository = (new TablasAFIPRepository($this->connection));
		$cliente = (new ClienteRepository($this->connection, $this->security))
			->procesoClientesNuevos($postValues['cliente'], $clienteType);

		$configuracionComprobante->procesarItems($postValues, false);

		$puntoVentaRepository = new ContadorPuntoDeVentaRepository($this->connection, $this->security);
		// verifico si el punto de venta es electrónico o no
		$puntoVenta = $puntoVentaRepository->getPuntoVentaElectronico($postValues['comprobante']['punto_venta_id'], $this->empresaId);
        $empresa = (new EmpresaRepository($this->connection, $this->security))->getByIdInterno();
		if ((int)$puntoVenta['tienefe'] === 1) {
			$tipoComprobante = $tablaAfipRepository->getTipoComprobanteById($postValues['comprobante']['tipo_comprobante_id']);
            $categoriaIvaReceptor = $tablaAfipRepository->getCategoriasIVAById($cliente['categoria_iva_id']);
            $cliente['cateogriaIvaReceptor'] = $categoriaIvaReceptor['codigo_afip'];

			$comprobanteFe = new ComprobanteFE();
			$comprobanteFe->setEmpresa($empresa)
				->setCliente($cliente)
				->setPuntoVenta($puntoVenta)
				->setTipoComprobante($tipoComprobante)
				->setItemsProcesados($configuracionComprobante->getItemsProcesados())
				->setTotalFinal($configuracionComprobante->getTotalFinal())
				->setTotalImpuestoInterno($configuracionComprobante->getTotalImpuestoInterno())
				->setTotalIva($configuracionComprobante->getTotalIva())
				->setTotalNeto($configuracionComprobante->getTotalNeto())
				->setTotalNoGravado($configuracionComprobante->getTotalNoGravado())
				->setPostValues($postValues);
			$arrTempCabecera = 	$comprobanteFe->generar();
		} else {
			$tipoComprobanteId = (int)$postValues['comprobante']['tipo_comprobante_id'];
			$nroComprobanteAEmitir = $puntoVentaRepository->getNumeroComprobanteAEmitir($puntoVenta['id']);
			$arrTempCabecera = $this->adaptoDatosCabeceraFactura($cliente,
				$postValues,
				$puntoVenta,
				$nroComprobanteAEmitir,
				$configuracionComprobante->getTotalIva(),
				$tipoComprobanteId,
				$configuracionComprobante->getTotalImpuestoInterno(),
				$configuracionComprobante->getTotalNoGravado()
			);
		}
        date_default_timezone_set('America/Argentina/Cordoba');
        // verifico si viene desde venta rápida (cajero_id = 0 o > 0) entonces tomo la fecha del servidor - JIRA 1293
        if($arrTempCabecera['cajero_id'] === NULL) {
            $arrTempCabecera['fecha'] .=  ' ' . date('H:i:s');
        }else{
            $arrTempCabecera['fecha'] = date('Y-m-d H:i:s');
        }

		// controlo si tiene presupuesto, guardo su Id en la cabecera de la fc
		if (isset($postValues['comprobante']['presupuesto_id']) && (int)$postValues['comprobante']['presupuesto_id'] > 0) {
			$arrTempCabecera['presupuesto_id'] = (int)$postValues['comprobante']['presupuesto_id'];
		}

		// genero la cabecera y los movimientos para guardar en las tablas
		$facturaCabecera = $configuracionComprobante->armoFacturaCabecera($arrTempCabecera);
		$facturaMovimientos = $configuracionComprobante->armoMovimFactura($configuracionComprobante->getItemsProcesados());

		$this->connection->beginTransaction();

		$uuid = $this->saveComprobante($facturaCabecera,
			$facturaMovimientos,
			$postValues['comprobante']['entrega'],
			$puntoVenta, true);

        // proceso Stock
        if (((int)$empresa['controla_stock']) === 1) {
            (new MovimientoStockRepository($this->connection, $this->security))
                ->generoMovStockFromComprobante($uuid, $this->empresaId);
        }

		$this->connection->commit();

		return $uuid;
	}

	/**
	 * Guarda un comprobante completo con sus movimientos y actualiza el saldo del cliente y presupuesto
	 * @param array $cabecera
	 * @param array $movimientos
	 * @param float $entrega
	 * @param array $puntoVenta - para recuperar comprobantes de AFIP paso este array vacío
	 * @param bool $controlaEmpresa
	 * @return string
	 * @throws Exception
	 */
	private function saveComprobante(array $cabecera,
									 array $movimientos,
									 float $entrega,
									 array $puntoVenta,
									 bool $controlaEmpresa): string
	{
		$cabecera['codigo'] = Uuid::uuid4();

		if ($controlaEmpresa) {
			$facturaId = $this->createRegistro($cabecera);
		} else {
			$this->connection->insert('factura', $cabecera);
			$facturaId = $this->connection->lastInsertId();
		}


		foreach ($movimientos as $item) {
			$item['factura_id'] = $facturaId;
			$this->connection->insert('factura_movimiento', $item);
		}

		// si no tiene FE aumento el nro de comprobante +1
		if (isset($puntoVenta['tienefe']) && (int)$puntoVenta['tienefe'] === 0 ) {
			$nuevoNro = (int)$cabecera['numero'] + 1;
			(new ContadorPuntoDeVentaRepository($this->connection, $this->security))
				->setNuevoNumeroComprobante($puntoVenta['id'], $nuevoNro);
		}

		$reciboRepository = new ReciboRepository($this->connection, $this->security);
		if ((int)$cabecera['condicion_venta_id'] !== 2 ) // si no es cuenta corriente genero un recibo
		{
			$reciboRepository->saveContado($cabecera, $facturaId);
		} else {
			if ($entrega > 0 ) {    // si hizo una entrega le hago un recibo por esa entrega
				$cabecera['total_final'] = $entrega;
				$reciboRepository->saveContado($cabecera, $facturaId);
			}
		}

		// actualizo el saldo del cliente
		$idCliente  = (int)$cabecera['cliente_id'];
		$saldo      = $reciboRepository->getSaldoResumen($idCliente);
		$arrCliente = ['saldo' => $saldo];
		(new ClienteRepository($this->connection, $this->security))->updateRegistro($arrCliente, $idCliente);

		// cambio el estado del presupuesto
		if (isset($cabecera['presupuesto_id'])){
			$arrPresupuesto = ['estado' => 40];
			(new PresupuestoRepository($this->connection, $this->security))->updateRegistro($arrPresupuesto, $cabecera['presupuesto_id']);
		}

		return $cabecera['codigo'];
	}



	/**
	 * Creo un array con los datos para generar la cabecera de la factura
	 * @param array $cliente
	 * @param array $postValues
	 * @param array $puntoVenta
	 * @param int $nroComprobanteAEmitir
	 * @param float $totalIva
	 * @param int $tipoComprobanteId
	 * @param float $totalImpuestoInterno
	 * @param float $totalNoGravado
	 * @return array
	 */
	private function adaptoDatosCabeceraFactura(array $cliente,
												array $postValues,
												array $puntoVenta,
												int $nroComprobanteAEmitir,
												float $totalIva,
												int $tipoComprobanteId,
												float $totalImpuestoInterno,
												float $totalNoGravado): array
	{
		return [
			'cliente_id' 					=> (int)$cliente['id'],
			'condicion_venta_id' 			=> (int)$postValues['comprobante']['condicion_venta_id'],
			'fecha' 						=> $postValues['comprobante']['fecha'],
			'punto_venta'					=> (int)$puntoVenta['numero'],
			'total_iva' 					=> $totalIva,
			'tipo_comprobante_id' 			=> $tipoComprobanteId,
			'cae' 							=> null,
			'fecha_vencimiento_cae' 		=> $postValues['comprobante']['fecha'],
			'numero' 						=> $nroComprobanteAEmitir,
			'fecha_vencimiento_factura' 	=> $postValues['comprobante']['fecha'],
			'impuesto_interno'          	=> $totalImpuestoInterno,
			'total_no_gravado'				=> $totalNoGravado,
			'tipo_comprobante_asociado' 	=> $postValues['comprobante']['tipo_comprobante_asociado'],
			'punto_vta_comprobante_asociado' => $postValues['comprobante']['punto_vta_comprobante_asociado'],
			'numero_comprobante_asociado' 	=> $postValues['comprobante']['numero_comprobante_asociado'],
            'cajero_id'                     => $postValues['comprobante']['cajero_id']
		];
	}

	/**
	 * Verifica si ya existe el comprobante sino lo guarda
	 *
	 * @param array $postvalues
	 * @param ProductoRepository $productoRepository
	 * @return void
	 * @throws Exception
	 */
	public function guardoComprobanteDesdeAfip(array $postvalues,
											   ProductoRepository $productoRepository): void
	{
		$this->checkSiElComprobanteExiste($postvalues);
		$comprobante = (new ConfiguracionComprobante($productoRepository))
			->procesoComprobanteAFIP($postvalues,$this->connection);

		$this->connection->beginTransaction();
		$comprobante['cabecera']['empresa_id'] = $postvalues['empresaId'];
		$this->saveComprobante($comprobante['cabecera'], $comprobante['movimientos'], 0, [], false);

		$this->connection->commit();
	}

    /**
     * Calcula la facturación de los últimos 6 meses
     * @param int $empresaId
     * @return array
     * @throws Exception
     */
    public function calculaFacturacionUltimos6Meses(int $empresaId): array
    {
        $mesesN = [
            1 => "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
            "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
        ];
        $facturacion = [];

        for ($i = 5; $i >= 0; $i--) {
            $fechaBase = new \DateTime("first day of -$i months");
            $primerDiaMes = $fechaBase->format('Y-m-01');
            $ultimoDiaMes = $fechaBase->format('Y-m-t');
            $numeroMes = (int)$fechaBase->format('n');

            $totalFacturas = $this->connection->fetchOne(
                "SELECT ifnull(SUM(total_final), 0) FROM " . $this->nombreTabla . " f 
                        INNER JOIN tipo_comprobante tc ON f.tipo_comprobante_id = tc.id
                        WHERE f.empresa_id = ? AND fecha BETWEEN ? AND ? AND tc.concepto = 2",
                        [$empresaId, $primerDiaMes, $ultimoDiaMes]
            );
            $totalNotasCredito = $this->connection->fetchOne(
                "SELECT ifnull(SUM(total_final), 0) FROM " . $this->nombreTabla . " f 
                    INNER JOIN tipo_comprobante tc ON f.tipo_comprobante_id = tc.id
                    WHERE f.empresa_id = ? AND fecha BETWEEN ? AND ? AND tc.concepto = 1",
                [$empresaId, $primerDiaMes, $ultimoDiaMes]
            );
            $totalNeto = round((float)$totalFacturas - (float)$totalNotasCredito, 3);
            $facturacion[] = [
                'mes' => $mesesN[$numeroMes],
                'total' => $totalNeto,
            ];
        }
        return $facturacion;
    }
}
