<?php

namespace App\Service\FE;

use App\Repository\Shared\TablasAFIPRepository;
use App\Utils\FE\WsFE;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use SoapFault;
use Symfony\Component\HttpKernel\Exception\HttpException;
/**
 * Consume el WS de AFIP llamado ws_sr_constancia_inscripcion y devuelve todos los datos de un asociado por su CUIT
 */
class GetConstanciaInscripcion
{

	private const URLWSAA = 'https://wsaa.afip.gov.ar/ws/services/LoginCms';

	private Connection $connection;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * @param int $cuit
	 * @return array
	 * @throws Exception
	 * @throws SoapFault
	 */
	public function getByCuit(int $cuit): array
	{
		$pathCertificado = __DIR__ .'/../../../public/certificados/claudio.crt';
		$pathClave = __DIR__ .'/../../../public/certificados/claudio.key';
		$wsfe = new WsFE();
		$wsfe->CUIT = 20245753501;
		$datosPersona = '';
		if ($wsfe->Login($pathCertificado, $pathClave, self::URLWSAA, "ws_sr_constancia_inscripcion")){
			if ($wsfe->ConsultarCUIT($cuit, $datosPersona, URLWSpersonaServiceA5_PROD)) {
				$devo = $this->parseoDatosConstancia((object)$datosPersona);
				//si Afip tiene el cuit/cuil de la persona se la asigno por mas que envíe un DNI
				$devo['cuit'] = $cuit;
				if (isset($devo['idPersona'])){
					$devo['cuit'] = $devo['idPersona'];
					unset($devo['idPersona']);
				}
			} else {
				throw new HttpException(400, str_replace('Id', 'Dni/Cuit', $wsfe->ErrorDesc));
			}
		} else {
			$errorTxt = "Error de Conexión a AFIP: " . $wsfe->ErrorDesc;
			throw new HttpException(400, $errorTxt);
		}
		return $devo;
	}


	/**
	 * @param object $objDatos
	 * @return array
	 * @throws Exception
	 */
	private function parseoDatosConstancia(object $objDatos): array
	{
		$resu   = [];
		$domi   = [];
		$nombre = '';
		if (isset($objDatos->errorConstancia->error)) {
			if ($objDatos->errorConstancia->error === 'La clave ingresada no es una CUIT') {                 			// es un DNI o un CUIL
				$resu['nombre']             = $objDatos->errorConstancia->apellido;
				$domi['direccion']          = 'Domicilio genérico';
				$domi['localidad']          = 'Localidad genérica';
				$domi['codigo_postal']      = '1000';
				$domi['provincia_afip']     = 1;
				$domi['provincia_nombre']   = '';
				$resu['domicilio']          = $domi;
				$resu['condiIva']           = $objDatos->CondicionIVADesc;
				$resu['categoria_iva_id'] = $this->retornoCategoriaIvaId($resu['condiIva']);
				return $resu;
			}else{
				$erroTxt = 'Error informado por AFIP: ';
				if (is_array($objDatos->errorConstancia->error)){
					foreach ($objDatos->errorConstancia->error as $item){
						$erroTxt .= $item;
					}
				}else{
					$erroTxt .= $objDatos->errorConstancia->error;
				}
				throw new HttpException(400, $erroTxt);
			}

		}
		if (isset($objDatos->datosGenerales->tipoPersona)) {
			if ($objDatos->datosGenerales->tipoPersona === 'FISICA'){
				$nombre = $objDatos->datosGenerales->apellido . ' ' . $objDatos->datosGenerales->nombre;
			}else if ($objDatos->datosGenerales->tipoPersona === 'JURIDICA'){
				$nombre = $objDatos->datosGenerales->razonSocial;
			}
			if (isset($objDatos->datosGenerales->domicilioFiscal->localidad)){
				$domi['localidad'] = $objDatos->datosGenerales->domicilioFiscal->localidad;
			}else{
				$domi['localidad'] = 'CABA';
			}
			$domi['direccion']          = $objDatos->datosGenerales->domicilioFiscal->direccion;
			$domi['codigo_postal']      = $objDatos->datosGenerales->domicilioFiscal->codPostal;
			$domi['provincia_afip']     = $objDatos->datosGenerales->domicilioFiscal->idProvincia;
			$domi['provincia_nombre']   = $objDatos->datosGenerales->domicilioFiscal->descripcionProvincia;
		}else{      // puede ser consumidor final
			if (isset($objDatos->CondicionIVADesc)) {
				$nombre     = $objDatos->errorConstancia->apellido;
				$domi['direccion'] = '';
				$domi['localidad'] = '';
				$domi['codigo_postal'] = '';
				$domi['provincia_afip'] = '';
				$domi['provincia_nombre'] = '';
			}
		}
		$resu['nombre']     = $nombre;
		$resu['domicilio']  = $domi;
		$resu['condiIva']   = $objDatos->CondicionIVADesc;
		$resu['categoria_iva_id'] = $this->retornoCategoriaIvaId($resu['condiIva']);

		$resu['observaciones_afip'] = '';
		if (isset($objDatos->Observaciones)) {
			if (is_array($objDatos->Observaciones)) {                    												//recorro las observaciones
				$obse = '' ;
				foreach ($objDatos->Observaciones as $observa ){
					$obse = $obse . $observa[0] . ' - ';
				}
				$resu['observaciones_afip'] = $obse;
			}else{
				$resu['observaciones_afip'] = $objDatos->Observaciones;
			}
		}

		// si envío un DNi y AFIP tiene el CUIT o CUIL de la persona, lo ingreso
		if (isset($objDatos->datosGenerales->idPersona)) {
			$resu['idPersona'] = $objDatos->datosGenerales->idPersona;
		}

		return $resu;
	}

	/**
	 * De acuerdo al nombre de la categoría pasado por AFIP busco id
	 *
	 * @param string $nombreCategoria
	 * @return int
	 * @throws Exception
	 */
	private function retornoCategoriaIvaId (string $nombreCategoria): int
	{
		$categoriaIva = 0;
		if (strlen($nombreCategoria) > 1) {
			$categoriaIva = (new TablasAFIPRepository($this->connection))->getCategoriasIVAByNombre($nombreCategoria);
			if ($categoriaIva) {
				$categoriaIva = (int)$categoriaIva['id'];
			}
		}
		return $categoriaIva;
	}
}
