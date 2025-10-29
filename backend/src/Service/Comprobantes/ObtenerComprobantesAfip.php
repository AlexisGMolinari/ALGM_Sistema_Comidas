<?php

namespace App\Service\Comprobantes;

use App\Repository\Contador\ContadorEmpresaRepository;
use App\Repository\Contador\ContadorPuntoDeVentaRepository;
use App\Utils\FE\WsFE;
use Doctrine\DBAL\Exception;
use SoapFault;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ObtenerComprobantesAfip
{

	private ContadorEmpresaRepository $empresaRepository;
	private ContadorPuntoDeVentaRepository $puntoDeVentaRepository;

	public function __construct(ContadorEmpresaRepository      $empresaRepository,
								ContadorPuntoDeVentaRepository $puntoDeVentaRepository)
	{
		$this->empresaRepository = $empresaRepository;
		$this->puntoDeVentaRepository = $puntoDeVentaRepository;
	}

	/**
	 * Obtiene un comprobantes desde Afip
	 * @param array $postValues
	 * @param int $empresaId
	 * @return array
	 * @throws Exception
	 * @throws SoapFault
	 */
	public function obtener(array $postValues, int $empresaId): array
	{
		$empresa = $this->empresaRepository->getByIdEmpresa($empresaId);
		$pathCertificado = __DIR__ .'../../../../public/certificados/' . $empresa['archivo_certificado'];
		$pathClave = __DIR__ .'../../../../public/certificados/' . $empresa['archivo_clave'];
		$puntoVenta = $this->puntoDeVentaRepository->getById($postValues['puntoVenta']);
		$puntoVentaNro = (int)$puntoVenta['numero'];
		$devo = [];

		$wsfe = new WsFE();
		$wsfe->CUIT = doubleval($empresa['cuit']);
		$wsfe->setURL(URLWSW_PROD);
		$urlProd = URLWSAA_PROD;
		if ($wsfe->Login($pathCertificado, $pathClave, $urlProd)) {
			$tipoComprobante = (int)$postValues['tipoComprobante'];
			$nroComprobante = (int)$postValues['numeroComprobante'];
			if ($wsfe->CmpConsultar($tipoComprobante, $puntoVentaNro, $nroComprobante, $cbte)) {
				$devo['comprobante'] = (array)$cbte;
			} else {
				throw new HttpException(400, 'Error en Consulta de AFIP: ' . $wsfe->ErrorDesc);
			}
		} else {
			throw new HttpException(400, 'Error en Servicio de AFIP: ' . $wsfe->ErrorDesc);
		}

		return $devo;
	}
}
