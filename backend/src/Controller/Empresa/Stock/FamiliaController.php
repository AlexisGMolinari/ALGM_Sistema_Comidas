<?php


namespace App\Controller\Empresa\Stock;

use App\Form\Empresa\Stock\FamiliaType;
use App\Repository\Empresa\Stock\FamiliaRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route("api/familias", name:"api_familias_")]
class FamiliaController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route("/", name:"index",  methods:["GET"])]
    public function getRegistros(GetRequestValidator $requestValidator,
        FamiliaRepository $familiaRepository): JsonResponse
    {
        $registros = $familiaRepository->getAllPaginados($requestValidator->getRequest());
        return $this->json($registros);
    }

    /**
     * @throws Exception
     */
    #[Route("/{id}", name:"getOne", requirements:["id" => "\d+"], methods:["GET"])]
    public function getRegistroById(int $id, FamiliaRepository $familiaRepository): JsonResponse
    {
        $registro = $familiaRepository->checkIdExiste($id);
        return $this->json($registro);
    }

	/**
	 * @param int $id
	 * @param FamiliaRepository $familiaRepository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/{id}/subfamilias", name:"getSubflias", requirements:["id" => "\d+"], methods:["GET"])]
	public function getSubFamilias(int $id, FamiliaRepository $familiaRepository): JsonResponse
	{
		$familiaRepository->checkIdExiste($id);
		$subflias = $familiaRepository->getSubFliasByFliaId($id);
		return $this->json($subflias);
	}

    /**
     * @throws Exception
     */
    #[Route("/", name:"add", methods:["POST"])]
    public function createRegistro(
        GetRequestValidator $requestValidator,
        FamiliaType $familiaType,
        FamiliaRepository $familiaRepository
    ): JsonResponse {
        $valores = $requestValidator->getRestBody();
        $familiaType->controloRegistro($valores);
        $familiaRepository->createRegistro($valores);
        return $this->json([], 201);
    }

    /**
     * @throws Exception
     */
    #[Route("/{id}", name:"edit", requirements: ["id" => "\d+"], methods: ["PUT"])]
    public function updateRegistro(
        GetRequestValidator $requestValidator,
        int $id,
        FamiliaType $familiaType,
        FamiliaRepository $familiaRepository
    ): JsonResponse {
        $valores = $requestValidator->getRestBody();
        $familiaType->controloRegistro($valores, $id);
        $familiaRepository->checkIdExiste($id);
        $familiaRepository->updateRegistro($valores, $id);
        return $this->json([]);
    }

    /**
     * @throws Exception
     */
    #[Route("/{id}", name: "del", requirements: ["id" => "\d+"], methods:["DELETE"])]
    public function deleteRegistro(int $id, FamiliaRepository $familiaRepository): JsonResponse
    {
        $familiaRepository->checkIdExiste($id);
        $familiaRepository->deleteRegistro($id);
        return $this->json([]);
    }
}
