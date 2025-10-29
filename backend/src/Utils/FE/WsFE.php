<?php

namespace App\Utils\FE;

#==============================================================================
use SimpleXMLElement;
use SoapClient;
use SoapFault;
use stdClass;
use Symfony\Component\HttpKernel\Exception\HttpException;

define ("WSDLWSAA", dirname(__FILE__) . "/lib/wsaa.wsdl");
define ("WSDLWSW", dirname(__FILE__) . "/lib/wsfe.wsdl");
define ("WSDLWSCDC", dirname(__FILE__) . "/lib/wscdc.wsdl");
define ("WSDLWSpersonaServiceA4", dirname(__FILE__) . "/lib/wspersonaServiceA4.wsdl");
define ("WSDLWSpersonaServiceA5", dirname(__FILE__) . "/lib/wspersonaServiceA5.wsdl");
define ("URLWSAA", "https://wsaahomo.afip.gov.ar/ws/services/LoginCms");
define ("URLWSW", "https://wswhomo.afip.gov.ar/wsfev1/service.asmx");
define ("URLWSCDC", "https://wswhomo.afip.gov.ar/WSCDC/service.asmx");
define ("URLWSpersonaServiceA4", "https://awshomo.afip.gov.ar/sr-padron/webservices/personaServiceA4");
define ("URLWSpersonaServiceA5", "https://awshomo.afip.gov.ar/sr-padron/webservices/personaServiceA5");

# Cambiar para produccion
define ("URLWSAA_PROD", "https://wsaa.afip.gov.ar/ws/services/LoginCms");
define ("URLWSW_PROD", "https://servicios1.afip.gov.ar/wsfev1/service.asmx");
define ("URLWSCDC_PROD", "https://servicios1.afip.gov.ar/WSCDC/service.asmx");
define ("URLWSpersonaServiceA4_PROD", "https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA4");
define ("URLWSpersonaServiceA5_PROD", "https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA5");
#==============================================================================



class WsFE
{
	private string $Token;
	private string $Sign;
	public int $CUIT;
	public string $ErrorCode;
	public string $ErrorDesc;

	public string $RespCAE;
	public string $RespVencimiento;
	public string $RespResultado;
	public int $RespUltNro;

	private object $client;
	private array $Request;
	private array $Lote;

	private string $certificado;
	private string $clave;
	private string $urlWsaa;

	private string $currentPath;
    private bool $verifyPeer = true;

	function __construct(){
        date_default_timezone_set('America/Argentina/Cordoba');
		$this->currentPath = dirname(__FILE__) . "/";
	}

	private function CreateTRA($SERVICE)
	{
		$TRA = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>' .
			'<loginTicketRequest version="1.0">' .
			'</loginTicketRequest>');
		$TRA->addChild('header');
		$TRA->header->addChild('uniqueId', date('U'));
		$TRA->header->addChild('generationTime', date('c', date('U') - 60 * 5));
		$TRA->header->addChild('expirationTime', date('c', date('U') + 3600 * 12));
		$TRA->addChild('service', $SERVICE);
		$TRA->asXML($this->currentPath.'tmp/TRA.xml');
	}

	private function SignTRA($certificado, $clave)
	{
		$STATUS = openssl_pkcs7_sign($this->currentPath . "tmp/TRA.xml", $this->currentPath . "tmp/TRA.tmp", "file://".$certificado,
			array("file://".$clave, ""),
			array(),
			!PKCS7_DETACHED
		);
		if (!$STATUS) {
			throw new HttpException(400, 'ERROR generating PKCS#7 signature');
			// exit("ERROR generating PKCS#7 signature\n");
		}
		$inf = fopen($this->currentPath . "tmp/TRA.tmp", "r");
		$i = 0;
		$CMS = "";
		while (!feof($inf)) {
			$buffer = fgets($inf);
			if ($i++ >= 4) {
				$CMS .= $buffer;
			}
		}
		fclose($inf);
		unlink($this->currentPath . "tmp/TRA.tmp");
		return $CMS;
	}

	private function CallWSAA($CMS, $urlWsaa)
	{
		$wsaaClient = new SoapClient(WSDLWSAA, array(
			'soap_version' => SOAP_1_2,
			'location' => $urlWsaa,
			'trace' => 1,
			'exceptions' => 0,
            stream_context_create(array('ssl'=> array('verify_peer'=>$this->verifyPeer,'verify_peer_name'=>$this->verifyPeer)))
		));
		$results = $wsaaClient->loginCms(array('in0' => $CMS));
//        file_put_contents("request-loginCms.xml", $wsaaClient->__getLastRequest());
//        file_put_contents("response-loginCms.xml", $wsaaClient->__getLastResponse());
		if (is_soap_fault($results)) {
			//exit("SOAP Fault: " . $results->faultcode . "\n" . $results->faultstring . "\n");
			$texto = "No se puede conectar con Servidor de AFIP: " . $results->faultcode . "\n" . $results->faultstring . "\n";
			throw new HttpException(400, $texto);
		}
		return $results->loginCmsReturn;
	}

	private function ProcesaErrores($Errors)
	{
		if (is_array($Errors->Err)){
			$this->ErrorCode = $Errors->Err[0]->Code;
			$this->ErrorDesc = mb_convert_encoding($Errors->Err[0]->Msg, 'ISO-8859-1', 'UTF-8');
		} else {
			$this->ErrorCode = $Errors->Err->Code;
			$this->ErrorDesc = mb_convert_encoding($Errors->Err->Msg, 'ISO-8859-1', 'UTF-8');
		}
	}

	/**
	 * @param string $certificado
	 * @param string $clave
	 * @param string $urlWsaa
	 * @param string $service
	 * @return bool
	 */
	function Login(string $certificado, string $clave, string $urlWsaa, string $service = "wsfe"): bool
	{
		$this->certificado = $certificado;
		$this->clave = $clave;
		$this->urlWsaa = $urlWsaa;

		if (!$this->loadCredentials($urlWsaa, $service)) {
			ini_set("soap.wsdl_cache_enabled", "1");
			if (!file_exists($this->certificado)) {
				//exit("Failed to open " . $certificado . "\n"); CARLOS
				throw new HttpException(400, "No se puede abrir el archivo (certificado) " . $this->certificado);
			}
			if (!file_exists($this->clave)) {
				//exit("Failed to open " . $clave . "\n");CARLOS
				throw new HttpException(400, "No se puede abrir el archivo (clave) " . $this->clave );
			}
			if (!file_exists(WSDLWSAA)) {
				//exit("Failed to open " . WSDLWSAA . "\n");CARLOS
				throw new HttpException(400, "Failed to open (WSDLWSAA)" . WSDLWSAA );
			}
			$SERVICE = $service;
			$this->CreateTRA($SERVICE);
			$CMS = $this->SignTRA($this->certificado, $this->clave);
			$TA = simplexml_load_string($this->CallWSAA($CMS, $urlWsaa));


			$this->Token = $TA->credentials->token;
			$this->Sign = $TA->credentials->sign;
			$this->saveCredentials($urlWsaa, $SERVICE);
		}
		return true;
	}

	/**
	 * @param string $urlWsaa
	 * @param string $service
	 * @return bool
	 */
	function loadCredentials(string $urlWsaa, string $service): bool
	{

		$filename = $this->currentPath."cache/".$this->CUIT.".cache";

		if (file_exists($filename)){
			$key = hash("md5", $urlWsaa.$service);
			$fcontent = file_get_contents($filename);
			if ($fcontent){
				$config = json_decode($fcontent);
				if (isset($config->$key)) {
					if ((time() - $config->$key->timeStamp) / 3600 < 10) {
						$this->Token = $config->$key->token;
						$this->Sign = $config->$key->sign;
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * @param string $urlWsaa
	 * @param string $service
	 * @return void
	 */
	function saveCredentials(string $urlWsaa,string $service): void
	{

		$filename = $this->currentPath."cache/".$this->CUIT.".cache";
		$key = hash("md5", $urlWsaa.$service);
		if (file_exists($filename)) {
			$fcontent = file_get_contents($filename);
		} else {
			$fcontent = false;
		}

		if ($fcontent){
			$config = json_decode($fcontent);
		} else {
			$config = new stdClass();
		}

		$config->$key = array("token"=> $this->Token,
			"sign" => $this->Sign,
			"timeStamp" => time());
		$file = fopen($filename, "w+");
		fwrite($file, json_encode($config));
		fclose($file);
	}

	/**
	 * @param int $PtoVta
	 * @param int $TipoComp
	 * @return bool
	 */
	function RecuperaLastCMP(int $PtoVta,int $TipoComp): bool
	{
		$results = $this->client->FECompUltimoAutorizado(
			array('Auth' => array('Token' => $this->Token,
				'Sign' => $this->Sign,
				'Cuit' => $this->CUIT),
				'PtoVta' => $PtoVta,
				'CbteTipo' => $TipoComp));
		if (isset($results->FECompUltimoAutorizadoResult->Errors)) {
			$this->procesaErrores($results->FECompUltimoAutorizadoResult->Errors);
			return false;
		} else if (is_soap_fault($results)){
			$this->ErrorCode = -1;
			$this->ErrorDesc = $results->faultstring;
			return false;
		}
		$this->RespUltNro = $results->FECompUltimoAutorizadoResult->CbteNro;

		return true;
	}

	/**
	 * @return void
	 */
	function Reset()
	{
		$this->Request = [];
		$this->Lote = [];
	}

    /**
     * @param int $Concepto
     * @param int $DocTipo
     * @param float $DocNro
     * @param float $CbteDesde
     * @param float $CbteHasta
     * @param string $CbteFch
     * @param float $ImpTotal
     * @param float $ImpTotalConc
     * @param float $ImpNeto
     * @param float $ImpOpEx
     * @param string $FchServDesde
     * @param string $FchServHasta
     * @param string $FchVtoPago
     * @param string $MonId
     * @param float $MonCotiz
     * @param string $CanMisMonExt
     * @param int $CondicionIVAReceptorId
     * @return void
     */
	function AgregaFactura(int $Concepto,int $DocTipo,float $DocNro,float $CbteDesde,float $CbteHasta,string $CbteFch,
						   float $ImpTotal, float $ImpTotalConc, float $ImpNeto, float $ImpOpEx, string $FchServDesde,
						   string $FchServHasta, string $FchVtoPago, string $MonId, float $MonCotiz, string $CanMisMonExt,
                           int $CondicionIVAReceptorId): void
    {
		$Request = [];
		$Request['Concepto'] = $Concepto;
		$Request['DocTipo'] = $DocTipo;
		$Request['DocNro'] = $DocNro;
		$Request['CbteDesde'] = $CbteDesde;
		$Request['CbteHasta'] = $CbteHasta;
		$Request['CbteFch'] = $CbteFch;
		$Request['ImpTotal'] = $ImpTotal;
		$Request['ImpTotConc'] = $ImpTotalConc;
		$Request['ImpNeto'] = $ImpNeto;
		$Request['ImpOpEx'] = $ImpOpEx;
		$Request['ImpTrib'] = 0;
		$Request['ImpIVA'] = 0;
		$Request['FchServDesde'] = $FchServDesde;
		$Request['FchServHasta'] = $FchServHasta;
		$Request['FchVtoPago'] = $FchVtoPago;
		$Request['MonId'] = $MonId;
		$Request['MonCotiz'] = $MonCotiz;
        $Request['CanMisMonExt'] = 'N';
        $Request['CondicionIVAReceptorId'] = $CondicionIVAReceptorId;

		$this->Lote['FECAEDetRequest'][] = $Request;
		end($this->Lote['FECAEDetRequest']);
		$this->Request = &$this->Lote['FECAEDetRequest'][key($this->Lote['FECAEDetRequest'])];
	}

	/**
	 * @param int $Id
	 * @param float $BaseImp
	 * @param float $Importe
	 * @return void
	 */
	function AgregaIVA(int $Id, float $BaseImp, float $Importe)
	{
		$AlicIva = array('Id' => $Id,
			'BaseImp' => $BaseImp,
			'Importe' => $Importe);
		if (!isset($this->Request['Iva'])) {
			$this->Request['Iva'] = array('AlicIva' => array());
		}

		$this->Request['Iva']['AlicIva'][] = $AlicIva;

		$this->Request['ImpIVA'] = 0;
		foreach ($this->Request['Iva']['AlicIva'] as $key => $value) {
			$this->Request['ImpIVA'] = $this->Request['ImpIVA'] + $value['Importe'];
		}
	}

	/**
	 * @param int $Id
	 * @param string $Desc
	 * @param float $BaseImp
	 * @param float $Alic
	 * @param float $Importe
	 * @return void
	 */
	function AgregaTributo(int $Id,string $Desc, float $BaseImp, float $Alic, float $Importe)
	{
		$Tributo = array('Id' => $Id,
			'Desc' => $Desc,
			'BaseImp' => $BaseImp,
			'Alic' => $Alic,
			'Importe' => $Importe);

		if (!isset($this->Request['Tributos'])) {
			$this->Request['Tributos'] = array('Tributo' => array());
		}

		$this->Request['Tributos']['Tributo'][] = $Tributo;

		$this->Request['ImpTrib'] = 0;
		foreach ($this->Request['Tributos']['Tributo'] as $key => $value) {
			$this->Request['ImpTrib'] = $this->Request['ImpTrib'] + $value['Importe'];
		}
	}

    /**
     * @param int $Tipo
     * @param int $PtoVta
     * @param int $Nro
     * @param int|null $Cuit
     * @param string|null $CbteFch
     * @return void
     */
	function AgregaCompAsoc(int $Tipo, int $PtoVta, int $Nro, int $Cuit = null, string $CbteFch = null): void
    {
		$CbteAsoc = array('Tipo' => $Tipo,
			'PtoVta' => $PtoVta,
			'Nro' => $Nro);

        if ($Cuit) {
            $CbteAsoc['Cuit'] = $Cuit;
        }
        if ($CbteFch) {
            $CbteAsoc['CbteFch'] = $CbteFch;
        }
		if (!isset($this->Request['CbtesAsoc'])) {
			$this->Request['CbtesAsoc'] = array('CbteAsoc' => array());
		}

		$this->Request['CbtesAsoc']['CbteAsoc'][] = $CbteAsoc;
	}

    /**
     * @param int $Id
     * @param string $Valor
     * @return void
     */
    function AgregaOpcional(int $Id, string $Valor): void
    {
        $Opcional = array('Id' => $Id,
            'Valor' => $Valor);

        if (!isset($this->Request['Opcionales'])) {
            $this->Request['Opcionales'] = array('Opcional' => array());
        }

        $this->Request['Opcionales']['Opcional'][] = $Opcional;
    }

	/**
	 * @param int $PtoVta
	 * @param int $TipoComp
	 * @return bool
	 */
	function Autorizar(int $PtoVta, int $TipoComp): bool
    {
		$Request = array('Auth' => array(
			'Token' => $this->Token,
			'Sign' => $this->Sign,
			'Cuit' => $this->CUIT),
			'FeCAEReq' => array(
				'FeCabReq' => array(
					'CantReg' => count($this->Lote['FECAEDetRequest']),
					'PtoVta' => $PtoVta,
					'CbteTipo' => $TipoComp),
				'FeDetReq' => $this->Lote
			)
		);
		$results = $this->client->FECAESolicitar($Request);
		if (isset($results->FECAESolicitarResult->Errors)) {
			$this->ProcesaErrores($results->FECAESolicitarResult->Errors);
			return false;
		}
		if (is_soap_fault($results)){
			$this->ErrorCode = -1;
			$this->ErrorDesc = $results->faultstring;
			return false;
		}

		$this->RespResultado = $results->FECAESolicitarResult->FeCabResp->Resultado;

		if ($this->RespResultado == "A") {
			$this->RespCAE = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAE;
			$this->RespVencimiento = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAEFchVto;
		}


		if (isset($results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Observaciones)){
			if (is_array($results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Observaciones->Obs)){
				$this->ErrorCode = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Observaciones->Obs[0]->Code;
				$this->ErrorDesc = mb_convert_encoding($results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Observaciones->Obs[0]->Msg, 'ISO-8859-1', 'UTF-8');
			} else {
				$this->ErrorCode = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Observaciones->Obs->Code;
				$this->ErrorDesc = mb_convert_encoding($results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Observaciones->Obs->Msg, 'ISO-8859-1', 'UTF-8');
			}
		}

		return $this->RespResultado == "A";
	}

	/**
	 * @param int $TipoComp
	 * @param int $PtoVta
	 * @param float $nro
	 * @param $cbte
	 * @return bool
	 */
	function CmpConsultar(int $TipoComp, int $PtoVta,float $nro, &$cbte): bool
	{
		$results = $this->client->FECompConsultar(
			array('Auth' => array('Token' => $this->Token,
				'Sign' => $this->Sign,
				'Cuit' => $this->CUIT),
				'FeCompConsReq' => array('PtoVta' => $PtoVta,
					'CbteTipo' => $TipoComp,
					'CbteNro' => $nro)
			)
		);
		if (isset($results->FECompConsultarResult->Errors)) {
			$this->procesaErrores($results->FECompConsultarResult->Errors);
			return false;
		}
		$cbte = $results->FECompConsultarResult->ResultGet;

		return true;
	}

	/**
	 * @return mixed
	 */
    function getXMLRequest(): mixed
    {
        return $this->client->__getLastRequest();
    }

	/**
	 * @param string $URL
	 * @return void
	 * @throws SoapFault
	 */
	function setURL(string $URL): void
    {
		$context = stream_context_create(
			array(
				'ssl' => array(
                    'ciphers' => 'AES256-SHA',
                    'verify_peer'=>$this->verifyPeer,
                    'verify_peer_name'=>$this->verifyPeer,
                    'allow_self_signed'=>!$this->verifyPeer
                )
			)
		);

		$this->client = new SoapClient(WSDLWSW, array(
				'soap_version'=> SOAP_1_2,
				'location' => $URL,
				'trace' => 1,
				'exceptions' => 0,
				'stream_context' => $context
			)
		);
	}


	/**
	 * @param string $Periodo
	 * @param string $Orden
	 * @param string $CAE
	 * @param string $FchVigDesde
	 * @param string $FchVigHasta
	 * @param string $FchTopeInf
	 * @param string $FchProceso
	 * @return bool
	 */
	function CAEASolicitar(string $Periodo, string $Orden, string &$CAE, string &$FchVigDesde, string &$FchVigHasta,
						   string &$FchTopeInf, string &$FchProceso): bool
	{
		$results = $this->client->FECAEASolicitar(
			array('Auth' =>
				array('Token' => $this->Token,
					'Sign' => $this->Sign,
					'Cuit' => $this->CUIT
				),
				'Periodo' => $Periodo,
				'Orden' => $Orden
			)
		);

		if (isset($results->FECAEASolicitarResult->Errors)) {
			$this->procesaErrores($results->FECAEASolicitarResult->Errors);
			return false;
		};

		$CAE = $results->FECAEASolicitarResult->ResultGet->CAEA;
		$FchVigDesde = $results->FECAEASolicitarResult->ResultGet->FchVigDesde;
		$FchVigHasta = $results->FECAEASolicitarResult->ResultGet->FchVigHasta;
		$FchTopeInf = $results->FECAEASolicitarResult->ResultGet->FchTopeInf;
		$FchProceso = $results->FECAEASolicitarResult->ResultGet->FchProceso;

		return true;
	}

	/**
	 * @param string $Periodo
	 * @param string $Orden
	 * @param string $CAE
	 * @param string $FchVigDesde
	 * @param string $FchVigHasta
	 * @param string $FchTopeInf
	 * @param string $FchProceso
	 * @return bool
	 */
	function CAEAConsultar(string $Periodo, string $Orden, string &$CAE, string &$FchVigDesde, string &$FchVigHasta,
						   string &$FchTopeInf, string &$FchProceso): bool
	{
		$results = $this->client->FECAEAConsultar(
			array('Auth' =>
				array('Token' => $this->Token,
					'Sign' => $this->Sign,
					'Cuit' => $this->CUIT
				),
				'Periodo' => $Periodo,
				'Orden' => $Orden
			)
		);

		if (isset($results->FECAEAConsultarResult->Errors)) {
			$this->procesaErrores($results->FECAEAConsultarResult->Errors);
			return false;
		};
		$CAE = $results->FECAEAConsultarResult->ResultGet->CAEA;

		$FchVigDesde = $results->FECAEAConsultarResult->ResultGet->FchVigDesde;
		$FchVigHasta = $results->FECAEAConsultarResult->ResultGet->FchVigHasta;
		$FchTopeInf = $results->FECAEAConsultarResult->ResultGet->FchTopeInf;
		$FchProceso = $results->FECAEAConsultarResult->ResultGet->FchProceso;

		return true;
	}

	/**
	 * @param int $ptoVenta
	 * @param int $CbteTipo
	 * @param string $CAE
	 * @return bool
	 */
	function CAEAInformar(int $ptoVenta,int $CbteTipo, string $CAE): bool
	{
		$this->Request['CAEA'] = $CAE;
		$request = array('Auth' =>
			array('Token' => $this->Token,
				'Sign' => $this->Sign,
				'Cuit' => $this->CUIT
			),
			'FeCAEARegInfReq' => array(
				'FeCabReq' => array(
					'CantReg' => 1,
					'PtoVta' => $ptoVenta,
					'CbteTipo' => $CbteTipo
				),
				'FeDetReq' => array($this->Request)
			)

		);

		$results = $this->client->FECAEARegInformativo($request);

		if (isset($results->FECAEARegInformativoResult->Errors)) {
			$this->procesaErrores($results->FECAEARegInformativoResult->Errors);
			return false;
		};
		return true;
	}

	/**
	 * @param string $Prefix
	 * @param string $DNI
	 * @return string
	 */
	function AddChecksum(string $Prefix,string $DNI): string
	{
		$DNIStr = $Prefix.$DNI;
		$Serie = 2;
		$Acc = 0;
		for ($i = strlen($DNIStr); $i > 0; $i--){
			$Acc = $Acc + intval(substr($DNIStr, $i-1,1)) * $Serie;
			if ($Serie == 7) {
				$Serie = 2;
			} else {
				$Serie = $Serie + 1;
			}
		}
		$Modulo = 11 - ($Acc % 11);
		/* if ($Modulo == 1) {
			//$Modulo = 0;
		}*/
		return $DNIStr.$Modulo;
	}

	/**
	 * @param float $CUIT
	 * @param string $Constancia
	 * @param string $URL
	 * @return bool
	 * @throws SoapFault
	 */
	function InternoConsultarConstancia(float $CUIT,string &$Constancia, string $URL): bool
	{

		if ($this->Login($this->certificado, $this->clave, $this->urlWsaa, "ws_sr_constancia_inscripcion")) {
			$context = stream_context_create(
				array(
					'ssl' => array(
						'ciphers' => 'DHE-RSA-AES256-SHA:DHE-DSS-AES256-SHA:AES256-SHA:KRB5-DES-CBC3-MD5:KRB5-DES-CBC3-SHA:EDH-RSA-DES-CBC3-SHA:EDH-DSS-DES-CBC3-SHA:DES-CBC3-SHA:DES-CBC3-MD5:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA:AES128-SHA:RC2-CBC-MD5:KRB5-RC4-MD5:KRB5-RC4-SHA:RC4-SHA:RC4-MD5:RC4-MD5:KRB5-DES-CBC-MD5:KRB5-DES-CBC-SHA:EDH-RSA-DES-CBC-SHA:EDH-DSS-DES-CBC-SHA:DES-CBC-SHA:DES-CBC-MD5:EXP-KRB5-RC2-CBC-MD5:EXP-KRB5-DES-CBC-MD5:EXP-KRB5-RC2-CBC-SHA:EXP-KRB5-DES-CBC-SHA:EXP-EDH-RSA-DES-CBC-SHA:EXP-EDH-DSS-DES-CBC-SHA:EXP-DES-CBC-SHA:EXP-RC2-CBC-MD5:EXP-RC2-CBC-MD5:EXP-KRB5-RC4-MD5:EXP-KRB5-RC4-SHA:EXP-RC4-MD5:EXP-RC4-MD5',
					)
				)
			);

			$consultacuit = new SoapClient(WSDLWSpersonaServiceA5, array(
					'soap_version' => SOAP_1_1,
					'location' => $URL,
					'trace' => 1,
					'exceptions' => 0,
					'stream_context' => $context
				)
			);

			$Request = array(
				'token' => $this->Token,
				'sign' => $this->Sign,
				'cuitRepresentada' => $this->CUIT,
				'idPersona' => $CUIT
			);

			$results = $consultacuit->getPersona($Request);

			if (is_soap_fault($results)) {
				$this->ErrorCode = -1;
				$this->ErrorDesc = $results->faultstring;
				return false;
			}

			$Constancia = $results->personaReturn;

			return true;
		}
		return false;
	}

	/**
	 * @param float $CUIT
	 * @param string $DatosPersona
	 * @param string $URL
	 * @return bool
	 * @throws SoapFault
	 */
	function InternoConsultarCUIT(float $CUIT,string &$DatosPersona, string $URL): bool
	{
		$this->ErrorCode = 0;
		$this->ErrorDesc = '';
		$constancia = '';

		if (!$this->InternoConsultarConstancia($CUIT, $constancia, $URL))
		{
			return false;
		} else {
			$DatosPersona = $constancia;
			$DatosPersona->Observaciones = array();
			if (isset($DatosPersona->errorConstancia) || isset($DatosPersona->errorMonotributo) or isset($DatosPersona->errorRegimenGeneral)) {

				if (isset($DatosPersona->errorConstancia))
					array_push($DatosPersona->Observaciones, $DatosPersona->errorConstancia->error);
				if (isset($DatosPersona->errorMonotributo))
					array_push($DatosPersona->Observaciones, $DatosPersona->errorMonotributo->error);
				if (isset($DatosPersona->errorRegimenGeneral))
					array_push($DatosPersona->Observaciones, $DatosPersona->errorRegimenGeneral->error);
			};
		}


		$DatosPersona->CondicionIVADesc = 'Consumidor Final';
		if (isset($DatosPersona->datosMonotributo)) {
			$DatosPersona->CondicionIVADesc = 'Monotributo';
		} else if (isset($DatosPersona->datosRegimenGeneral)) {
			$impuestos = $DatosPersona->datosRegimenGeneral->impuesto;
			if (isset($impuestos)) {
				foreach ($impuestos as $impuesto) {
					if ($impuesto->idImpuesto == 30) {
						$DatosPersona->CondicionIVADesc = 'Responsable Inscripto';
						break;
					} elseif ($impuesto->idImpuesto == 32) {
						$DatosPersona->CondicionIVADesc = 'Exento';
						break;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @param float $CUIT
	 * @param string $DatosPersona
	 * @param string $URL
	 * @return bool
	 * @throws SoapFault
	 */
	function ConsultarCUIT(float $CUIT, string &$DatosPersona, string $URL): bool
	{
		$CuitStr = $CUIT;

		if (strlen($CuitStr) < 11) {

			if (strlen($CuitStr) < 8) {
				for ($i = strlen($CuitStr) + 1; $i < 9; $i++){
					$CuitStr = '0' . $CuitStr;
				}
			}
			$Result = $this->InternoConsultarCUIT(doubleval($this->AddChecksum(20, $CuitStr)), $DatosPersona, $URL);
			if (!$Result)
				$Result = $this->InternoConsultarCUIT(doubleval($this->AddChecksum(27, $CuitStr)), $DatosPersona, $URL);
			if (!$Result)
				$Result = $this->InternoConsultarCUIT(doubleval($this->AddChecksum(23, $CuitStr)), $DatosPersona, $URL);
			if (!$Result)
				$Result = $this->InternoConsultarCUIT(doubleval($this->AddChecksum(24, $CuitStr)), $DatosPersona, $URL);
		}
		else {
			$Result = $this->InternoConsultarCUIT(doubleval($CuitStr), $DatosPersona, $URL);
		}
		return $Result;
	}


	/**
	 * @param string $CbteModo
	 * @param float $CuitEmisor
	 * @param int $PtoVta
	 * @param int $CbteTipo
	 * @param int $CbteNro
	 * @param string $CbteFch
	 * @param float $ImpTotal
	 * @param string $CodAutorizacion
	 * @param int $DocTipoReceptor
	 * @param int $DocNroReceptor
	 * @param string $FchProceso
	 * @return bool
	 * @throws SoapFault
	 */
	function ComprobanteConstatar( string $CbteModo,float $CuitEmisor,int $PtoVta,int $CbteTipo,int $CbteNro,string $CbteFch,float $ImpTotal,
								  string $CodAutorizacion, int $DocTipoReceptor,int $DocNroReceptor, string &$FchProceso): bool
	{

		$this->ErrorCode = 0;
		$this->ErrorDesc = '';

		$context = stream_context_create(
			array(
				'ssl' => array(
					'ciphers' => 'DHE-RSA-AES256-SHA:DHE-DSS-AES256-SHA:AES256-SHA:KRB5-DES-CBC3-MD5:KRB5-DES-CBC3-SHA:EDH-RSA-DES-CBC3-SHA:EDH-DSS-DES-CBC3-SHA:DES-CBC3-SHA:DES-CBC3-MD5:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA:AES128-SHA:RC2-CBC-MD5:KRB5-RC4-MD5:KRB5-RC4-SHA:RC4-SHA:RC4-MD5:RC4-MD5:KRB5-DES-CBC-MD5:KRB5-DES-CBC-SHA:EDH-RSA-DES-CBC-SHA:EDH-DSS-DES-CBC-SHA:DES-CBC-SHA:DES-CBC-MD5:EXP-KRB5-RC2-CBC-MD5:EXP-KRB5-DES-CBC-MD5:EXP-KRB5-RC2-CBC-SHA:EXP-KRB5-DES-CBC-SHA:EXP-EDH-RSA-DES-CBC-SHA:EXP-EDH-DSS-DES-CBC-SHA:EXP-DES-CBC-SHA:EXP-RC2-CBC-MD5:EXP-RC2-CBC-MD5:EXP-KRB5-RC4-MD5:EXP-KRB5-RC4-SHA:EXP-RC4-MD5:EXP-RC4-MD5',
				)
			)
		);

		$clientConstatar = new SoapClient(WSDLWSCDC, array(
				'soap_version'=> SOAP_1_2,
				'location' => URLWSCDC,
				'trace' => 1,
				'exceptions' => 0,
				'stream_context' => $context
			)
		);

		$Request = array('Auth' => array(
			'Token' => $this->Token,
			'Sign' => $this->Sign,
			'Cuit' => $this->CUIT),
			'CmpReq' => array(
				'CbteModo' => $CbteModo,
				'CuitEmisor' => $CuitEmisor,
				'PtoVta' => $PtoVta,
				'CbteTipo' => $CbteTipo,
				'CbteNro' => $CbteNro,
				'CbteFch' => $CbteFch,
				'ImpTotal' => $ImpTotal,
				'CodAutorizacion' => $CodAutorizacion,
				'DocTipoReceptor' => $DocTipoReceptor,
				'DocNroReceptor' => $DocNroReceptor
			)
		);

		$results = $clientConstatar->ComprobanteConstatar($Request);
		if (isset($results->ComprobanteConstatarResult->Errors)) {
			$this->ProcesaErrores($results->ComprobanteConstatarResult->Errors);
			return false;
		}
		if (is_soap_fault($results)){
			$this->ErrorCode = -1;
			$this->ErrorDesc = $results->faultstring;
			return false;
		}

		$RespResultado = $results->ComprobanteConstatarResult->Resultado;

		if (isset($results->ComprobanteConstatarResult->Observaciones)){
			if (is_array($results->ComprobanteConstatarResult->Observaciones->Obs)){
				$this->ErrorCode = $results->ComprobanteConstatarResult->Observaciones->Obs[0]->Code;
				$this->ErrorDesc = mb_convert_encoding($results->ComprobanteConstatarResult->Observaciones->Obs[0]->Msg, 'ISO-8859-1', 'UTF-8');
			} else {
				$this->ErrorCode = $results->ComprobanteConstatarResult->Observaciones->Obs->Code;
				$this->ErrorDesc = mb_convert_encoding($results->ComprobanteConstatarResult->Observaciones->Obs->Msg, 'ISO-8859-1', 'UTF-8');
			}
		}

		if ($RespResultado == "A"){
			$FchProceso = sprintf("%s-%s-%s %s:%s:%s",
				substr($results->ComprobanteConstatarResult->FchProceso, 0, 4),
				substr($results->ComprobanteConstatarResult->FchProceso, 4, 2),
				substr($results->ComprobanteConstatarResult->FchProceso, 6, 2),
				substr($results->ComprobanteConstatarResult->FchProceso, 8, 2),
				substr($results->ComprobanteConstatarResult->FchProceso, 10, 2),
				substr($results->ComprobanteConstatarResult->FchProceso, 12, 2));
		}

		return $RespResultado == "A";
	}

}
