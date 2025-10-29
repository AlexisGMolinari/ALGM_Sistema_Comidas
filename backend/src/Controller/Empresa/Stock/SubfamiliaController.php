<?php


namespace App\Controller\Empresa\Stock;

use App\Form\Empresa\Stock\SubfamiliaType;
use App\Repository\Empresa\Stock\SubfamiliaRepository;
use App\Service\GetRequestValidator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route("api/subfamilias", name:"api_subfamilias_")]
class SubfamiliaController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route("/", name:"index",  methods:["GET"])]
    public function getRegistros(GetRequestValidator $requestValidator,
        SubfamiliaRepository $subfamiliaRepository): JsonResponse
    {
        $registros = $subfamiliaRepository->getAllPaginados($requestValidator->getRequest());
        return $this->json($registros);
    }

    /**
     * @throws Exception
     */
    #[Route("/{id}", name:"getOne", requirements:["id" => "\d+"], methods:["GET"])]
    public function getRegistroById(int $id, SubfamiliaRepository $subfamiliaRepository): JsonResponse
    {
        $registro = $subfamiliaRepository->checkIdExiste($id);
        return $this->json($registro);
    }

	/**
	 * Obtiene las subfamilias relacionadas con el id de una de ellas
	 * @param int $id
	 * @param SubfamiliaRepository $subfamiliaRepository
	 * @return JsonResponse
	 * @throws Exception
	 */
	#[Route("/{id}/relacionadas", name:"getSubRelacionadas", requirements:["id" => "\d+"], methods:["GET"])]
	public function getSubFliasRelacionadas(int $id,
											SubfamiliaRepository $subfamiliaRepository): JsonResponse
	{
		$subfamiliaRepository->checkIdExiste($id);
		$subfamilias = $subfamiliaRepository->getASubFliasRelacionadas($id);
		return $this->json($subfamilias);
	}

    /**
     * @throws Exception
     */
    #[Route("/", name:"add", methods:["POST"])]
    public function createRegistro(
        GetRequestValidator $requestValidator,
        SubfamiliaType $subfamiliaType,
        SubfamiliaRepository $subfamiliaRepository
    ): JsonResponse {
        $valores = $requestValidator->getRestBody();
        $subfamiliaType->controloRegistro($valores);
        $subfamiliaRepository->createRegistro($valores);
        return $this->json([], 201);
    }

    /**
     * @throws Exception
     */
    #[Route("/{id}", name:"edit", requirements: ["id" => "\d+"], methods: ["PUT"])]
    public function updateRegistro(
        GetRequestValidator $requestValidator,
        int $id,
        SubfamiliaType $subfamiliaType,
        SubfamiliaRepository $subfamiliaRepository
    ): JsonResponse {
        $valores = $requestValidator->getRestBody();
        $subfamiliaType->controloRegistro($valores, $id);
        $subfamiliaRepository->checkIdExiste($id);
        $subfamiliaRepository->updateRegistro($valores, $id);
        return $this->json([]);
    }

    /**
     * @throws Exception
     */
    #[Route("/{id}", name: "del", requirements: ["id" => "\d+"], methods:["DELETE"])]
    public function deleteRegistro(int $id, SubfamiliaRepository $subfamiliaRepository): JsonResponse
    {
        $subfamiliaRepository->checkIdExiste($id);
        $subfamiliaRepository->deleteRegistro($id);
        return $this->json([]);
    }
}
