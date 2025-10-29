<?php

namespace App\Controller\Empresa\Reportes;

use App\Reportes\Empresa\Comprobantes\ComprobanteNOEPdf;
use App\Reportes\Empresa\Comprobantes\ComprobantePdf;
use App\Reportes\Empresa\Comprobantes\ComprobanteTicketPdf;
use App\Reportes\Empresa\Comprobantes\PresupuestoPdf;
use App\Reportes\Empresa\Comprobantes\ReciboPdf;
use App\Repository\Configuracion\UsuarioRepository;
use App\Repository\Empresa\Clientes\ClienteRepository;
use App\Repository\Empresa\Cobranzas\ReciboRepository;
use App\Repository\Empresa\EmpresaPuntoDeVentaRepository;
use App\Repository\Empresa\EmpresaRepository;
use App\Repository\Empresa\Ventas\Comprobantes\FacturaRepository;
use App\Repository\Empresa\Ventas\Presupuestos\PresupuestoRepository;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

/**
 * Clase con todos los endpoint públicos a la impresión PDF de los comprobantes
 */
#[Route("reportes/comprobantes", name: "reportes_comprobantes_")]
class ReportesComprobantesController extends AbstractController
{

    /**
     * @param string $codigo
     * @param PresupuestoRepository $repository
     * @param EmpresaRepository $empresaRepository
     * @param UsuarioRepository $usuarioRepository
     * @param PresupuestoPdf $pdf
     * @return Response
     * @throws Exception
     */
    #[Route("/presupuestos/{codigo}", name: "presupuesto", methods: ["GET"])]
    public function ImprimirPresupuesto(string                $codigo,
                                        PresupuestoRepository $repository,
                                        EmpresaRepository     $empresaRepository,
                                        UsuarioRepository     $usuarioRepository,
                                        PresupuestoPdf        $pdf): Response
    {
        $presupuesto = $repository->getByCodigoCompleto($codigo);

        $empresaId = (int)$presupuesto['cabecera']['empresa_id'];
        $empresa = $empresaRepository->getByIdInternoCompleto($empresaId);
        $usuario = $usuarioRepository->getByEmpresa($empresaId);
        $movIva = $repository->getMovimientosIVA($presupuesto['cabecera']['id']);
        $empreArr = ['empresa' => $empresa, 'usuario' => $usuario];


        $pdf->setEmpresa($empreArr)
            ->setComprobante($presupuesto)
            ->setMovimIVA($movIva);

        // variables para analizar si discriminar IVA o no en la Impresión
        $empresaCateIVA = (int)$empresa['categoria_iva_id'];
        $clienteCatIVA = (int)$presupuesto['cabecera']['categoria_iva_id'];

        // solo si es resp inscripto empresa y cliente discrimino
        $pdf->setDiscrimino(false);
        if ($empresaCateIVA === 1 && $clienteCatIVA === 1) {
            $pdf->setDiscrimino(true);
        }
        return new Response($pdf->generarPdf(), 200, array(
            'Content-Type' => 'application/pdf'));
    }

    /**
     * Controller que genera un comprobante en PDF de acuerdo al código del mismo
     * @throws Exception
     */
    #[Route("/factura/{codigo}", name: "factura_comprobante_to_pdf", requirements: ["codigo" => Requirement::ASCII_SLUG], methods: ["GET"])]
    public function ImprimirComprobante(FacturaRepository             $facturaRepository,
                                        EmpresaRepository             $empresaRepository,
                                        UsuarioRepository             $usuarioRepository,
                                        EmpresaPuntoDeVentaRepository $empresaPuntoDeVentaRepository,
                                        string                        $codigo): Response
    {
        $comprobante = $facturaRepository->getByCodigoFactura($codigo, false);


        $empresaId = (int)$comprobante['cabecera']['empresa_id'];
        $empresa = $empresaRepository->getByIdInternoCompleto($empresaId);
        $usuario = $usuarioRepository->getByEmpresa($empresaId);
        $movIva = $facturaRepository->getMovimientosIVA($comprobante['cabecera']['id']);
        $puntoVenta = $empresaPuntoDeVentaRepository->getByNumero($comprobante['cabecera']['punto_venta'], $empresaId);

        $empreArr = $this->getDatosCabecera($empresa, $usuario, $puntoVenta);

        // determino si es un comprobante electrónico o no para imprimir una factura o un presupuesto
        $pvTieneFE = $empresaPuntoDeVentaRepository->getTieneFEPuntoVenta((int)$comprobante['cabecera']['punto_venta'], $empresaId);
        if ($pvTieneFE === 1) {
            $generarPdf = new ComprobantePdf();
            $generarPdf->setEmpresa($empreArr);
            $generarPdf->setComprobante($comprobante);
            $generarPdf->setMovimIVA($movIva);
            $generarPdf->setEntrega($comprobante['entrega']);

            // Si La letra del comprobante es A discrimino
            $generarPdf->setDiscrimino(false);
            if (trim($comprobante['cabecera']['letra_factura']) === 'A') {
                $generarPdf->setDiscrimino(true);
            }

            $pdf = $generarPdf->GenerarPdf();
        } else {
            $generarPdfNOFE = new ComprobanteNOEPdf();
            $generarPdfNOFE->setEmpresa($empreArr);
            $generarPdfNOFE->setComprobante($comprobante);
            $generarPdfNOFE->setEntrega($comprobante['entrega']);
            $pdf = $generarPdfNOFE->GenerarPdf();
        }

        return new Response($pdf, 200, array(
            'Content-Type' => 'application/pdf'));
    }

	/**
	 * Genera un ticket PDF
	 * @param FacturaRepository $facturaRepository
	 * @param EmpresaRepository $empresaRepository
	 * @param UsuarioRepository $usuarioRepository
	 * @param EmpresaPuntoDeVentaRepository $empresaPuntoDeVentaRepository
	 * @param ComprobanteTicketPdf $ticketPdf
	 * @param string $codigo
	 * @return Response
	 * @throws Exception
	 */
	#[Route("/factura/{codigo}/ticket", name: "ticket_to_pdf", requirements: ["codigo" => Requirement::ASCII_SLUG], methods: ["GET"])]
	public function ImprimirTicket(FacturaRepository $facturaRepository,
								   EmpresaRepository $empresaRepository,
								   UsuarioRepository $usuarioRepository,
								   EmpresaPuntoDeVentaRepository $empresaPuntoDeVentaRepository,
								   ComprobanteTicketPdf $ticketPdf,
								   string $codigo): Response
	{
		$comprobante = $facturaRepository->getByCodigoFactura($codigo, false);
		if (!$comprobante)
			throw new HttpException(404, 'No se encontró el comprobante: ' . $codigo);

		$empresaId = (int)$comprobante['cabecera']['empresa_id'];
		$movIva = $facturaRepository->getMovimientosIVA($comprobante['cabecera']['id']);
		$empresa = $empresaRepository->getByIdInternoCompleto($empresaId);
		$usuario = $usuarioRepository->getByEmpresa($empresaId);
		$puntoVenta = $empresaPuntoDeVentaRepository->getByNumero($comprobante['cabecera']['punto_venta'], $empresaId);
		$empreArr = $this->getDatosCabecera($empresa, $usuario, $puntoVenta);
		// determino si es un comprobante electrónico o no para imprimir una factura o un presupuesto
		$pvTieneFE = $empresaPuntoDeVentaRepository->getTieneFEPuntoVenta((int)$comprobante['cabecera']['punto_venta'], $empresaId);

		$ticketPdf->setEmpresa($empreArr)
			->setComprobante($comprobante)
			->setEsFE((bool)$pvTieneFE);
		// Si La letra del comprobante es A discrimino
		$ticketPdf->setDiscrimino(false);
		if (trim($comprobante['cabecera']['letra_factura']) === 'A') {
			$ticketPdf->setDiscrimino( true)
				->setMovimIVA($movIva);
		}
		$pdf = $ticketPdf->GenerarTicketPdf();

		return new Response($pdf, 200, ['Content-Type' => 'application/pdf']);
	}

	/**
	 * @param ReciboRepository $reciboRepository
	 * @param EmpresaRepository $empresaRepository
	 * @param ClienteRepository $clienteRepository
	 * @param UsuarioRepository $usuarioRepository
	 * @param ReciboPdf $reciboPdf
	 * @param string $codigo
	 * @return Response
	 * @throws Exception
	 */
	#[Route("/recibo/{codigo}", name: "recibo_to_pdf", requirements: ["codigo" => Requirement::ASCII_SLUG], methods: ["GET"])]
	public function ImprimirRecibo(ReciboRepository $reciboRepository,
								   EmpresaRepository $empresaRepository,
								   ClienteRepository $clienteRepository,
								   UsuarioRepository $usuarioRepository,
								   ReciboPdf $reciboPdf,
								   string $codigo): Response
	{
		$recibo = $reciboRepository->getByCodigoCompleto($codigo);
		$empresaId = (int)$recibo['cabecera']['empresa_id'];
		$empresa = $empresaRepository->getByIdInternoCompleto($empresaId);
		$cliente = $clienteRepository->getByidCompleto($recibo['cabecera']['cliente_id'], $empresaId);
		$usuario = $usuarioRepository->getByEmpresa($empresaId);
		$empreArr = ['empresa' => $empresa, 'usuario' => $usuario];

		$reciboPdf->setEmpresa($empreArr)
			->setRecibo($recibo['cabecera'])
			->setCliente($cliente)
			->setFacturasimputadas($recibo['fcImputadas'])
			->setDiscrimino(false);
		$pdf = $reciboPdf->GenerarPdf();

		return new Response($pdf, 200, ['Content-Type' => 'application/pdf']);
	}

	/**
	 * @param array $empresa
	 * @param array $usuario
	 * @param array $puntoVenta
	 * @return array
	 */
    private function getDatosCabecera(array $empresa, array $usuario, array $puntoVenta): array
    {
        if (strlen($puntoVenta['razon_social']) > 1 && strlen($puntoVenta['direccion']) > 1) {
            $usuario['nombre'] = $puntoVenta['razon_social'];
            $empresa['nombre'] = $puntoVenta['razon_social'];
            $empresa['direccion'] = $puntoVenta['direccion'];
            $empresa['localidad'] = $puntoVenta['localidad'];
            $empresa['codigo_postal'] = $puntoVenta['codigo_postal'];
            $empresa['provincia'] = $puntoVenta['provincia'];
        }
        return ['empresa' => $empresa, 'usuario' => $usuario];
    }


}
