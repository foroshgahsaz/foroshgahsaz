<?php

use App\Support\ShopDate;
use App\Support\ShopFormatter;

if (! function_exists('site_name')) {
    function site_name(): string
    {
        return app(\App\Services\Settings\SettingsService::class)->site()['name'];
    }
}

if (! function_exists('site_logo_url')) {
    function site_logo_url(): ?string
    {
        $logo = app(\App\Services\Settings\SettingsService::class)->site()['logo'] ?? null;

        if (! is_string($logo) || $logo === '') {
            return null;
        }

        return ShopFormatter::optionalStorageImageUrl($logo);
    }
}

if (! function_exists('shop_jalali')) {
    function shop_jalali(mixed $date, string $format = 'Y/m/d H:i', string $empty = '—'): string
    {
        return ShopDate::format($date, $format, $empty);
    }
}

if (! function_exists('shop_money')) {
    function shop_money(?int $amountTomans): string
    {
        return \App\Support\ShopLabels::formatShopMoney($amountTomans);
    }
}

if (! function_exists('shop_prices_hidden')) {
    function shop_prices_hidden(): bool
    {
        return \App\Support\ShopLabels::shopPricesHidden();
    }
}
