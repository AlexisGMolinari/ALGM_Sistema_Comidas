<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpClientService
{
    const URL_ADMIN_FS = 'https://adminfs.cuotasimple.ar/api/';
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

	/**
	 * Trae la cantidad de cuotas impagas de la empresa F$
	 * @param int $empresaFS
	 * @return int
	 * @throws TransportExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 */
    public function getCuotasImpagasClienteFS(int $empresaFS): int
    {
        $urlEndPoint = self::URL_ADMIN_FS . $empresaFS . '/cuotas-impagas';

        $result = $this->httpClient->request(
            'POST',
            $urlEndPoint, [ 'headers' => ['Accept' => 'application/json',],]
        )->getContent();

        // verifico si es un json válido
        $arrCuotas = json_decode($result, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($arrCuotas['error'])){
                $devo = 0; // hubo un error
            }else{
                $devo = (int)$arrCuotas['success'];
            }
        }else{
            $devo = 0; // hubo un error
        }
        return $devo;
    }
}
