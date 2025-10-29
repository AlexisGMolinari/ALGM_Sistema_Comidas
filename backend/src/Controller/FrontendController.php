<?php

namespace App\Controller;

use App\Repository\Shared\MigracionesRepository;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FrontendController extends AbstractController
{
    /**
     * @return Response
     */
    #[Route('/', name: 'app_frontend')]
    public function index(): Response
    {
        return new Response(null, 403);
    }

    /**
     * @param MigracionesRepository $repository
     * @return Response
     * @throws Exception
     */
    #[Route('/migracion-usuarios-accesos', name: 'app_migracion', methods: ['POST'])]
    public function migracionUsuariosAccesos(MigracionesRepository $repository): Response
    {
        $repository->procesarAccesos();
        return new Response('Terminamos', 200);
    }
}
