<?php

namespace App\Controller\Administrador;

use App\Form\Administrador\Localidad\LocalidadType;
use App\Form\Empresa\EmpresaType;
use App\Repository\Administrador\AdminEmpresaRepository;
use App\Repository\Configuracion\UsuarioRepository;
use App\Repository\Empresa\Clientes\ClienteRepository;
use App\Repository\Shared\LocalidadRepository;
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
     * @param AdminEmpresaRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/sin-usuarios", name: "get_empresas_sin_usuario", methods: ["GET"])]
    public function getEmpresasSinUsuario(AdminEmpresaRepository $repository): JsonResponse
    {
        $empresas = $repository->getEmpresasSinUsuario();
        return $this->json($empresas);
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
     * @param int $id
     * @param Request $request
     * @param AdminEmpresaRepository $empresaRepository
     * @param ClienteRepository $clienteRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/{id}/clientes', name: 'getClientes', requirements: ['id' => '\d+'], methods: ["GET"])]
    public function getClientes(int $id,
                                Request $request,
                                AdminEmpresaRepository $empresaRepository,
                                ClienteRepository $clienteRepository): JsonResponse
    {
        $empresaRepository->checkIdExiste($id);
        $clienteRepository->setEmpresa($id);
        $clientes = $clienteRepository->getAllPaginados($request);
        return $this->json($clientes);
    }

    /**
     * @param AdminEmpresaRepository $repository
     * @param GetRequestValidator $requestValidator
     * @param EmpresaType $type
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/', name: 'creo_empresa', methods: ["POST"])]
    public function altaEmpresa(AdminEmpresaRepository $repository,
                                GetRequestValidator $requestValidator,
                                EmpresaType $type): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $type->controloRegistro($postValues, 0);
        $repository->createRegistro($postValues);
        return $this->json([]);
    }

    /**
     * @param int $id
     * @param AdminEmpresaRepository $repository
     * @param GetRequestValidator $requestValidator
     * @param EmpresaType $type
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/editar/{id}', name: 'edito_empresa', requirements: ['id' => '\d+'], methods: ["PUT"])]
    public function editoEmpresa(int $id,
                                 AdminEmpresaRepository $repository,
                                GetRequestValidator $requestValidator,
                                EmpresaType $type): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $type->controloRegistro($postValues, $id);
        $repository->updateRegistro($postValues, $id);
        return $this->json([]);
    }

    // --------------------------- LOCALIDADES

    /**
     * @param LocalidadRepository $localidadRepo
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/localidades', name: 'getall_localidad', methods: ["GET"])]
    public function getAllLocalidades(LocalidadRepository $localidadRepo): JsonResponse
    {
        $result = $localidadRepo->getall(false, true, true);
        return $this->json($result);
    }

    /**
     * @param int $id
     * @param LocalidadRepository $localidadRepo
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/localidades/{id}', name: 'get_localidad_by_id', requirements: ['id' => '\d+'], methods: ["GET"])]
    public function getLocalidadById(int $id,
                                      LocalidadRepository $localidadRepo): JsonResponse
    {
        $localidadRepo->checkIdExiste($id);
        $result = $localidadRepo->getById($id);
        return $this->json($result);
    }


    /**
     * @param GetRequestValidator $requestValidator
     * @param LocalidadType $typeLocalidad
     * @param LocalidadRepository $repositoryLocalidad
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/localidades/alta', name: 'creo_localidad', methods: ["POST"])]
    public function creoLocalidad(GetRequestValidator $requestValidator,
                                  LocalidadType $typeLocalidad,
                                  LocalidadRepository $repositoryLocalidad): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $typeLocalidad->controloRegistro($postValues, 0);
        $repositoryLocalidad->createRegistro($postValues);
        return $this->json([]);
    }

    /**
     * @param GetRequestValidator $requestValidator
     * @param int $id
     * @param LocalidadType $typeLocalidad
     * @param LocalidadRepository $repositoryLocalidad
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/localidades/editar/{id}', name: 'edito_localidad', requirements: ['id' => '\d+'], methods: ["PUT"])]
    public function editoLocalidad(GetRequestValidator $requestValidator,
                                  int $id,
                                  LocalidadType $typeLocalidad,
                                  LocalidadRepository $repositoryLocalidad): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $typeLocalidad->controloRegistro($postValues, $id);
        $repositoryLocalidad->updateRegistro($postValues, $id);
        return $this->json([]);
    }

    /**
     * @param int $id
     * @param LocalidadRepository $repositoryLocalidad
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/localidades/eliminar/{id}', name: 'elimino_localidad', requirements: ['id' => '\d+'], methods: ["PUT"])]
    public function eliminoLocalidad(int $id,
                                   LocalidadRepository $repositoryLocalidad): JsonResponse
    {
        $repositoryLocalidad->checkIdExiste($id);
        $repositoryLocalidad->deleteRegistro($id);
        return $this->json([]);
    }


    // ------------------------------ LOCALIDADES
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
     * @param int $id
     * @param AdminEmpresaRepository $empresaRepository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ["DELETE"])]
    public function deleteEmpresa(int $id,
                                  AdminEmpresaRepository $empresaRepository): JsonResponse
    {
        $empresaRepository->checkIdExiste($id);
        $empresaRepository->eliminarEmpresa($id);
        return $this->json([]);
    }


}
