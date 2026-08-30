<?php

namespace App\Support;

class ShopMedia
{
    public static function url(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (
            str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, '/')
        ) {
            return $path;
        }

        return asset('storage/'.$path);
    }
}
