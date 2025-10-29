<?php

namespace App\Controller\Empresa\Ventas\Comprobantes;

use App\Form\Empresa\Clientes\ClienteType;
use App\Form\Empresa\Ventas\ComprobanteType;
use App\Reportes\Empresa\Comprobantes\ComprobanteNOEPdf;
use App\Reportes\Empresa\Comprobantes\ComprobantePdf;
use App\Repository\Configuracion\UsuarioRepository;
use App\Repository\Empresa\Clientes\ClienteRepository;
use App\Repository\Empresa\EmpresaPuntoDeVentaRepository;
use App\Repository\Empresa\EmpresaRepository;
use App\Repository\Empresa\Ventas\Comprobantes\FacturaRepository;
use App\Service\Comprobantes\ConfiguracionComprobante;
use App\Service\Comprobantes\EnvioEmailComprobante;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use SoapFault;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('api/ventas/comprobantes', name: "api_ventas_comprobantes_")]
class ComprobanteController extends AbstractController
{

    /**
     * Controller que trae todos los comprobantes de un usuario
     * @throws Exception
     */
    #[Route("/", name: "index", methods: ["GET"])]
    public function getComprobantes(FacturaRepository $facturaRepository, Request $request): JsonResponse
    {
		$comprobantes = $facturaRepository->getAllPaginados($request);
        return $this->json($comprobantes);
    }

	/**
	 *
	 * @param int $id
	 * @param FacturaRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/{id}", name: "get_one", requirements: ["id" => "\d+"], methods: ["GET"])]
	public function getComprobanteById(int                   $id,
								   FacturaRepository $repository): JsonResponse
	{
		$comprobante = $repository->checkIdExiste($id);
		return $this->json($comprobante);
	}

	/**
	 * Obtiene un comprobante por su código
	 * @param string $codigo
	 * @param FacturaRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/{codigo}", name: "get_by_codigo", requirements: ["codigo" => Requirement::ASCII_SLUG], methods: ["GET"])]
	public function getByCodigo(string $codigo,
								   FacturaRepository $repository): JsonResponse
	{
		$comprobante = $repository->getByCodigoFactura($codigo);
		return $this->json($comprobante);
	}

	/**
	 * Crea un comprobante nuevo
	 *
	 * @param GetRequestValidator $requestValidator
	 * @param FacturaRepository $repository
	 * @param ClienteType $clienteType
	 * @param ConfiguracionComprobante $configuracionComprobante
	 * @param ComprobanteType $type
	 * @return JsonResponse
	 * @throws Exception|SoapFault
	 */
	#[Route("/agregar", name: "agregar", methods: ["POST"])]
	public function createComprobante(GetRequestValidator $requestValidator,
									  FacturaRepository $repository,
									  ClienteType $clienteType,
									  ConfiguracionComprobante $configuracionComprobante,
									  ComprobanteType $type): JsonResponse
	{
		$postValues = $requestValidator->getRestBody();
		$type->controloRegistro($postValues);
		$codigoNuevoComprobante = $repository->createComprobante($postValues,$clienteType, $configuracionComprobante);
		return $this->json(['idFact' => $codigoNuevoComprobante]);
	}

	/**
	 * Envía un comprobante por email
	 * @param GetRequestValidator $requestValidator
	 * @param FacturaRepository $facturaRepository
	 * @param EmpresaRepository $empresaRepository
	 * @param UsuarioRepository $usuarioRepository
	 * @param EmpresaPuntoDeVentaRepository $empresaPuntoDeVentaRepository
	 * @param ClienteRepository $clienteRepository
	 * @param string $codigo
	 * @param string $email
	 * @param EnvioEmailComprobante $envioEmailComprobante
	 * @return JsonResponse
	 * @throws Exception
	 * @throws TransportExceptionInterface
	 */
	#[Route("/{codigo}/email/{email}", name: "factura_mail", requirements: ["codigo" => Requirement::ASCII_SLUG],
		methods: ["GET"])]
	public function EnvioMailComprobante(GetRequestValidator           $requestValidator,
										 FacturaRepository             $facturaRepository,
										 EmpresaRepository             $empresaRepository,
										 UsuarioRepository             $usuarioRepository,
										 EmpresaPuntoDeVentaRepository $empresaPuntoDeVentaRepository,
										 ClienteRepository             $clienteRepository,
										 string                        $codigo,
										 string                        $email,
										 EnvioEmailComprobante         $envioEmailComprobante
	): JsonResponse
	{
		$email = filter_var($email, FILTER_SANITIZE_EMAIL);
		$comprobante = $facturaRepository->getByCodigoFactura($codigo);
		if (!$comprobante)
			throw new HttpException(404, 'No se encontró el comprobante: ' . $codigo);

		if (!$email)
			throw new HttpException(400, 'Cuenta de email no válida: ' . $email);

		$empresaId = (int)$this->getUser()->getEmpresa();
		$empresa = $empresaRepository->getByIdInternoCompleto($empresaId);
		$usuario = $usuarioRepository->getByEmpresa($empresaId);
		$movIva = $facturaRepository->getMovimientosIVA($comprobante['cabecera']['id']);
		$empreArr = ['empresa' => $empresa, 'usuario' => $usuario];


		// determino si es un comprobante electrónico o no para imprimir una factura o un presupuesto
		$pvTieneFE = $empresaPuntoDeVentaRepository->getTieneFEPuntoVenta((int)$comprobante['cabecera']['punto_venta']);
		if ($pvTieneFE === 1) {
			$generarPdf = new ComprobantePdf();
			$generarPdf->setEmpresa($empreArr);
			$generarPdf->setComprobante($comprobante);
			$generarPdf->setMovimIVA($movIva);
			$generarPdf->setSalida('S');
			$generarPdf->setEntrega($comprobante['entrega']);

			// Si La letra del comprobante es A discrimino
			$generarPdf->setDiscrimino(false);
			if (trim($comprobante['cabecera']['letra_factura']) === 'A') {
				$generarPdf->setDiscrimino(true);
			}
			$pdfGenerado = $generarPdf->GenerarPdf();
		} else {
			$generarPdfNOFE = new ComprobanteNOEPdf();
			$generarPdfNOFE->setEmpresa($empreArr);
			$generarPdfNOFE->setComprobante($comprobante);
			$generarPdfNOFE->setSalida('S');
			$generarPdfNOFE->setEntrega($comprobante['entrega']);
			$pdfGenerado = $generarPdfNOFE->GenerarPdf();
		}

		$envioEmailComprobante->setEmailCliente($email)
			->setPdfComprobante($pdfGenerado)
			->setTipoComprobante($comprobante['cabecera']['tipoComprobanteNombre'])
			->setNombreEmpresa($empresa['nombre_fantasia']);
		$envioEmailComprobante->enviar();

		//actualizo el email del cliente con la cuenta de email que se envió
		$clienteRepository->updateCampoRegistro(['email' => $email], (int)$comprobante['cabecera']['cliente_id']);

		return $this->json([]);
	}
}
