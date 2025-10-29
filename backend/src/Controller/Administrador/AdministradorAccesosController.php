<?php

declare(strict_types=1);

namespace App\Controller\Administrador;

use App\Repository\Administrador\AdministradorAccesosRepository;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/administrador/accesos', name: 'app_administrador_accesos_')]
class AdministradorAccesosController extends AbstractController
{
    /**
     * @param AdministradorAccesosRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/', name:"get_all", methods:["GET"])]
    public function index(AdministradorAccesosRepository $repository): JsonResponse
    {
        $accesos = $repository->getAll(false,true);
        return $this->json($accesos);
    }
}
