<?php

namespace App\Utils\FE;

use Symfony\Component\HttpKernel\Exception\HttpException;

class Certificado
{
    private string $archivoCertificado;
    private string $archivoClavePrivada;

    /**
     * @param string $archivoCertificado
     * @param string $archivoClavePrivada
     * @return void
     */
    function cargarInformacionCertificado(string $archivoCertificado, string $archivoClavePrivada): void
    {
        $this->archivoCertificado = $archivoCertificado;
        $this->archivoClavePrivada = $archivoClavePrivada;
    }

    /**
     * @param $o
     * @param $cn
     * @param $cuit
     * @param $archivoSolicitud
     * @param $archivoClavePrivada
     * @return array
     */
    function generarNuevoCertificado($o, $cn, $cuit, $archivoSolicitud, $archivoClavePrivada): array
    {
        $config = array(
            "digest_alg" => "sha512",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );

        $pkey = openssl_pkey_new($config);

        openssl_pkey_export($pkey, $pkeyPEM);

        $dn = array(
            "organizationName" => $o,
            "commonName" => $cn,
            "serialNumber" => "CUIT ".$cuit
        );

        $csr = openssl_csr_new($dn, $pkey);

        openssl_csr_export($csr, $csrPEM);

        $result = array(
            "csr" => $csrPEM,
            "clave" => $pkeyPEM
        );

        if (isset($archivoSolicitud))
            file_put_contents($archivoSolicitud, $csr);

        if (isset($archivoClavePrivada))
            file_put_contents($archivoClavePrivada, $csr);

        return $result;
    }

    /**
     * @param $archivoSolicitud
     * @return array
     */
    function renovarCertificado($archivoSolicitud): array
    {
        $this->leerInformacionCertificado($o, $cn, $cuit, $vencimiento);
        return $this->generarNuevoCertificado($o, $cn, $cuit, $archivoSolicitud, null);
    }


    /**
     * @param $o
     * @param $cn
     * @param $cuit
     * @param $vencimiento
     * @return void
     */
    private function leerInformacionCertificado(&$o, &$cn, &$cuit, &$vencimiento): void
    {
        $cert = @file_get_contents($this->archivoCertificado);
        if ($cert === false) {
            throw new HttpException(400,'Error: no se pudo leer el archivo del certificado');
        }
        $ssl = openssl_x509_parse($cert);
        $o = isset($ssl['subject']['O']) ? $ssl['subject']['O'] : $ssl['subject']['CN'];
        $cn = $ssl['subject']['CN'];
        $cuit = str_replace("CUIT ", "", $ssl['subject']['serialNumber']);
        $vencimiento = $ssl['validTo'];
    }

    /**
     * @return string
     */
    function ic_FechaVencimiento(): string
    {
        $this->leerInformacionCertificado($o, $cn, $cuit, $vencimiento);
        return substr($vencimiento, 0, 6);
    }
}