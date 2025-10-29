<?php

declare(strict_types=1);

namespace App\Controller\Configuracion;

use App\Repository\Configuracion\AccesoRepository;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/accesos',  name: 'app_accesos_')]
class AccesosController extends AbstractController
{
    /**
     * @param AccesoRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(AccesoRepository $repository): JsonResponse
    {
        $accesos = $repository->getAllAccesosCompleto();
        return $this->json($accesos);
    }
}
