<?php

declare(strict_types=1);

namespace App\Controller\Administrador\Configuracion;

use App\Form\Administrador\Configuracion\ConfiguracionType;
use App\Repository\Administrador\Configuracion\ConfiguracionRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route("api/admin/configuracion", name: "api_admin_configuracion_")]
class ConfiguracionController extends AbstractController
{
    /**
     * @param ConfiguracionRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/", name: "get_config", methods: ["GET"])]
    public function index(ConfiguracionRepository $repository): JsonResponse
    {
        $empresa_id = $this->getUser()->getEmpresa();
        $registro = $repository->getConfiguracion($empresa_id);
        return $this->json($registro);
    }

    /**
     * @param GetRequestValidator $getRequestValidator
     * @param ConfiguracionRepository $repository
     * @param ConfiguracionType $type
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/", name:"edito", methods:["PUT"])]
    public function update(GetRequestValidator $getRequestValidator,
                           ConfiguracionRepository $repository,
                           ConfiguracionType $type): JsonResponse
    {
        $postValues = $getRequestValidator->getRestBody();
        $type->controloRegistro($postValues);
        $empresa_id = $this->getUser()->getEmpresa();
        $repository->guardoConfig($postValues, $empresa_id);
        return $this->json([]);
    }
}
