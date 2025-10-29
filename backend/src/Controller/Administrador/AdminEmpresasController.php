<?php

namespace App\Controller\Administrador;

use App\Form\Contador\EmpresaType;
use App\Repository\Administrador\AdminConciliacionRepository;
use App\Repository\Administrador\AdminEmpresaRepository;
use App\Repository\Configuracion\UsuarioRepository;
use App\Repository\Empresa\Clientes\ClienteRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/administrador/empresas', name: 'app_administrador_empresas_')]
class AdminEmpresasController extends AbstractController
{
    /**
     * Controller que trae todas las Empresas
     *
     * @param AdminEmpresaRepository $empresaRepository
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/", name: "get_all", methods: ["GET"])]
    public function getEmpresas(AdminEmpresaRepository $empresaRepository,
                                Request                $request): JsonResponse
    {
        $registros = $empresaRepository->getAllPaginados($request);
        return $this->json($registros);
    }

    /**
     * @param int $id
     * @param AdminEmpresaRepository $empresaRepository
     * @param UsuarioRepository $usuarioRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/{id}', name: 'getOne', requirements: ['id' => '\d+'], methods: ["GET"])]
    public function getEmpresaById(int                    $id,
                                   AdminEmpresaRepository $empresaRepository,
                                   UsuarioRepository      $usuarioRepository): JsonResponse
    {
        $empresa = $empresaRepository->checkIdExiste($id);
        $usuario = $usuarioRepository->getByEmpresa($id);
        $empresa['activo'] = (int)$usuario['activo'];
        return $this->json($empresa);
    }

    /**
     * Trae todos los usuarios de una empresa
     * @param int $id
     * @param Request $request
     * @param AdminEmpresaRepository $empresaRepository
     * @param UsuarioRepository $usuarioRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/{id}/usuarios', name: 'getUsuarios', requirements: ['id' => '\d+'], methods: ["GET"])]
    public function getUsuariosDeLaEmpresa(int                    $id,
                                           Request                $request,
                                           AdminEmpresaRepository $empresaRepository,
                                           UsuarioRepository      $usuarioRepository): JsonResponse
    {
        $empresaRepository->checkIdExiste($id);
        $usuarios = $usuarioRepository->getAllByEmpresa($request, $id);
        return $this->json($usuarios);
    }

    /**
     * Actualiza datos de la empresa (Estado y controla Stock)
     *
     * @param int $id
     * @param GetRequestValidator $requestValidator
     * @param AdminEmpresaRepository $empresaRepository
     * @param EmpresaType $type
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/{id}', name: 'activar', requirements: ['id' => '\d+'], methods: ["PUT"])]
    public function activarEmpresa(int                    $id,
                                   GetRequestValidator    $requestValidator,
                                   AdminEmpresaRepository $empresaRepository,
                                   EmpresaType            $type): JsonResponse
    {
        $empresaRepository->checkIdExiste($id);
        $putValues = $requestValidator->getRestBody();
        $type->controloActivacionEmpresaAdministrador($putValues);
        $empresaRepository->updateEmpresa($putValues, $id);
        return $this->json([]);
    }

    /**
     * Concilia ctas. ctes. de clientes.
     *
     * @param int $id
     * @param int $idCliente
     * @param AdminEmpresaRepository $empresaRepository
     * @param AdminConciliacionRepository $conciliacionRepository
     * @return JsonResponse
     * @throws Exception
     */
	#[Route('/{id}/cliente/{idCliente}/conciliar-cta-cte', name: 'conciliar',
        requirements: ['id' => '\d+', 'idCliente' => '\d+'], methods:["POST"])]
	public function conciliarCtaCte(int $id, int $idCliente,
                                    AdminEmpresaRepository $empresaRepository,
                                    AdminConciliacionRepository $conciliacionRepository): JsonResponse
    {
        $empresaRepository->checkIdExiste($id);
        $empresaRepository->checkClienteExiste($idCliente);
        $conciliacionRepository->concilioCuenta($idCliente);
        return $this->json([]);
    }

}
