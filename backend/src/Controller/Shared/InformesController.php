<?php

namespace App\Controller\Shared;

use App\Form\Empresa\Informes\LibroIvaVentaType;
use App\Reportes\Shared\IvaVentasPdf;
use App\Repository\Empresa\EmpresaRepository;
use App\Repository\Shared\IvaVentasRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Programar aquí todos los informes para contadores y empresas (ejemplo IVA venta)
 */
#[Route('/api/informes-shared', name: 'app_informes_shared_')]
class InformesController extends AbstractController
{
    /**
     * Controller que genera el PDF con el IVA Venta - Lo genera con el ID del Contador
     *
     * @throws Exception
     */
    #[Route('/iva-venta', name: 'iva_venta', methods: ['POST'])]
    public function getIvaVenta(GetRequestValidator $requestValidator,
                                LibroIvaVentaType   $libroIvaVentaType,
                                EmpresaRepository   $empresaRepository,
                                Security            $security,
                                Connection          $connection): JsonResponse
    {
        $ivaVentaPost = $requestValidator->getRestBody();
        $libroIvaVentaType->controloRegistro($ivaVentaPost);

        // si es usuario busco los datos de empresa con otro método
        $roles = $security->getUser()->getRoles();
        if ($roles[0] === 'ROLE_USER') {
            $idEmpresa = (int)$security->getUser()->getEmpresa();
            $empresa = $empresaRepository->getByIdInternoCompleto();
        } else {
            $idEmpresa = (int)$ivaVentaPost['empresaId'];
            $idContador = (int)$this->getUser()->getId();
        }

        if (!$empresa)
            throw new HttpException(404, 'No se encontró la empresa');

        $ivaVentasRepository = (new IvaVentasRepository($connection, $security, $idEmpresa));
        $movimientos = $ivaVentasRepository->getIvaVentas(
            $ivaVentaPost['fechaDesde'],
            $ivaVentaPost['fechaHasta'],
            $ivaVentaPost['puntoVenta']);

        if ($ivaVentaPost['formato'] === 'pdf') {
            $nombrePdf =  $idEmpresa . '_reporte_iva_venta.pdf';
            $movCateTasa = $ivaVentasRepository->getTotalesPorCategoriaYTasa(
                $ivaVentaPost['fechaDesde'],
                $ivaVentaPost['fechaHasta'],
                $ivaVentaPost['puntoVenta']);

            // var_dump($movCateTasa);
            $generarPdf = new IvaVentasPdf();
            $generarPdf->setNombreArchivo('informes/' . $nombrePdf);
            $generarPdf->setMovimientos($movimientos);
            $generarPdf->setTotalesPorCategoriaYTasa($movCateTasa);
            $generarPdf->setDatosCabecera($this->armoIvaVentaCabecera($empresa, $ivaVentaPost));
            $generarPdf->setSalida('F');
            $generarPdf->GenerarPdf();
			$devo['nombrePdf'] = $nombrePdf;
        }else{
			$devo['registrosExcel'] = $movimientos;
		}

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
        $datosCabecera['cuit'] = substr($datosEmpresa['cuit'], 0, 2) . '-' . substr($datosEmpresa['cuit'], 2, 8) . '-' . substr($datosEmpresa['cuit'], 10, 1);
        $fechaDesde = \DateTime::createFromFormat('Y-m-d', $camposFrm['fechaDesde']);
        $fechaHasta = \DateTime::createFromFormat('Y-m-d', $camposFrm['fechaHasta']);
        $datosCabecera['fechaDesde'] = $fechaDesde->format('d/m/Y');
        $datosCabecera['fechaHasta'] = $fechaHasta->format('d/m/Y');
        $datosCabecera['categoria_iva_id'] = (int)$datosEmpresa['categoria_iva_id'];
        return $datosCabecera;
    }


}
