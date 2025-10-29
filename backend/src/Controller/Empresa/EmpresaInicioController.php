<?php

namespace App\Controller\Empresa;

use App\Repository\Configuracion\UsuarioRepository;
use App\Repository\Empresa\EmpresaGraficosTempRepository;
use App\Repository\Empresa\EmpresaPuntoDeVentaRepository;
use App\Repository\Empresa\EmpresaRepository;
use App\Repository\Empresa\Ventas\Comprobantes\FacturaRepository;
use App\Service\MessageService;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/empresa/inicio', name: 'app_empresa_inicio_')]
class EmpresaInicioController extends AbstractController
{
    /**
     * @param FacturaRepository $facturaRepository
     * @param EmpresaRepository $empresaRepository
     * @param EmpresaGraficosTempRepository $graficosTempRepository
     * @param MessageService $messageService
     * @return JsonResponse
     * @throws ClientExceptionInterface
     * @throws Exception
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    #[Route('/', name: 'inicio', methods: ["GET"])]
    public function getInicio(FacturaRepository             $facturaRepository,
                              EmpresaRepository             $empresaRepository,
                              EmpresaGraficosTempRepository $graficosTempRepository,
                              MessageService                $messageService): JsonResponse
    {
        $empresa = $empresaRepository->getByIdInterno();
        $facturacion = [];
        $facturacionDia = [];
        if ((int)$empresa['ocultar_graficos'] === 0) {
            $facturacion = $facturaRepository->getFacturacionPeriodo();
            $facturacionDia = $facturaRepository->getFacturacionDia();
        }

        $mensajes = $messageService->getMensajesPredefinidos($this->getUser()->getEmpresa());
        $graficoMeses = $graficosTempRepository->getGraficoByEmpresa($empresa['id']);

        $devo = [
            'mensajes' => $mensajes,
            'factuPeriodo' => $facturacion,
            'factuDia' => $facturacionDia,
            'grafico_6_meses' => $graficoMeses,
        ];

        return $this->json($devo);
    }
}
