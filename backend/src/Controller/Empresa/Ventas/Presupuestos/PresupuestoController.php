<?php

namespace App\Controller\Empresa\Ventas\Presupuestos;

use App\Form\Empresa\Clientes\ClienteType;
use App\Form\Empresa\Presupuestos\PresupuestoType;
use App\Reportes\Empresa\Comprobantes\PresupuestoPdf;
use App\Repository\Configuracion\UsuarioRepository;
use App\Repository\Empresa\Clientes\ClienteRepository;
use App\Repository\Empresa\EmpresaRepository;
use App\Repository\Empresa\Ventas\Presupuestos\PresupuestoMovimientoRepository;
use App\Repository\Empresa\Ventas\Presupuestos\PresupuestoRepository;
use App\Service\Comprobantes\ConfiguracionComprobante;
use App\Service\Comprobantes\EnvioEmailComprobante;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route("api/ventas/presupuestos", name: "api_ventas_presupuestos_")]
class PresupuestoController extends AbstractController
{

	/**
	 * @param GetRequestValidator $requestValidator
	 * @param PresupuestoRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/", name: "index", methods: ["GET"])]
	public function getPresupuestos(GetRequestValidator $requestValidator,
								 PresupuestoRepository $repository): JsonResponse
	{
		$registros = $repository->getAllPaginados($requestValidator->getRequest());
		return $this->json($registros);
	}


	/**
	 *
	 * @param int $id
	 * @param PresupuestoRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/{id}", name: "get_one", requirements: ["id" => "\d+"], methods: ["GET"])]
	public function getPresupuesto(int                   $id,
								   PresupuestoRepository $repository): JsonResponse
	{
		$presupuesto = $repository->checkIdExiste($id);
		return $this->json($presupuesto);
	}

	/**
	 *
	 * @param int $id
	 * @param PresupuestoRepository $repository
	 * @param PresupuestoMovimientoRepository $movimientoRepository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/{id}/movimientos", name: "get_movimientos", requirements: ["id" => "\d+"], methods: ["GET"])]
	public function getPresupuestoMovimientos(int                             $id,
											  PresupuestoRepository           $repository,
											  PresupuestoMovimientoRepository $movimientoRepository): JsonResponse
	{
		$repository->checkIdExiste($id);
		$movimientos = $movimientoRepository->getMovimientosByPresupuesto($id);
		return $this->json($movimientos);
	}


	/**
	 * @param GetRequestValidator $requestValidator
	 * @param PresupuestoRepository $repository
	 * @param ConfiguracionComprobante $configuracionComprobante
	 * @param ClienteRepository $clienteRepository
	 * @param ClienteType $clienteType
	 * @param PresupuestoType $type
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/", name: "create", methods: ["POST"])]
	public function createPresupuesto(GetRequestValidator      $requestValidator,
									  PresupuestoRepository    $repository,
									  ConfiguracionComprobante $configuracionComprobante,
									  ClienteRepository        $clienteRepository,
									  ClienteType              $clienteType,
									  PresupuestoType          $type): JsonResponse
	{
		$postValues= $requestValidator->getRestBody();
		$type->controloRegistro($postValues);
		$codigo = $repository->createPresupuesto($postValues, $configuracionComprobante, $clienteRepository, $clienteType);
		return $this->json(['codigoPresupuesto' => $codigo], 201);
	}


	/**
	 * @param int $id
	 * @param GetRequestValidator $requestValidator
	 * @param PresupuestoRepository $repository
	 * @param ConfiguracionComprobante $configuracionComprobante
	 * @param ClienteRepository $clienteRepository
	 * @param ClienteType $clienteType
	 * @param PresupuestoType $type
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/{id}", name: "update", requirements: ["id" => "\d+"], methods: ["PUT"])]
	public function update(int                      $id, GetRequestValidator $requestValidator,
						   PresupuestoRepository    $repository,
						   ConfiguracionComprobante $configuracionComprobante,
						   ClienteRepository        $clienteRepository,
						   ClienteType              $clienteType,
						   PresupuestoType          $type): JsonResponse
	{
		$postValues= $requestValidator->getRestBody();
		$type->controloRegistro($postValues, $id);
		$codigo = $repository->updatePresupuesto($postValues, $configuracionComprobante, $clienteRepository, $clienteType);
		return $this->json(['codigoPresupuesto' => $codigo]);
	}


	/**
	 * Envía por email un presupuesto
	 *
	 * @param int $id
	 * @param string $email
	 * @param PresupuestoRepository $repository
	 * @param EmpresaRepository $empresaRepository
	 * @param UsuarioRepository $usuarioRepository
	 * @param PresupuestoPdf $pdf
	 * @param EnvioEmailComprobante $envioSvc
	 * @return JsonResponse
	 * @throws Exception
	 * @throws TransportExceptionInterface
	 */
	#[Route("/{id}/email/{email}", name: "email", requirements: ["id" => Requirement::POSITIVE_INT], methods: ["GET"])]
	public function envioEmailPresupuesto(int                   $id, string $email,
										  PresupuestoRepository $repository,
										  EmpresaRepository     $empresaRepository,
										  UsuarioRepository     $usuarioRepository,
										  PresupuestoPdf        $pdf,
										  EnvioEmailComprobante $envioSvc): JsonResponse
	{
		$presupuesto = $repository->checkIdExiste($id);
		$presupuesto = $repository->getByCodigoCompleto($presupuesto['codigo']);

		$empresaId  = $this->getUser()->getEmpresa();
		$empresa    = $empresaRepository->getByIdInternoCompleto($empresaId);
		$usuario    = $usuarioRepository->getByEmpresa($empresaId);
		$movIva     = $repository->getMovimientosIVA($presupuesto['cabecera']['id']);
		$empreArr   = ['empresa' => $empresa, 'usuario' => $usuario];


		$pdf->setEmpresa($empreArr)
			->setComprobante($presupuesto)
			->setMovimIVA($movIva)
			->setSalida('S');

		// variables para analizar si discriminar IVA o no en la Impresión
		$empresaCateIVA = (int)$empresa['categoria_iva_id'];
		$clienteCatIVA  = (int)$presupuesto['cabecera']['categoria_iva_id'];

		// solo si es resp inscripto empresa y cliente discrimino
		$pdf->setDiscrimino(false);
		if ($empresaCateIVA === 1 && $clienteCatIVA === 1){
			$pdf->setDiscrimino( true);
		}

		$email = filter_var($email, FILTER_SANITIZE_EMAIL);
		$pdfPresupuesto = $pdf->generarPdf();

		$envioSvc->setPdfComprobante($pdfPresupuesto)
			->setEmailEmpresa($usuario['email'])
			->setNombreEmpresa($empresa['nombre_fantasia'])
			->setEmailCliente($email)
			->setTipoComprobante('Presupuesto')
			->enviar();

		return $this->json([]);
	}
}
