<?php

namespace App\Controller\Empresa\Cobranzas;

use App\Form\Empresa\Cobranzas\ReciboType;
use App\Repository\Empresa\Cobranzas\ReciboRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route("api/cobranzas", name: "api_cobranzas_")]
class ReciboController extends AbstractController
{

	/**
	 * Asienta un pago y genera un recibo
	 * @param GetRequestValidator $requestValidator
	 * @param ReciboRepository $repository
	 * @param ReciboType $type
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/save-pago", name: "save_pago", methods: ["POST"])]
	public function savePago(GetRequestValidator $requestValidator,
								 ReciboRepository $repository,
								 ReciboType $type): JsonResponse
	{
		$postValues = $requestValidator->getRestBody();
		$type->controloAltaPago($postValues);
		$repository->savePago($postValues);
		return $this->json([], 201);
	}

	/**
	 * @param GetRequestValidator $requestValidator
	 * @param ReciboRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/recibos/", name: "get_recibos", methods: ["GET"])]
	public function getRecibos(GetRequestValidator $requestValidator,
							   ReciboRepository $repository): JsonResponse
	{
		$registros = $repository->getAllPaginados($requestValidator->getRequest());
		return $this->json($registros);
	}

	/**
	 * @param int $id
	 * @param ReciboRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/recibos/{id}", name: "delete_recibos", requirements:["id" => Requirement::POSITIVE_INT], methods: ["DELETE"])]
	public function deleteRecibos(int $id,
								  ReciboRepository $repository): JsonResponse
	{
		$recibo = $repository->checkIdExiste($id);
		$repository->deleteRecibo($id, $recibo['cliente_id']);
		return $this->json([]);
	}
}
