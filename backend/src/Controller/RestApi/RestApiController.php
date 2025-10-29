<?php

declare(strict_types=1);

namespace App\Controller\RestApi;

use App\Repository\Empresa\Clientes\ClienteRepository;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/rest-api", name: "api_restapi_")]
class RestApiController extends AbstractController
{
    /**
     * Controller que devuelve todos los usuarios con ROLE_USER
     * @param ClienteRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/clientes", name: "index", methods: ["GET"])]
    public function index(ClienteRepository $repository): JsonResponse
    {
        $empresas = $repository->getAllRestApi();
        return $this->json($empresas);
    }
}
