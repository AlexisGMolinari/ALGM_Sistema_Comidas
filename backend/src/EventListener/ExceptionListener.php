<?php


namespace App\EventListener;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExceptionListener
{
    /**
     * @var Connection
     */
    private Connection $connection;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;
    /**
     * @var KernelInterface
     */
    private KernelInterface $kernel;

    public function __construct(Connection $connection,
                                LoggerInterface $logger,
                                TranslatorInterface $translator,
                                KernelInterface $kernel)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->kernel = $kernel;
    }

    public function onKernelException(ExceptionEvent $event )
    {
        if ($this->connection->isTransactionActive()){
            $this->connection->rollBack();
        }
        // You get the exception object from the received event
        $exception = $event->getThrowable();
        // var_dump(get_class($exception)); // para saber qué tipo de Excepción lanza
        $jsonResponse  =  new JsonResponse();

        $jsonContent = [
            'status' => 'error',
            // 'message' => $exception->getMessage()
            'message' => $this->translator->trans($exception->getMessage(), [])
        ];

        // HttpExceptionInterface is a special type of exception that
        // holds status code and header details
        if ($exception instanceof HttpExceptionInterface) {
            $jsonResponse->setStatusCode($exception->getStatusCode());
            $jsonResponse->headers->replace($exception->getHeaders());
            $jsonContent['traza'] = $exception->getTraceAsString();
        }else if (is_a($exception, 'Doctrine\DBAL\Exception')) {
            $jsonResponse->setStatusCode(404);
            $jsonContent = $this->procesoErrorDbal($exception);

            $mensaje = 'ALGM:' . date('d-m-Y h:i:s') . $exception->getMessage();
            $mensajeTraza = 'ALGM:' . $exception->getTraceAsString();
            if ($this->kernel->getEnvironment() === 'prod') {
                // si es producción guardo un log común porque el logger NO funciona
                $fp = fopen(__DIR__  . '/../../var/log/prodALGM.log','a+');
                fwrite($fp,$mensaje . PHP_EOL);
                fwrite($fp,$mensajeTraza . PHP_EOL);
                fclose($fp);
            }else{
                $this->logger->critical($mensaje);
                $this->logger->critical($mensajeTraza);
            }

        } else {
            $mensaje = 'ALGM:' . $exception->getMessage();
            $mensajeTraza = 'ALGM:' . $exception->getTraceAsString();
            if ($this->kernel->getEnvironment() === 'prod') {
                // si es producción guardo un log común porque el logger NO funciona
                $fp = fopen(__DIR__  . '/../../var/log/prodALGM.log','a+');
                fwrite($fp,$mensaje . PHP_EOL);
                fwrite($fp,$mensajeTraza . PHP_EOL);
                fclose($fp);
            }else{
                $this->logger->critical($mensaje);
                $this->logger->critical($mensajeTraza);
            }
            $jsonResponse->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $jsonContent['traza'] = $mensajeTraza;
        }

        $jsonResponse->headers->set('Content-Type', 'application/json');
        $jsonResponse->setContent(json_encode($jsonContent));
        // sends the modified response object to the event
        $event->setResponse($jsonResponse);
    }


    /**
     * Parseo los errores de Bases de datos y devuelvo el array con mensajes
     * @param object $exception
     * @return array
     */
    private function procesoErrorDbal (object $exception): array {
        $devo = [
            'message' => $exception->getMessage(),
            'traza' => $exception->getTraceAsString()
        ];
        $exeDbal = $exception;
        if ($exeDbal->getPrevious()){
            $codigoError = (int)$exeDbal->getPrevious()->getCode();
            switch ($codigoError) {
                case 23000 :
                    $devo['message'] = 'Error tiene registros asociados : ' . $this->parseoRegistrosAsociados($exeDbal->getMessage());
                    break;
                case 1146 :
                    $devo['message'] = $exeDbal->getMessage();
                    break;
                case 1062 : // clave duplicada
                    $devo['message'] = 'Registro duplicado para ' . $this->parseoClaveDuplicada($exeDbal->getMessage());
                    break;
                case 1451 : // registros Asociados
                    $devo['message'] = 'Error tiene registros asociados : ' . $this->parseoRegistrosAsociados($exeDbal->getMessage());
                    break;
            }
            // agregar más cases en el switch para errores de otros tipos por ejemplo email transport
        }else{
            $devo['message'] = $exeDbal->getMessage();
        }
        return $devo;
    }



    /**
     * @param string $textoError
     * @return mixed|string
     */
    private function parseoRegistrosAsociados(string $textoError): string
    {
        $txtDevo = '';
        $pos = strpos($textoError, 'for key');
        if ($pos !== false) {
            $texto = explode('for key', $textoError);
            if (isset($texto[1])) {
                $txtDevo = $texto[1];
            }
        }

        $pos = strpos($textoError, 'constraint fails');
        if ($pos !== false) {
            $texto = explode('constraint fails', $textoError);
            if (isset($texto[1])) {
                $txtDevo = $texto[1];
            }
        }
		$pos = strpos($textoError, 'Integrity constraint violation');
		if ($pos !== false) {
			$texto = explode('constraint violation', $textoError);
			if (isset($texto[1])) {
				$txtDevo = $texto[1];
			}
		}

		return (strlen($txtDevo) > 0)?$txtDevo: $textoError;
    }

    /**
     * @param string $textoError
     * @return string
     */
    private function parseoClaveDuplicada(string $textoError): string
    {
        $texto = explode("'", $textoError);
        if (isset($texto[1])){
            return $texto[1] . ' en el campo: ' .  $texto[3];
        }else{
            return '';
        }
    }

}
