<?php

namespace App\Utils\FE;

use DateInterval;
use DateTime;
use Exception;
use SoapFault;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Obtiene todos los datos y genera un comprobante electrónico
 */
class ComprobanteFE
{

	// variables usadas para calcular los totales de la factura
	protected float $totalFinal = 0.0;
	protected float $totalNeto  = 0.0;
	protected float $totalIva  = 0.0;
	protected float $totalImpuestoInterno  = 0.0;
	protected float $totalNoGravado  = 0.0;
	protected array $itemsProcesados = [];

	// variables de entidades (tablas)
	protected array $cliente = [];
	protected array $puntoVenta = [];
	protected array $empresa = [];
	protected array $tipoComprobante = [];

	protected array $postValues = [];

	protected string $pathUrlWsFE;

	private WsFE $wsfe;

	public function __construct()
	{
		$this->wsfe = new WsFE();
	}

	/**
	 * Setea los datos y envía los datos de un comprobante al WS de AFIP FE
	 * @return array
	 * @throws SoapFault
	 * @throws Exception
	 */
	public function generar(): array
	{
		$pathCertificado = __DIR__ . '/../../../public/certificados/' . $this->empresa['archivo_certificado'];
		$pathClave = __DIR__  . '/../../../public/certificados/' . $this->empresa['archivo_clave'];

		$tipoComprobante = (int)$this->tipoComprobante['tipo_comprobante_afip'];
		$puntoVentaNumero = (int)$this->puntoVenta['numero'];

		$this->verificoCertificados();

		$this->seteoUrlFe();

		if ($this->wsfe->Login($pathCertificado, $pathClave, $this->pathUrlWsFE)) {
			if (!$this->wsfe->RecuperaLastCMP($puntoVentaNumero, $tipoComprobante)) {
				throw new HttpException(400,'Servicio de ARCA no disponible. Intente más tarde: ' . $this->wsfe->ErrorDesc);
			}
			$this->wsfe->Reset();
			$nroFact = $this->wsfe->RespUltNro + 1;

			$this->metodoAgregaFactura($nroFact);
			$this->metodoAgregaIVA();
			$this->metodoAgregaTributo();
			$this->metodoAgregarComprobantesAsociados();

			try {
				if ($this->wsfe->Autorizar($puntoVentaNumero, $tipoComprobante)) {
					return $this->adaptoDatosCabeceraFactura($this->wsfe->RespCAE, $this->wsfe->RespVencimiento, $nroFact);
				} else {
					throw new HttpException(400,'Error en Factura electrónica (autorizar): ' . $this->wsfe->ErrorDesc);
				}
			} catch (Exception $e){
				if ($this->wsfe->CmpConsultar($tipoComprobante, $puntoVentaNumero, $nroFact, $cbte)){
					return $this->adaptoDatosCabeceraFactura($cbte->CodAutorizacion, $cbte->FchVto, $nroFact);
				} else {
					// si dio error en autorizar y ahora vuelve a dar error en consultar muestro el primer error
					throw new HttpException(400, $e->getMessage());
				}
			}
		} else {
			throw new HttpException(400,'Error en Factura electrónica (login): ' . $this->wsfe->ErrorDesc);
		}
	}

	/**
	 * Creo un array con los datos devueltos para generar la cabecera de la factura
	 * @param string $cae
	 * @param string $vencimientoCae
	 * @param int $ntoFactura
	 * @return array
	 * @throws Exception
	 */
	private function adaptoDatosCabeceraFactura(string $cae, string $vencimientoCae, int $ntoFactura): array
	{
		$totalIva = round($this->totalIva,2);
		$fechaVtoFact = $this->seteoFechaVtoFc('0');
		return [
			'cliente_id'  => $this->cliente['id'],
			'condicion_venta_id' => (int)$this->postValues['comprobante']['condicion_venta_id'],
			'fecha' => $this->postValues['comprobante']['fecha'],
			'punto_venta' => (int)$this->puntoVenta['numero'],
			'total_iva' => $totalIva,
			'tipo_comprobante_id' => (int)$this->tipoComprobante['id'],
			'cae' => $cae,
			'fecha_vencimiento_cae' => $vencimientoCae,
			'numero' => $ntoFactura,
			'fecha_vencimiento_factura' => $fechaVtoFact->format('Y-m-d'),
			'impuesto_interno' => $this->totalImpuestoInterno,
			'total_no_gravado' => $this->totalNoGravado,
			'tipo_comprobante_asociado' => $this->postValues['comprobante']['tipo_comprobante_asociado'],
			'punto_vta_comprobante_asociado' => $this->postValues['comprobante']['punto_vta_comprobante_asociado'],
			'numero_comprobante_asociado' => $this->postValues['comprobante']['numero_comprobante_asociado'],
			'cajero_id' => $this->postValues['comprobante']['cajero_id']
		];
	}


	/**
	 * Informa al WS el comprobante asociado
	 * @return void
	 */
	private function metodoAgregarComprobantesAsociados(): void
	{
		if (preg_match('/CREDITO|DEBITO/i', $this->tipoComprobante['nombre'])) {
			$this->wsfe->AgregaCompAsoc(
				$this->postValues['comprobante']['tipo_comprobante_asociado'],
				$this->postValues['comprobante']['punto_vta_comprobante_asociado'],
				$this->postValues['comprobante']['numero_comprobante_asociado']
			);
		};
	}

	/**
	 * Informa al WS los tributos agregados (Impuesto interno)
	 * @return void
	 */
	private function metodoAgregaTributo(): void
	{
		if ($this->totalImpuestoInterno > 0) {
			$this->wsfe->AgregaTributo(
				4,
				'Impuestos Internos',
				round($this->totalNeto, 2),
				round((($this->totalImpuestoInterno / $this->totalNeto)  * 100 ), 2),
				round($this->totalImpuestoInterno,2)
			);
		}
	}



	/**
	 * Agrega los ítems agrupados por % de IVA al WS. Método AgregarIVA del WS
	 * @return void
	 */
	private function metodoAgregaIVA(): void
	{
		$arrComSinIVA = [2,3,4,5];
		$categoriaIVAEmpresa = (int)$this->empresa['categoria_iva_id'];
		$itemsAgrupados = $this->agrupoPorTasaIVA($this->itemsProcesados);
		if (!in_array($categoriaIVAEmpresa, $arrComSinIVA)) {
            // var_dump('procesados');
            // var_dump($this->itemsProcesados);
			foreach ($itemsAgrupados as $itemIva) {
				// solo detallo el iva si la empresa es RI (ver array) y si el neto es > 0
				$montoNeto = round(floatval($itemIva['montoNeto']), 2);
                // var_dump($montoNeto);
				$tasa = (int)$itemIva['tasa'];
				if ($montoNeto > 0) {
					$this->wsfe->AgregaIVA($tasa,
						$montoNeto,
						round(floatval($itemIva['montoIVA']), 2));
				}
			}
		}
	}

	/**
	 * Genera los datos del método AgregaFactura del WS
	 * @param int $nroFact
	 * @return void
	 * @throws Exception
	 */
	private function metodoAgregaFactura(int $nroFact): void
	{
		$arrComSinIVA = [2,3,4,5];
		$categoriaIVAEmpresa = (int)$this->empresa['categoria_iva_id'];

		$fechaComp = DateTime::createFromFormat('Y-m-d', $this->postValues['comprobante']['fecha']);
		$fechaVtoFact = $this->seteoFechaVtoFc('0');
		$concepto = (int)$this->empresa['concepto_id'];                         //si es producto/servicio
		$imptotal = round($this->totalFinal,2);
		$totalImpInterno = $this->totalImpuestoInterno;
		if (in_array($categoriaIVAEmpresa, $arrComSinIVA)) {
			$ImpNeto = $imptotal - $totalImpInterno;
		}else{
			$ImpNeto = round($this->totalNeto, 2);
		}

		$fechaServicio = '';                                  					// si es servicio tiene que pasarle las fechas de vto
		if ($concepto > 1 && $concepto < 4 ) {
			$fechaServicio = $fechaVtoFact->format('Ymd');
		}

		$tipoDoc = 80; // cuit
		$docNro = $this->cliente['numero_documento'];
		// analizo si es cliente consumidor final - JIRA 554
		if ( intval($this->cliente['tipo_documento']) === 9) {
			$tipoDoc = 99;
			$docNro = 0;
		}else{
			if (strlen($docNro) < 11 ) {
				$tipoDoc = 96; // dni
			}
		}
		$impOpEx = 0.0;

		$this->wsfe->AgregaFactura(
			$concepto,
			$tipoDoc,
			doubleval($docNro),
			$nroFact,
			$nroFact,
			$fechaComp->format('Ymd'),
			$imptotal,
			$this->totalNoGravado,
			$ImpNeto,
			$impOpEx,
			$fechaServicio,
			$fechaServicio,
			$fechaServicio,
			"PES",
			1,
            '',
            $this->cliente['cateogriaIvaReceptor']
		);
	}


	/**
	 * Función que agrupa el array de items por tasa de IVA; luego recorre por cada tasa el array
	 * nuevo y acumula los montos para agregarIVA de FE
	 * @param array $itemsComprob
	 * @return array
	 */
	private function agrupoPorTasaIVA(array $itemsComprob): array
	{
		$arrAgrup = [];
		$arrFinal = [];
		foreach($itemsComprob as $val) {
			if (intval($val['tasaAfip']) === 2) {
				continue;   // saco todos los ítems que son exentos
			}
			$arrAgrup[$val['tasaAfip']][] = $val;
		}

		foreach ($arrAgrup as $itemIvaAgrup){
			$tasa       = 0;
			$montoIva   = 0;
			$montoNeto  = 0;
			foreach ($itemIvaAgrup as $itemsIva){
				$tasa       = $itemsIva['tasaAfip'];
				$montoIva   += (float)$itemsIva['montoIVA'];
				$montoNeto  += (float)($itemsIva['montoNeto']);
			}
			$arro = ['tasa' => $tasa, 'montoIVA' => $montoIva, 'montoNeto' => $montoNeto];
			$arrFinal[] = $arro;
		}
		return $arrFinal;
	}

	/**
	 * @param int $dias
	 * @return DateTime
	 * @throws Exception
	 */
	private function seteoFechaVtoFc(int $dias): DateTime
	{
		$diaHoy = new DateTime();
		$expresion = 'P' . $dias . 'D';
		$newDate =  $diaHoy->add(new DateInterval($expresion));
		return $newDate;
	}

	/**
	 * Setea la url de los servidores de AFIP y verifica qué empresa quiere facturar (usamos pruebas para 1 y 81)
	 * @return void
	 * @throws SoapFault
	 */
	private function seteoUrlFe(): void
	{
		//  si el cliente es 1 u 81 hago facturas de prueba
		if (intval($this->empresa['id']) === 1) {
			$this->wsfe->CUIT = doubleval(20245753501);   //cuit Claudio
			$this->wsfe->setURL(URLWSW);
			$this->pathUrlWsFE = URLWSAA;
		}elseif (intval($this->empresa['id']) === 81){
			$this->wsfe->CUIT     = doubleval($this->empresa['cuit']);
			$this->wsfe->setURL(URLWSW);
			$this->pathUrlWsFE = URLWSAA;
		}else{
			$this->wsfe->CUIT     = doubleval($this->empresa['cuit']);
			$this->wsfe->setURL(URLWSW_PROD);
			$this->pathUrlWsFE = URLWSAA_PROD;
		}
	}


	/**
	 * Verifico tener los certificados y el tipo de comprobante setedo
	 * @return void
	 */
	private function verificoCertificados (): void
	{
		$tipoComprobante = (int)$this->tipoComprobante['tipo_comprobante_afip'];
		if (strlen($this->empresa['archivo_certificado']) === 0 ||
			strlen($this->empresa['archivo_clave']) === 0 ||
			$tipoComprobante === 0) {
			throw new HttpException(400, 'Error en Factura electrónica: Asegúrese de tener el punto 
			de venta electrónico, los certificados/llaves y el tipo de comprobante configurado adecuadamente');
		}
	}

	/**
	 * @param float $totalFinal
	 * @return $this
	 */
	public function setTotalFinal(float $totalFinal): ComprobanteFE
	{
		$this->totalFinal = $totalFinal;
		return $this;
	}

	public function setTotalNeto(float $totalNeto): ComprobanteFE
	{
		$this->totalNeto = $totalNeto;
		return $this;
	}

	public function setTotalIva(float $totalIva): ComprobanteFE
	{
		$this->totalIva = $totalIva;
		return $this;
	}

	public function setTotalImpuestoInterno(float $totalImpuestoInterno): ComprobanteFE
	{
		$this->totalImpuestoInterno = $totalImpuestoInterno;
		return $this;
	}

	public function setTotalNoGravado(float $totalNoGravado): ComprobanteFE
	{
		$this->totalNoGravado = $totalNoGravado;
		return $this;
	}

	public function setItemsProcesados(array $itemsProcesados): ComprobanteFE
	{
		$this->itemsProcesados = $itemsProcesados;
		return $this;
	}

	public function setCliente(array $cliente): ComprobanteFE
	{
		$this->cliente = $cliente;
		return $this;
	}

	public function setPuntoVenta(array $puntoVenta): ComprobanteFE
	{
		$this->puntoVenta = $puntoVenta;
		return $this;
	}

	public function setEmpresa(array $empresa): ComprobanteFE
	{
		$this->empresa = $empresa;
		return $this;
	}

	public function setTipoComprobante(array $tipoComprobante): ComprobanteFE
	{
		$this->tipoComprobante = $tipoComprobante;
		return $this;
	}

	public function setPostValues(array $postValues): ComprobanteFE
	{
		$this->postValues = $postValues;
		return $this;
	}
}
