<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;

class ShopIconUpload
{
    public static function make(string $name, string $directory, string $label = 'آیکون'): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->maxSize(1024)
            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif', 'image/svg+xml'])
            ->helperText('نمایش در صفحه تسویه حساب. PNG، JPG، WebP یا SVG');
    }

    public static function forForm(?string $path): array
    {
        return $path ? [$path] : [];
    }

    public static function fromState(mixed $value): string
    {
        if (is_array($value)) {
            return (string) ($value[0] ?? '');
        }

        return (string) ($value ?? '');
    }
}
