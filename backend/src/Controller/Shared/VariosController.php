<?php

namespace App\Controller\Shared;

use App\Service\FE\GetConstanciaInscripcion;
use Doctrine\DBAL\Exception;
use SoapFault;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/varios',  name: 'app_varios_')]
class VariosController extends AbstractController
{

	/**
	 * @param int $dniCuit
	 * @param GetConstanciaInscripcion $getConstanciaInscripcion
	 * @return JsonResponse
	 * @throws SoapFault
	 * @throws Exception
	 */
	#[Route('/get-constancia/{dniCuit}', name: 'get_constancia', requirements: ['dniCuit' => '\d+'], methods: ['GET'])]
	public function getConstanciaAfip(int $dniCuit,
									  GetConstanciaInscripcion $getConstanciaInscripcion): JsonResponse
	{
		$constancia = $getConstanciaInscripcion->getByCuit($dniCuit);
		return $this->json($constancia);
	}

}
