<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ImageUtils
{

    public const PATH_FISICO_PRODUCTOS = 'imagenes/productos';


    public static function GetImageAbsolutPath(UrlGeneratorInterface $urlGenerator, string $routesName, ?string $filename): string|null
    {
        if ($filename === null || $filename === '')
            return $filename;

        return $urlGenerator->generate($routesName, [
            'filename' => $filename
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @param UploadedFile|null $uploadedFile
     * @param string $physicalPath
     * @param string|null $name
     * @return string|null
     */
    public static function PutImageOnPhysicalPath(?UploadedFile $uploadedFile, string $physicalPath, ?string $name = null): string|null
    {
        if ($uploadedFile === null)
            return null;
        if ($name !== null)
            $uploadedFileName = $name;
        else
            $uploadedFileName = $uploadedFile->getClientOriginalName();
        $storeName = uniqid(rand(), true) . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $uploadedFileName);
        $uploadedFile->move($physicalPath, $storeName);
        return $storeName;
    }

    /**
     * Recorre los campos tipos FILES con las imágenes de los repuestos y obtengo el índice dentro de los ítems de los
     * repuestos para asignar la imagen al repuesto que le corresponde
     * @param array $postValues
     * @param array $movimiento
     * @return void
     */
    public static function grabarImagenProducto(array $postValues, array &$movimiento): void
    {
        foreach ($postValues as $key => $post) {
            if (str_contains($key, 'imagen_producto_')) {
                $campo = explode('_', $key);
                $indice = (int)$campo[2];
                $movimiento[$indice]['imagen'] = ImageUtils::PutImageOnPhysicalPath($post, self::PATH_FISICO_PRODUCTOS);
            }
        }
    }

    /**
     * Verifica que exista el archivo de la imagen y lo borra
     * @param string $physicalPath
     * @param string $nombreImagen
     * @return bool
     */
    public static function deleteImagenFisica(string $physicalPath, string $nombreImagen): bool
    {
        $pathImagen = $physicalPath . '/' . $nombreImagen;
        if (file_exists($pathImagen)) {
            unlink($pathImagen);
            return true;
        }
        return false;
    }


    /**
     * Reemplaza los espacios en los nombres de los archivos
     * @param string $nombreArchivo
     * @return string
     */
    public static function reemplazarEspacios(string $nombreArchivo): string
    {
        return str_replace('_', ' ', $nombreArchivo);
    }
}