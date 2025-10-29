<?php

namespace App\Controller\Empresa\Stock;

use App\Form\Empresa\Stock\DepositoType;
use App\Repository\Empresa\EmpresaRepository;
use App\Repository\Empresa\Stock\DepositoRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route("api/empresa/inventario/depositos", name: "api_empresa_inventario_depositos_")]
class DepositosController extends AbstractController
{

	/**
	 * @param GetRequestValidator $requestValidator
	 * @param DepositoRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/", name: "index", methods: ["GET"])]
	public function getRegistros(GetRequestValidator $requestValidator,
								 DepositoRepository $repository): JsonResponse
	{
		$registros = $repository->getAllPaginados($requestValidator->getRequest());
		return $this->json($registros);
	}

	/**
	 * Devuelve todos los depósitos de una empresa
	 * @param int $id
	 * @param DepositoRepository $repository
	 * @param EmpresaRepository $empresaRepository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/empresa/{id}", name: "getByEmpresa", requirements: ["id" => "\d+"], methods: ["GET"])]
	public function getDepositosDeUnaEmpresa(int $id,
											 DepositoRepository $repository,
											 EmpresaRepository $empresaRepository): JsonResponse
	{
		$empresaRepository->checkIdExiste($id);
		$deposito = $repository->getAllPorEmpresa($id);
		return $this->json($deposito);
	}

	/**
	 * @param int $id
	 * @param DepositoRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/{id}", name: "getOne", requirements: ["id" => "\d+"], methods: ["GET"])]
	public function getRegistroById(int $id, DepositoRepository $repository): JsonResponse
	{
		$deposito = $repository->checkIdExiste($id);
		return $this->json($deposito);
	}

	/**
	 * @param GetRequestValidator $requestValidator
	 * @param DepositoType $type
	 * @param DepositoRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/", name: "add", methods: ["POST"])]
	public function createRegistro(GetRequestValidator $requestValidator,
								   DepositoType $type,
								   DepositoRepository $repository): JsonResponse
	{
		$valores = $requestValidator->getRestBody();
		$type->controloRegistro($valores);
		$repository->createRegistro($valores);
		return $this->json($valores, 201);
	}

	/**
	 * @param GetRequestValidator $requestValidator
	 * @param int $id
	 * @param DepositoType $type
	 * @param DepositoRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/{id}", name: "edit", requirements: ["id" => "\d+"], methods: ["PUT"])]
	public function updateCliente(GetRequestValidator $requestValidator,
								  int $id,
								  DepositoType $type,
								  DepositoRepository $repository): JsonResponse
	{
		$valores = $requestValidator->getRestBody();
		$type->controloRegistro($valores, $id);
		$repository->checkIdExiste($id);
		$repository->updateRegistro($valores, $id);
		return $this->json([]);
	}

	/**
	 * @param int $id
	 * @param DepositoRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/{id}", name: "del", requirements: ["id" => "\d+"], methods:["DELETE"])]
	public function deleteRegistro(int $id, DepositoRepository $repository): JsonResponse
	{
		$repository->checkIdExiste($id);
		$repository->deleteRegistro($id);
		return $this->json([]);
	}
}
