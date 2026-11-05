<?php

namespace App\Services\Payment;

use App\Models\Payment;

class NopayReturnUrl
{
    /**
     * @param  array<string, mixed>  $config
     */
    public static function absolute(array $config, Payment $payment): string
    {
        $path = self::path($config);

        return url($path).'?payment='.$payment->tracking_code;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function path(array $config): string
    {
        $callback = trim((string) ($config['callback_url'] ?? '/payment/callback/nopay'));
        if ($callback === '') {
            $callback = '/payment/callback/nopay';
        }

        if (str_starts_with($callback, 'http://') || str_starts_with($callback, 'https://')) {
            $parsed = parse_url($callback);

            return ($parsed['path'] ?? '/payment/callback/nopay')
                .(isset($parsed['query']) ? '?'.$parsed['query'] : '');
        }

        return str_starts_with($callback, '/') ? $callback : '/'.$callback;
    }
}
