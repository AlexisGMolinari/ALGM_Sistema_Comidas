<?php

namespace App\Service;

use App\Repository\Empresa\EmpresaRepository;
use App\Utils\FE\Certificado;
use Doctrine\DBAL\Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class MessageService
{
	const ALERT_INFO = 'info';
	const ALERT_WARNING = 'warning';
	const ALERT_DANGER = 'danger';

    public function __construct(private HttpClientInterface $httpClient,
								private EmpresaRepository $empresaRepository)
    {
    }

	/**
	 * Mensajes que no se generan por base de datos, sino que se autogeneran como cuotas pendientes
	 * @param int $empresaId
	 * @return array
	 * @throws Exception
	 * @throws ClientExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 */
    public function getMensajesPredefinidos(int $empresaId): array
    {
		$mensajes = [];
        $mensajesPred = [];
        $enlaceCuotas = '/empresa/perfil/pagos';
        // traigo las cuotas desde el sistema de AdminF$ Cuotas
        $cuotasAdeudadas = (new HttpClientService($this->httpClient))->getCuotasImpagasClienteFS($empresaId);
        if ($cuotasAdeudadas) {
            if ($cuotasAdeudadas === 1) {
                $mensajesPred['tipo'] = self::ALERT_INFO;
                $mensajesPred['mensaje'] = 'Hola!!, según nuestros registros se adeuda una cuota del mes en curso:';
            } elseif ($cuotasAdeudadas === 2) {
                $mensajesPred['tipo'] = self::ALERT_WARNING;
                $mensajesPred['mensaje'] = 'Estimado usuario según nuestros registros se adeudan dos cuotas a la fecha; '
                    . 'tenga en cuenta que a la 3er cuota impaga el sistema automáticamente lo inhabilitará. '
                    . 'Recuerde que puede pagar con MercadoPago desde aquí:';
            } elseif ($cuotasAdeudadas > 2) {
                $mensajesPred['tipo'] = self::ALERT_DANGER;
                $mensajesPred['mensaje'] = 'Estimado usuario según nuestros registros se adeudan más de dos cuotas a la fecha; '
                    . 'por lo que el sistema automáticamente lo inhabilitará. '
                    . 'Recuerde que puede pagar con MercadoPago desde aquí:';
            }
            $mensajesPred['enlace'] = $enlaceCuotas;
			$mensajes[] = $mensajesPred;
        }

        /**
         * Vencimiento Certificado: avisar 1 mes antes (info), 15 días antes(warning) y finalmente 1 semana (danger).
         */
        $mensajesPred = [];
        $empresa = $this->empresaRepository->getByIdInterno();

        $pathCertificado = __DIR__ .'../../../public/certificados/' . $empresa['archivo_certificado'];
        $pathClave = __DIR__ .'../../../public/certificados/' . $empresa['archivo_clave'];

        // pueda que la empresa NO tenga el certificado
        if ($empresa['archivo_certificado']) {
            $certificadoObj = new Certificado();
            $certificadoObj->cargarInformacionCertificado($pathCertificado, $pathClave);

            $fechaCertificado = \DateTime::createFromFormat('ymd', $certificadoObj->ic_FechaVencimiento());
            // si la fecha del certificado es inválida genero una alerta de tipo danger
            if ($fechaCertificado === false) {
                $mensajesPred['tipo'] = self::ALERT_DANGER;
                $mensajesPred['mensaje'] = 'Error al leer la fecha del certificado de AFIP.';
            }
            $fecha_actual = (new \DateTime("now"));
            $diff = date_diff($fecha_actual, $fechaCertificado, true);
            if ($fecha_actual < $fechaCertificado) {
                if ($diff->days <= 7 ) {
                    $mensajesPred['tipo'] = self::ALERT_DANGER;
                    $mensajesPred['mensaje'] = 'El certificado de seguridad y comunicación con AFIP se vence en 7 días. Favor de comunicarse para renovarlo ya que no podrá seguir facturando.';
                }else if ($diff->days <= 15 ) {
                    $mensajesPred['tipo'] = self::ALERT_WARNING;
                    $mensajesPred['mensaje'] = 'El certificado de seguridad y comunicación con AFIP se vence en 15 días. Favor de comunicarse para renovarlo.';
                }else if ($diff->days <= 30 ) {
                    $mensajesPred['tipo'] = self::ALERT_INFO;
                    $mensajesPred['mensaje'] = "El certificado de seguridad y comunicación con AFIP se vence en un mes. Favor de comunicarse para renovarlo.";
                }
            }else{
                $mensajesPred['tipo'] = self::ALERT_DANGER;
                $mensajesPred['mensaje'] = 'El certificado de seguridad y comunicación con AFIP está vencido. Favor de comunicarse para renovarlo ya que no podrá seguir facturando.';
            }
            if (count($mensajesPred) > 0) {
                $mensajes[] = $mensajesPred;
            }
        }


		return $mensajes;
    }

}
