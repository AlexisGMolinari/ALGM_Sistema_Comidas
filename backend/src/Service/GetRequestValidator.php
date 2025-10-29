<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\RequestStack;

class GetRequestValidator
{
    protected RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Obtiene los parámetros del request body y valida que por lo menos haya alguno
     * @return mixed
     * @throws HttpException
     */
    public function getRestBody(): mixed
	{
        $request = $this->requestStack->getCurrentRequest();
        $xmlContent = $request->getContent();
        $valuePosted = json_decode($xmlContent, true);
        if (!$valuePosted) {
            throw new HttpException(400, 'Parámetros Erróneos');
        }
        return $valuePosted;
    }

	/**
	 * @return Request|null
	 */
    public function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }


	/**
	 * Busca en la url del request un string
	 * @param string $needle
	 * @return bool
	 */
    public function existeEnRequest(string $needle) : bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (str_contains($request->getPathInfo(), $needle))
            return true;
        return false;
    }
}
