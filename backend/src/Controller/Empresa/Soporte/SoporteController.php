<?php

namespace App\Controller\Empresa\Soporte;

use App\Repository\Empresa\Soporte\SoporteRepository;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/soporte', name: 'app_soporte_')]
class SoporteController extends AbstractController
{

	/**
	 * @param SoporteRepository $repository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/videos", name:"get_videos", methods:["GET"])]
	public function getVideos(SoporteRepository $repository): JsonResponse
	{
		$videos = $repository->getAllVideos();
		return $this->json($videos);
	}

}
