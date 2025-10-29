<?php

namespace App\Controller\Empresa\Ventas;

use App\Repository\Empresa\Clientes\ClienteRepository;
use App\Repository\Empresa\EmpresaRepository;
use App\Repository\Empresa\Perfil\CajerosRepository;
use App\Repository\Empresa\Stock\ProductoRepository;
use App\Repository\Shared\TablasAFIPRepository;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route("api/ventas/venta-rapida", name: "api_ventas_venta_rapida_")]
class VentaRapidaController extends AbstractController
{

    /**
     * @param ClienteRepository $clienteRepository
     * @param ProductoRepository $productoRepository
     * @param TablasAFIPRepository $AFIPRepository
     * @param EmpresaRepository $empresaRepository
     * @param CajerosRepository $cajerosRepository
     * @return JsonResponse
     * @throws Exception
     */
	#[Route("/get-datos", name: "getDatos", methods: ["GET"])]
	public function getDatos(ClienteRepository $clienteRepository,
							 ProductoRepository $productoRepository,
							 TablasAFIPRepository $AFIPRepository,
							 EmpresaRepository $empresaRepository,
                             CajerosRepository $cajerosRepository): JsonResponse
	{
		$clientes = $clienteRepository->getAll(true,true, true);
		$productos = $productoRepository->getAll(true, true, true);
		$clienteCF = $clienteRepository->getClienteConsumidorFinal();
		$categoriasIva = $AFIPRepository->getAllCategoriasIVA();
		$puntosDeVenta = $empresaRepository->getPuntosVentas();
		$condicionesVta = $AFIPRepository->getCondicionesVentas();
        $cajeros = $cajerosRepository->getAll(true);

		$registro = [
			'clientes' => $clientes,
			'productos' => $productos,
			'categoriasIva' => $categoriasIva,
			'puntosVenta' => $puntosDeVenta,
			'condVentas' => $condicionesVta,
            'cajeros' => $cajeros,
			'idCliente' => (int)$clienteCF['id']
		];
		return $this->json($registro);
	}
}
