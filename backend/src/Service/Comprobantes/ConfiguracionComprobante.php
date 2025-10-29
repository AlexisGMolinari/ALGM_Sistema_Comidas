<?php

namespace App\Service\Comprobantes;

use App\Repository\Empresa\Stock\ProductoRepository;
use App\Repository\Shared\TablasAFIPRepository;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * Clase con funciones útiles para armar info de un comprobante (cabecera/items/totales, etc.)
 */
class ConfiguracionComprobante
{

	private ProductoRepository $productoRepository;

	public function __construct(ProductoRepository $productoRepository)
	{
		$this->productoRepository = $productoRepository;
	}

	// variables usadas para calcular los totales de la factura
	protected float $totalFinal = 0.0;
	protected float $totalNeto  = 0.0;
	protected float $totalIva  = 0.0;
	protected float $totalImpuestoInterno  = 0.0;
	protected float $totalNoGravado  = 0.0;

	/**
	 * Almacena los ítems procesados
	 * @var array
	 */
	protected array $itemsProcesados = [];

	/**
	 * Función que arma la cabecera de la factura de acuerdo a la cabecera temporal devuelta
	 *
	 * @param $cabecera array
	 * @return array
	 */
	public function armoFacturaCabecera(array $cabecera): array
	{
		$tipoComprobanteAsociado = null;
		if (isset($cabecera['tipo_comprobante_asociado']) && (int)$cabecera['tipo_comprobante_asociado'] > 0) {
			$tipoComprobanteAsociado = (int)$cabecera['tipo_comprobante_asociado'];
		}
		$ptoVtaComprobanteAsociado = null;
		if (isset($cabecera['punto_vta_comprobante_asociado']) && (int)$cabecera['punto_vta_comprobante_asociado'] > 0) {
			$ptoVtaComprobanteAsociado = (int)$cabecera['punto_vta_comprobante_asociado'];
		}
		$numeroComprobanteAsociado = null;
		if (isset($cabecera['numero_comprobante_asociado']) && (int)$cabecera['numero_comprobante_asociado'] > 0) {
			$numeroComprobanteAsociado = (int)$cabecera['numero_comprobante_asociado'];
		}
		$presupuestoId = null;
		if (isset($cabecera['presupuesto_id']) && (int)$cabecera['presupuesto_id'] > 0) {
			$presupuestoId = (int)$cabecera['presupuesto_id'];
		}
		return [
			'cliente_id'            => $cabecera['cliente_id'],
			'condicion_venta_id'    => $cabecera['condicion_venta_id'],
			'fecha'                 => $cabecera['fecha'],
			'punto_venta'           => $cabecera['punto_venta'],
			'total_exento'          => 0,
			'total_neto'            => round($this->totalNeto,2),
			'total_iva'             => $cabecera['total_iva'],
			'total_final'           => round($this->totalFinal,2),
			'impuesto_interno'      => round($this->totalImpuestoInterno,2),
			'total_no_gravado'      => round($this->totalNoGravado,2),
			'tipo_comprobante_id'   => $cabecera['tipo_comprobante_id'],
			'cae'                   => $cabecera['cae'],
			'fecha_vencimiento_cae' => $cabecera['fecha_vencimiento_cae'],
			'numero'                => $cabecera['numero'],
			'fecha_vencimiento_factura' => $cabecera['fecha_vencimiento_factura'],
			'tipo_comprobante_asociado' => $tipoComprobanteAsociado,
			'punto_vta_comprobante_asociado' => $ptoVtaComprobanteAsociado,
			'numero_comprobante_asociado' => $numeroComprobanteAsociado,
			'presupuesto_id' 		=> $presupuestoId,
            'cajero_id'             => ((int)$cabecera['cajero_id'] === 0)? null: $cabecera['cajero_id']
		];
	}

	/**
	 * Arma los registros de factura movimientos de acuerdo a los items procesados
	 * @param array $itemsFc
	 * @return array
	 */
	public function armoMovimFactura(array $itemsFc): array
	{
		$items = [];
		foreach ($itemsFc as $item) {
			$itemsFactura = [
				'tasa_iva_id'           => $item['tasa_iva_id'],
				'producto_id'           => $item['id'],
				'producto_nombre'       => $item['nombre'],
				'cantidad'              => $item['cantidad'],
				'precio_unitario_siva'  => $item['precioUnitSnIva'],
				'precio_unitario_civa'  => $item['precio'],
				'monto_iva'             => $item['montoIVA'],
				'monto_neto'            => $item['montoNeto'],
				'porcentaje_iva'        => (float)($item['tasa'] - 1) * 100,
				'porcentaje_descuento'  => $item['porcentaje_descuento'],
				'monto_descuento'       => $item['monto_descuento'],
				'impuesto_interno'      => $item['impuesto_interno'],
				'monto_no_gravado'      => $item['montoNoGravado']
			];
            if (isset($item['costo'])) {
                $itemsFactura['costo'] = $item['costo'];
            }
			$items[] = $itemsFactura;
		}
		return $items;
	}

    /**
     * Función que recorre los movimientos, agrega la tasa de iva, calcula el iva y neto y totales para la cabecera
     * de la factura, agrupa items por tasa de iva y deja listo items para guardar en factura movimiento. También trae
     * el costo de cada ítem. SE usa en PRESUPUESTO también
     *
     * @param array $itemsComprob
     * @param bool $esPresupuesto
     * @return void
     * @throws Exception
     */
	public function procesarItems(array $itemsComprob, bool $esPresupuesto): void
	{
		$items = $itemsComprob['productos'];
		$totalFinal = 0.0;
		$totalIva = 0.0;
		$totalNeto  = 0.0;
		$totalImpInterno  = 0.0;
		$totalNoGravado = 0.0;
		foreach ($items as $item){
			$tasaCostoProducto = $this->productoRepository->getByIdItemComprob($item['id']);
			$item['impuesto_interno']   = (float)$tasaCostoProducto['impuesto_interno'] * $item['cantidad'];
			$item['total']              = (float)$item['total'] ;
			$item['tasa_iva_id']        = (int)$tasaCostoProducto['tasa_iva_id'];                                       //calculo tasa ej: 1.105
			$item['tasa']               = '1.' . str_replace('.','',$tasaCostoProducto['tasa']);          //calculo tasa ej: 1.105
			$item['tasaAfip']           = (int)$tasaCostoProducto['codigo_afip'];                                       //nro de tasa iva AFIP: ej 5
			// si la tasa de iva es 1 (No Gravado) NO sumo Neto
			if ($item['tasaAfip'] === 1 ) {
				$item['montoNeto'] = 0;
				$item['montoNoGravado'] = $item['total'] ;
				$item['montoIVA'] = 0;
			}else{
				$item['montoNeto'] = ($item['total'] - $item['impuesto_interno']) / floatval($item['tasa']);          	//calculo el monto del IVA del total del ítem
				$item['montoNoGravado'] = 0;
				$item['montoIVA'] = ($item['total'] - $item['impuesto_interno'] - $item['montoNeto']);       	        //monto total menos el IVA
			}

			$item['precioUnitSnIva']    =  ($item['precio'] - ($item['impuesto_interno'] / $item['cantidad'] ) )/ floatval($item['tasa']);
			$totalFinal                 += $item['total'];
			$totalIva                 	+= $item['montoIVA'];
			$totalNeto                  += (float)$item['montoNeto'];
			$totalImpInterno            += $item['impuesto_interno'];
			$totalNoGravado 			+= (float)$item['montoNoGravado'];
			$item['porcentaje_descuento'] = (float)$item['porcRecBonif'];
			$item['monto_descuento']    = (float)$item['precioBonif'];
            if (!$esPresupuesto) {
                $item['costo'] = $tasaCostoProducto['costo'];
            }
			$this->itemsProcesados[] = $item;
		}


		$this->totalNeto = $totalNeto;
		$this->totalIva= $totalIva;
		$this->totalFinal = $totalFinal;
		$this->totalNoGravado  = $totalNoGravado;
		$this->totalImpuestoInterno = $totalImpInterno;
	}


	/**
	 * Proceso y parseo los datos del comprobante que viene desde la consulta de AFIP para guardarlo en F$
	 *
	 * @param array $comprobante
	 * @param Connection $connection
	 * @return array
	 * @throws Exception
	 */
	public function procesoComprobanteAFIP(array $comprobante, Connection $connection): array
	{
		$comprobFecha = DateTime::createFromFormat('Ymd', $comprobante['CbteFch']);
		// si viene la fecha de vto pago la paso sino tomo la fecha del comprobante
		$vtoCae = DateTime::createFromFormat('Ymd', $comprobante['FchVto']);
		if (strlen($comprobante['FchVtoPago']) > 2){
			$vtoFactura = DateTime::createFromFormat('Ymd', $comprobante['FchVtoPago']);
		}else{
			$vtoFactura = $comprobFecha;
		}

		$this->totalNeto =  round(floatval($comprobante['ImpNeto']),2);
		$this->totalFinal =  round(floatval($comprobante['ImpTotal']),2);
		$this->totalNoGravado =  round(floatval($comprobante['ImpTotConc']),2);
		$empresaId = (int)$comprobante['empresaId'];
		$tablasAfipRepository = new TablasAFIPRepository($connection);

		$arrCabecera = [
			'cliente_id' => (int)$comprobante['clienteId'],
			'condicion_venta_id' => 1,
			'fecha' => $comprobFecha->format('Y-m-d'),
			'punto_venta' => (int)$comprobante['PtoVta'],
			'total_iva' => (float)$comprobante['ImpIVA'],
			'tipo_comprobante_id' => (int)$comprobante['CbteTipo'],
			'cae' => $comprobante['CodAutorizacion'],
			'fecha_vencimiento_cae' => $vtoCae->format('Y-m-d'),
			'numero' => (int)$comprobante['CbteDesde'],
			'fecha_vencimiento_factura' => $vtoFactura->format('Y-m-d'),
			'impuesto_interno' => 0.0,
			'total_no_gravado' => (float)$comprobante['ImpTotConc'],
            'cajero_id' => null,
		];
		$cabecera = $this->armoFacturaCabecera($arrCabecera);
		$cabecera['presupuesto_id'] = null;

		// analizo si tiene movimientos los cargo, sino genero un movimiento con el total
		$producto = $this->productoRepository->getPrimerProductoEmpresa($empresaId);
		$productoId = (int)$producto['id'];
		$items = [];
		if (isset($comprobante['Iva'])){
			// tiene movimientos - puede ser Fc A con varios arrays con IVA o B con un solo registro de IVA
			if (isset($comprobante['Iva']['AlicIva']['Id'])) {
				$tasaIva = $tablasAfipRepository->getTasaIvaByCodAfip($comprobante['Iva']['AlicIva']['Id']);
				$precioSinIva = (float)$comprobante['Iva']['AlicIva']['BaseImp'];
				$precioConIva = (float)$comprobante['Iva']['AlicIva']['BaseImp'] + (float)$comprobante['Iva']['AlicIva']['Importe'];
				$arrMov = [
					'tasa_iva_id' => (int)$tasaIva['id'],
					'id' => $productoId,
					'nombre' => 'Comprobante recuperado desde AFIP',
					'cantidad' => 1,
					'precioUnitSnIva' => $precioSinIva,
					'precio' => $precioConIva,                   // tomo monto neto
					'montoIVA' => (float)$comprobante['Iva']['AlicIva']['Importe'],
					'montoNeto' => $precioSinIva,
					'tasa' => (($tasaIva['tasa'] / 100) + 1),
					'porcentaje_descuento' => 0,
					'monto_descuento' => 0,
					'impuesto_interno' => 0,
					'montoNoGravado' => 0
				];
				$items[] = $arrMov;
			} else {
				foreach ($comprobante['Iva']['AlicIva'] as $iva) {
					$tasaIva = $tablasAfipRepository->getTasaIvaByCodAfip($iva['Id']);
					$precioSinIva = (float)$iva['BaseImp'];
					$precioConIva = (float)$iva['BaseImp'] + (float)$iva['Importe'];
					$arrMov = [
						'tasa_iva_id' => (int)$tasaIva['id'],
						'id' => $productoId,
						'nombre' => 'Comprobante recuperado desde AFIP',
						'cantidad' => 1,
						'precioUnitSnIva' => $precioSinIva,
						'precio' => $precioConIva,                   // tomo monto neto
						'montoIVA' => (float)$iva['Importe'],
						'montoNeto' => $precioSinIva,
						'tasa' => (($tasaIva['tasa'] / 100) + 1),
						'porcentaje_descuento' => 0,
						'monto_descuento' => 0,
						'impuesto_interno' => 0,
						'montoNoGravado' => 0
					];
					$items[] = $arrMov;
				}
			}

		}else{
			$arrMov = [
				'tasa_iva_id' => 5, // como NO viene le ponemos las del 21%
				'id' => $productoId,
				'nombre' => 'Comprobante recuperado desde AFIP',
				'cantidad' => 1,
				'precioUnitSnIva' => $this->totalNeto,          // tomo monto neto
				'precio' => $this->totalNeto,                   // tomo monto neto
				'montoIVA' => 0,
				'montoNeto' => $this->totalNeto,               // tomo monto neto
				'tasa' => 1,
				'porcentaje_descuento' => 0,
				'monto_descuento' => 0,
				'impuesto_interno' => 0,
				'montoNoGravado' => $this->totalNoGravado
			];
			$items[] = $arrMov;
		}

		$movimientos = $this->armoMovimFactura($items);

		return [
			'cabecera' => $cabecera,
			'movimientos' => $movimientos
		];
	}

	/**
	 * @return float
	 */
	public function getTotalFinal(): float
	{
		return $this->totalFinal;
	}

	/**
	 * @return float
	 */
	public function getTotalNeto(): float
	{
		return $this->totalNeto;
	}

	/**
	 * @return float
	 */
	public function getTotalIva(): float
	{
		return $this->totalIva;
	}

	/**
	 * @return float
	 */
	public function getTotalImpuestoInterno(): float
	{
		return $this->totalImpuestoInterno;
	}

	/**
	 * @return float
	 */
	public function getTotalNoGravado(): float
	{
		return $this->totalNoGravado;
	}

	/**
	 * @return array
	 */
	public function getItemsProcesados(): array
	{
		return $this->itemsProcesados;
	}

}
