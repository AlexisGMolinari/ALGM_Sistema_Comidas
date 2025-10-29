<?php

namespace App\Controller\Empresa\Informes;

use App\Form\Empresa\Informes\ResumenVentaType;
use App\Reportes\Empresa\Clientes\ComprobantesImpagosPdf;
use App\Reportes\Empresa\Comprobantes\ResumenDeVentaPdf;
use App\Reportes\Empresa\Informes\ResumenCostosDeVentasReport;
use App\Repository\Empresa\Clientes\ClienteRepository;
use App\Repository\Empresa\EmpresaRepository;
use App\Repository\Empresa\Informes\CostosRepository;
use App\Repository\Empresa\Informes\ResumenDeVentaRepository;
use App\Repository\Empresa\Ventas\Comprobantes\FacturaRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Programar aquí todos los informes SOLO de empresas (ej. Resumen de ventas)
 */
#[Route("api/informes", name: "api_informes_")]
class EmpresaInformesController extends AbstractController
{

    /**
     * Ctrl que genera un pdf con el resumen de cuenta de un cliente y envía el enlace
     * @throws Exception
     */
    #[Route("/comprobantes-impagos/{clienteId}", name: "comprobantes_impagos", requirements: ["clienteId" => "\d+"], methods: ["GET"])]
    public function comprobantesImpagos(ClienteRepository $clienteRepository,
                                        EmpresaRepository $empresaRepository,
                                        FacturaRepository $facturaRepository,
                                        int               $clienteId
    ): JsonResponse
    {
        $cliente = $clienteRepository->checkIdExiste($clienteId);
        $empresa = $empresaRepository->getByIdInternoCompleto();

        $nombrePdf = uniqid() . '_comprobantes_impagos.pdf';

        $registros = $facturaRepository->getComprobantesAdeudados($clienteId);
        if (!$registros)
            throw new HttpException(404, 'No posee comprobantes adeudados');

        $generarPdf = new ComprobantesImpagosPdf();
        $generarPdf->setNombreArchivo('informes/' . $nombrePdf);
        $generarPdf->setDatosCabecera($empresa);
        $generarPdf->setMovimientos($registros);
        $generarPdf->setDatosCliente($cliente);
        $generarPdf->setSalida('F');
        $generarPdf->GenerarPdf();

        $devo['archivoPdf'] = $nombrePdf;

        return $this->json($devo);
    }

    /**
     * Ctrl que genera el PDF con el Resumen de Venta
     * @throws Exception
     */
    #[Route("/resumen-venta", name: "resumen_venta", methods: ["POST"])]
    public function getResumenVentas(GetRequestValidator      $requestValidator,
                                     ResumenVentaType         $resumenVentaType,
                                     EmpresaRepository        $empresaRepository,
                                     ResumenDeVentaRepository $resumenDeVentaRepository
    ): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $resumenVentaType->controloRegistro($postValues);

        $empresa = $empresaRepository->getByIdInternoCompleto();
        if (!$empresa)
            throw new HttpException(404,'No se encontró la empresa');

        $nombrePdf = uniqid() . '_resumen_venta.pdf';

        $movimientos = $resumenDeVentaRepository->getResumenVentas($postValues['fechaDesde'], $postValues['fechaHasta'], $postValues['puntoVenta'], 1, $postValues['cajero_id'] ?? null);
        $subFamilias = $resumenDeVentaRepository->getResumenVentas($postValues['fechaDesde'], $postValues['fechaHasta'], $postValues['puntoVenta'], 2, $postValues['cajero_id'] ?? null);
        $medioPagos  = $resumenDeVentaRepository->getResumenVentasMediosPagos($postValues['fechaDesde'], $postValues['fechaHasta'], $postValues['puntoVenta'], $postValues['cajero_id'] ?? null);

        if (!$movimientos)
            throw new HttpException(404, 'No se registraron ventas para el período de tiempo seleccionado');

        $generarPdf = new ResumenDeVentaPdf();
        $generarPdf->setNombreArchivo('informes/' . $nombrePdf);
        $generarPdf->setMovimientos($movimientos);
        $generarPdf->setDatosCabecera($this->armoIvaVentaCabecera($empresa, $postValues));
        $generarPdf->setDatosMediosPagos($medioPagos);
        $generarPdf->setTotalesPorSubFlia($subFamilias);
        $generarPdf->setSalida('F');
        $generarPdf->GenerarPdf();

        $devo['success'] = $nombrePdf;

        return $this->json($devo, 201);
    }

    /**
     * Genera el resumen de costos de la venta de cada movimiento de las facturas
     * @param GetRequestValidator $requestValidator
     * @param ResumenVentaType $resumenVentaType
     * @param EmpresaRepository $empresaRepository
     * @param CostosRepository $costosRepository
     * @param ResumenCostosDeVentasReport $report
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/costos-venta", name: "costos_venta", methods: ["POST"])]
    public function getCostosDeVentas(GetRequestValidator $requestValidator,
                                      ResumenVentaType $resumenVentaType,
                                      EmpresaRepository $empresaRepository,
                                      CostosRepository $costosRepository,
                                      ResumenCostosDeVentasReport $report): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $resumenVentaType->controloRegistro($postValues);

        $empresa = $empresaRepository->getByIdInternoCompleto();
        if (!$empresa)
            throw new HttpException(404,'No se encontró la empresa');


        $nombrePdf = uniqid() . '_costos_venta.pdf';

        $movimientos = $costosRepository->getResumenCostosDeLasVentas($postValues['fechaDesde'], $postValues['fechaHasta'], $postValues['puntoVenta']);

        if (!$movimientos)
            throw new HttpException(404, 'No se registraron ventas para el período de tiempo seleccionado');

        $report->setNombreArchivo('informes/' . $nombrePdf);
        $report->setMovimientos($movimientos);
        $report->setDatosCabecera($this->armoIvaVentaCabecera($empresa, $postValues));
        $report->setSalida('F');
        $report->GenerarPdf();

        $devo['success'] = $nombrePdf;

        return $this->json($devo, 201);
    }

    /**
     * Función que arma los datos de la cabecera del Sub diario IVA ventas
     * @param $datosEmpresa array  Empresa
     * @param $camposFrm array datos del formulario para mostrar en cabecera
     * @return array datos de la cabecera
     */
    private function armoIvaVentaCabecera(array $datosEmpresa, array $camposFrm): array
    {
        $datosCabecera = [];
        $datosCabecera['nombreEmpresa'] = $datosEmpresa['nombre'];
        $datosCabecera['cuit']          = substr($datosEmpresa['cuit'],0,2) . '-' .substr($datosEmpresa['cuit'],2,8) . '-' .substr($datosEmpresa['cuit'],10,1);
        $fechaDesde = \DateTime::createFromFormat('Y-m-d H:i', $camposFrm['fechaDesde']);
        $fechaHasta = \DateTime::createFromFormat('Y-m-d H:i', $camposFrm['fechaHasta']);
        $datosCabecera['fechaDesde']    = $fechaDesde->format('d/m/Y H:i');
        $datosCabecera['fechaHasta']    = $fechaHasta->format('d/m/Y H:i');
        $datosCabecera['categoria_iva_id'] = (int)$datosEmpresa['categoria_iva_id'];
        $datosCabecera['logo']          = $datosEmpresa['logo'];
        return $datosCabecera;
    }

}
