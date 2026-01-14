<?php

namespace App\Controller\Configuracion;

use App\Form\Configuracion\UsuarioType;
use App\Repository\Administrador\AdministradorAccesosRepository;
use App\Repository\Configuracion\UsuarioRepository;
use App\Repository\Empresa\EmpresaPuntoDeVentaRepository;
use App\Repository\Empresa\EmpresaRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Json;

#[Route('/api/usuarios',  name: 'app_usuario_')]
class UsuarioController extends AbstractController
{

	/**
	 * @param UsuarioRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(UsuarioRepository $repository): JsonResponse
    {
        $usuarios = $repository->getAllPorEmpresa(false, true);
        return $this->json($usuarios);
    }

    /**
     * @param UsuarioRepository $repository
     * @param AdministradorAccesosRepository $accesosRepository
     * @return JsonResponse
     * @throws Exception
     */
	#[Route('/usuario-actual', name: 'current', methods: ['GET'])]
	public function getCurrentUser( UsuarioRepository $repository,
                                    AdministradorAccesosRepository $accesosRepository): JsonResponse
	{
        $usuarioId = (int)$this->getUser()->getId();
        $empresa_id = $this->getUser()->getEmpresa();
		$usuarioActual = $repository->getUsuarioActual($usuarioId, $empresa_id);
        $usuarioActual['accesos'] = $repository->getUsuarioAccesos($usuarioId);
		unset($usuarioActual['autocompletar_registros']);
		return $this->json($usuarioActual);
	}


    /**
     * @param int $id
     * @param UsuarioRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/{id}', name: 'getOne', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getOne(int $id,
                           UsuarioRepository $repository): JsonResponse
    {
        $repository->checkIdExiste($id);
        $usuario = $repository->getByIdSinPass($id);
        return $this->json($usuario);
    }

    /**
     * @param UsuarioRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/sin-empresa', name: 'get_users_sin_empresa', methods: ['GET'])]
    public function getUsuariosSinEmpresa(UsuarioRepository $repository): JsonResponse
    {
        $usuario = $repository->getUsersSinEmpresa();
        return $this->json($usuario);
    }

    /**
     * Trae los accesos de cualquier usuario (id)
     * @param int $id
     * @param UsuarioRepository $repository
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/{id}/accesos', name: 'get_accesos', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getAccesos(int $id,
                               UsuarioRepository $repository): JsonResponse
    {
        $accesos = $repository->getUsuarioAccesos($id);
        return $this->json($accesos);
    }

	/**
	 * Controller que actualiza la clave de la empresa logueada
	 * @param GetRequestValidator $requestValidator
	 * @param UsuarioType $type
	 * @param UsuarioRepository $usuarioRepository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route('/cambiar-clave', name: 'cambia_clave',  methods: ['POST'])]
	public function cambiarClave(GetRequestValidator $requestValidator,
								 UsuarioType $type,
								 UsuarioRepository $usuarioRepository): JsonResponse
	{
		$postValues = $requestValidator->getRestBody();
		$type->controloCambioClaves($postValues);
		$usuarioRepository->savePasswords($postValues, $this->getUser()->getId());
		return $this->json([]);
	}

    /**
     * Crea un usuario nuevo y si se pasan accesos se los asigna
     * @param UsuarioRepository $repository
     * @param GetRequestValidator $requestValidator
     * @param UsuarioType $type
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/', name: 'create', methods: ['POST'])]
    public function create(UsuarioRepository $repository,
                           GetRequestValidator $requestValidator,
                           UsuarioType $type): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $usuario = $postValues['usuario'];
        $type->controloRegistro($usuario, 0);
        $repository->createUsuarioCompleto($postValues);
        return $this->json([], 201);
    }

    /**
     * @param int $id
     * @param UsuarioRepository $repository
     * @param GetRequestValidator $requestValidator
     * @param UsuarioType $type
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(int $id,
                           UsuarioRepository $repository,
                           GetRequestValidator $requestValidator,
                           UsuarioType $type): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $repository->checkIdExiste($id);
        if (!isset($postValues['usuario'])) {
            throw new HttpException(400, 'Formato inválido: falta el nodo usuario');
        }
        $usuario = $postValues['usuario'];
        // Limpieza de campos que NO van a BD
        unset($usuario['empresa_id'], $usuario['status']);
        $type->controloRegistro($usuario, $id);
        $repository->updateUsuario([
            'usuario' => $usuario
        ], $id);
        return $this->json([]);
    }

    /**
     * @param int $id
     * @param UsuarioRepository $repository
     * @param GetRequestValidator $requestValidator
     * @param UsuarioType $type
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/{id}/asigno-empresa', name: 'asigno_empresa', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function asignoEmpresa(int $id,
                                  UsuarioRepository $repository,
                                  GetRequestValidator $requestValidator,
                                  UsuarioType $type): JsonResponse
    {
        $postValues = $requestValidator->getRestBody();
        $repository->checkIdExiste($id);
        $type->controloDatos($postValues, $id);
        $repository->updateRegistro($postValues, $id);
        return $this->json([]);
    }
}
