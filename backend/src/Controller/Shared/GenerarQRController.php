<?php

declare(strict_types=1);

namespace App\Controller\Shared;

use App\Form\Shared\GenerarQRType;
use App\Service\Comprobantes\QRGenerator;
use App\Service\GetRequestValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reportes', name: 'app_genera_qr_')]
class GenerarQRController extends AbstractController
{
    #[Route('/generar-qr', name: 'generar_qr', methods: ['POST'])]
    public function generaQR(GetRequestValidator $requestValidator,
                             GenerarQRType $type): JsonResponse
    {
        $datos = $requestValidator->getRestBody();
        $type->controloRegistro($datos);

        $qrPath = QRGenerator::GenerarQR($datos['texto']);
        $qrFilename = basename($qrPath);

        $qrUrl = sprintf('https://api.facturasimple.com.ar/public/tempQR/%s', $qrFilename);

        return new JsonResponse(['success' => true, 'url' => $qrUrl], 200);
    }
}
