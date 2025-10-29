<?php

namespace App\Controller\Empresa\Perfil;

use App\Repository\Empresa\EmpresaRepository;
use App\Service\Perfil\GetCuotasEmpresas;
use Doctrine\DBAL\Exception;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route("api/perfil/pagos", name: "api_perfil_pagos_")]
class EmpresaPerfilPagosController extends AbstractController
{
	/**
	 * @param GetCuotasEmpresas $getCuotasEmpresas
	 * @param EmpresaRepository $empresaRepository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/", name: "index", methods: ["GET"])]
	public function getRegistros(GetCuotasEmpresas $getCuotasEmpresas, EmpresaRepository $empresaRepository): JsonResponse
	{
		$empresa = $empresaRepository->getByIdInterno();
		$registros = $getCuotasEmpresas->getCuotasClienteFS($empresa['id']);
		return $this->json($registros);
	}
}
