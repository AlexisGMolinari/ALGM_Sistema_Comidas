<?php


namespace App\Controller\Empresa\Clientes;

use App\Form\Empresa\Clientes\ClienteType;
use App\Reportes\Empresa\Clientes\ResumenCuentaPdf;
use App\Repository\Configuracion\UsuarioRepository;
use App\Repository\Empresa\Clientes\ClienteRepository;
use App\Repository\Empresa\Cobranzas\ReciboRepository;
use App\Repository\Empresa\EmpresaRepository;
use App\Repository\Empresa\Ventas\Comprobantes\FacturaRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route("api/clientes", name: "api_clientes_")]
class ClienteController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route("/", name: "index", methods: ["GET"])]
    public function getRegistros(GetRequestValidator $requestValidator,
                                 ClienteRepository   $clienteRepository): JsonResponse
    {
        $registros = $clienteRepository->getAllPaginados($requestValidator->getRequest());
        return $this->json($registros);
    }


    /**
     * Trae clientes del autocompletar
     *
     * @throws Exception
     */
    #[Route('/autocompletar/{texto}', name: "api_clientes_autocompletar", methods: ["GET"])]
    public function getClientesAutocompletar(string            $texto,
                                             ClienteRepository $clienteRepository): JsonResponse
    {
        $registros = $clienteRepository->getAutocompletar($texto, null);
        return $this->json($registros);
    }

    /**
     * @param string $texto
     * @param int $empresaId
     * @param ClienteRepository $clienteRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/autocompletar-empresas/{texto}/{empresaId}', name: "api_clientes_autocompletar_empresas", methods: ["GET"])]
    public function getClientesAutocompletarEmpresas(string            $texto, int $empresaId,
                                                     ClienteRepository $clienteRepository): JsonResponse
    {
        $registros = $clienteRepository->getAutocompletar($texto, $empresaId);
        return $this->json($registros);
    }

    /**
     * Obtiene todas las nc/nd de unc cliente
     * @param int $id
     * @param int $empresaId
     * @param ClienteRepository $clienteRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/{id}/nc-nd/{empresaId}", name: "getNcNd", requirements: ["id" => "\d+"], methods: ["GET"])]
    public function getClienteNcNd(int $id,
                                   int $empresaId,
                                   ClienteRepository $clienteRepository): JsonResponse
    {
        $comprobantes = $clienteRepository->getNcNd($id, $empresaId);
        return $this->json($comprobantes);
    }

    /**
     * @throws Exception
     */
    #[Route("/{id}", name: "getOne", requirements: ["id" => "\d+"], methods: ["GET"])]
    public function getRegistroById(int $id, ClienteRepository $clienteRepository): JsonResponse
    {
        $clienteRepository->checkIdExiste($id);
		$cliente = $clienteRepository->getByidCompleto($id);
        return $this->json($cliente);
    }

    /**
     * @throws Exception
     */
    #[Route("/", name: "add", methods: ["POST"])]
    public function createRegistro(
        GetRequestValidator $requestValidator,
        ClienteType         $clienteType,
        ClienteRepository   $clienteRepository
    ): JsonResponse
    {
        $valores = $requestValidator->getRestBody();
        $clienteType->controloRegistro($valores);
        $clienteRepository->createRegistroCliente($valores);
        return $this->json($valores, 201);
    }

    /**
     * @throws Exception
     */
    #[Route("/{id}", name: "edit", requirements: ["id" => "\d+"], methods: ["PUT"])]
    public function updateCliente(
        GetRequestValidator $requestValidator,
        int                 $id,
        ClienteType         $clienteType,
        ClienteRepository   $clienteRepository
    ): JsonResponse
    {
        $valores = $requestValidator->getRestBody();
        $clienteType->controloRegistro($valores, $id);
        $clienteRepository->checkIdExiste($id);
        $clienteRepository->updateRegistroCliente($valores, $id);
        return $this->json([]);
    }

    /**
     * @throws Exception
     */
    #[Route("/{id}", name: "del", requirements: ["id" => "\d+"], methods: ["DELETE"])]
    public function deleteRegistro(int $id, ClienteRepository $clienteRepository): JsonResponse
    {
        $clienteRepository->checkIdExiste($id);
        $clienteRepository->deleteRegistro($id);
        return $this->json([]);
    }


    /**
     * ctrl que trae todos los comprobantes adeudados de un cliente, también busca si hay dinero a cuenta :
     * resta el monto de la factura - el monto del recibo
     * @throws Exception
     */
    #[Route("/{id}/comprobantes-adeudados", name: "adeudados", requirements: ["id" => "\d+"], methods: ["GET"])]
    public function getComprobantesAdeudados(FacturaRepository $facturaRepository,
                                             ReciboRepository  $reciboRepository,
                                             int               $id): JsonResponse
    {

        $comprobantes = $facturaRepository->getComprobantesAdeudados($id);
        $saldo = $reciboRepository->getSaldoResumen($id);
        $devo['comprobantes'] = $comprobantes;
        $devo['saldo'] = $saldo;
        return $this->json($devo);
    }


    /**
     * ctrl trae el resumen de cuenta del cliente, facturas y recibos :
     *
     * @throws Exception
     */
    #[Route("/{id}/resumen-cuenta/{periodo}", name: "resumen_cuenta", requirements: ["id" => "\d+", "periodo" => "\d+"], methods: ["GET"])]
    public function getResumenCuenta(ReciboRepository  $reciboRepository,
                                     ClienteRepository $clienteRepository,
                                     int               $id,
                                     int               $periodo): JsonResponse
    {
        $registros = $reciboRepository->getResumenCuenta($id, $periodo);
        $saldo = $reciboRepository->getSaldoResumen($id);

        // aprovecho y acualizo el saldo del cliente en la tabla
        $clienteRepository->update(['saldo' => $saldo], $id);

        $devo['registros'] = $registros;
        $devo['saldo'] = $saldo;

        return $this->json($devo);
    }

    /**
     * Ctrl que genera un pdf con el resumen de cuenta de un cliente y envía el enlace
     * @throws Exception
     */
    #[Route("/resumen/generar", name: "resumen_generar", methods: ["POST"])]
    public function generoResumenCuenta(GetRequestValidator $requestValidator,
										ClienteType         $clienteType,
										EmpresaRepository   $empresaRepository,
										ReciboRepository    $reciboRepository
    ): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $clienteType->controloRegistroResumenCta($postValues);

        $empresa = $empresaRepository->getByIdInternoCompleto();

        $nombrePdf = uniqid() . '_resumen_de_cuenta.pdf';

        $idCliente = (int)$postValues['idCliente'];
        $periodo = (int)$postValues['periodoResumen'];
        $registros = $reciboRepository->getResumenCuenta($idCliente, $periodo);
        $saldo = $reciboRepository->getSaldoResumen($idCliente);

        if (!$registros)
            throw new HttpException(404, 'No se registraron movimientos para el período de tiempo seleccionado');

        $generarPdf = new ResumenCuentaPdf();
        $generarPdf->setNombreArchivo('informes/' . $nombrePdf);
        $generarPdf->setSaldoTotal($saldo);
        $generarPdf->setDatosCabecera($empresa);
        $generarPdf->setDatosResumen($postValues);
        $generarPdf->setMovimientos($registros);
        $generarPdf->setSalida('F');
        $generarPdf->GenerarPdf();

        $devo['archivoPdf'] = $nombrePdf;

        return $this->json($devo);
    }


    /**
     * Controller que envía un email con el comprobante a un destinatario
     *
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    #[Route("/resumen/enviar", name: "resumen_envio", methods: ["POST"])]
    public function EnvioMailComprobante(GetRequestValidator $requestValidator,
                                         UsuarioRepository   $usuarioRepository,
                                         MailerInterface     $mailer): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $email = filter_var($postValues['emailCliente'], FILTER_SANITIZE_EMAIL);
        $idEmpresa = (int)$this->getUser()->getEmpresa();
        $usuario = $usuarioRepository->getByEmpresa($idEmpresa);
        $subject = $postValues['nombreCliente'] . ' - Resumen de cuenta (adjunto)';
        $path = 'informes/' . $postValues['archivoPdf'];
        $body = $this->armoBody($usuario);
        $message = (new Email())
            ->subject($subject)
            ->from(new Address('avisos@facturasimple.com.ar', $usuario['nombre']))
            ->replyTo(new Address($usuario['email'], $usuario['nombre']))
            ->to($email)
            ->html($body, 'text/html')
            ->attachFromPath($path, 'ResumenDeCuenta', 'application/pdf');

        $mailer->send($message);

        return $this->json([]);
    }

    /**
     * Función que arma el body del email
     */
    private function armoBody(array $datos): string
    {
        return '<html lang="es"><header></header><body>'
            . '<h4>Mensaje de la empresa ' . $datos['nombre'] . '</h4>'
            . '<hr>'
            . '<p> El presente email es para comunicarle que se le está adjuntando un resumen de cuenta</p>'
            . '<hr>'
            . '</body></html>';
    }

}
