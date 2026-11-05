<?php

namespace App\Services\Payment;

use RuntimeException;

class PaymentGatewayManager
{
    public function driver(?string $name = null): PaymentGatewayInterface
    {
        $name ??= (string) config('payment.default');
        if ($name === 'toopi') {
            $name = 'nopay';
        }
        $class = config("payment.gateways.{$name}.driver");

        if (! is_string($class) || $class === '' || ! class_exists($class)) {
            throw new RuntimeException('درگاه پرداخت پشتیبانی نمی‌شود.');
        }

        $gateway = app($class);

        if (! $gateway instanceof PaymentGatewayInterface) {
            throw new RuntimeException('درگاه پرداخت پشتیبانی نمی‌شود.');
        }

        return $gateway;
    }
}
