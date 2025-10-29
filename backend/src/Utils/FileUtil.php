<?php

namespace App\Utils;


class FileUtil
{
    public static function borrarArchivo(string $path): void
    {
        if (file_exists($path))
            unlink($path);
    }
}