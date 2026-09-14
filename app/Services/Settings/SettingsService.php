<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    protected const CACHE_KEY = 'shop:settings:all';

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return data_get($settings, "{$group}.{$key}", $default);
    }

    public function set(string $group, string $key, mixed $value): void
    {
        Setting::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
        );

        Cache::forget(self::CACHE_KEY);
    }

    /** @param  array<string, mixed>  $values */
    public function setMany(string $group, array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($group, $key, $value);
        }
    }

    /** @return array<string, array<string, string|null>> */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            return Setting::query()
                ->get()
                ->groupBy('group')
                ->map(fn ($items) => $items->pluck('value', 'key')->all())
                ->all();
        });
    }

    /** @return array<string, mixed> */
    public function zarinpal(): array
    {
        return [
            'merchant_id' => $this->get('zarinpal', 'merchant_id') ?: config('payment.gateways.zarinpal.merchant_id'),
            'sandbox' => filter_var(
                $this->get('zarinpal', 'sandbox', config('payment.gateways.zarinpal.sandbox')),
                FILTER_VALIDATE_BOOLEAN
            ),
            'callback_url' => $this->get('zarinpal', 'callback_url') ?: config('payment.gateways.zarinpal.callback_url'),
            'amount_unit' => $this->get('zarinpal', 'amount_unit') ?: config('payment.gateways.zarinpal.amount_unit', 'toman'),
            'enabled' => filter_var($this->get('zarinpal', 'enabled', true), FILTER_VALIDATE_BOOLEAN),
            'icon' => $this->get('zarinpal', 'icon') ?: null,
        ];
    }

    /** @return array<string, mixed> */
    public function tara(): array
    {
        $cfg = config('payment.gateways.tara', []);
        $sandbox = filter_var($this->get('tara', 'sandbox', $cfg['sandbox'] ?? true), FILTER_VALIDATE_BOOLEAN);

        $purchaseDefault = $sandbox
            ? ($cfg['sandbox_base_url'] ?? 'https://stage-pay.tara360.ir/pay')
            : ($cfg['base_url'] ?? 'https://pay.tara360.ir/pay');
        $refundDefault = $sandbox
            ? ($cfg['sandbox_refund_base_url'] ?? 'https://stage.tara-club.ir/club')
            : ($cfg['refund_base_url'] ?? 'https://club.tara-club.ir/club');

        return [
            'enabled' => filter_var($this->get('tara', 'enabled', false), FILTER_VALIDATE_BOOLEAN),
            'sandbox' => $sandbox,
            'username' => (string) ($this->get('tara', 'username') ?: ($cfg['username'] ?? '')),
            'password' => (string) ($this->get('tara', 'password') ?: ($cfg['password'] ?? '')),
            'service_id' => (string) ($this->get('tara', 'service_id') ?: ($cfg['service_id'] ?? '')),
            'amount_unit' => (string) ($this->get('tara', 'amount_unit') ?: ($cfg['amount_unit'] ?? 'toman')),
            'callback_url' => (string) ($this->get('tara', 'callback_url') ?: ($cfg['callback_url'] ?? '/payment/callback/tara')),
            'client_ip' => (string) ($this->get('tara', 'client_ip') ?: ($cfg['client_ip'] ?? '')),
            'default_group' => (string) ($this->get('tara', 'default_group') ?: ($cfg['default_group'] ?? '1')),
            'default_group_title' => (string) ($this->get('tara', 'default_group_title') ?: ($cfg['default_group_title'] ?? 'عمومی')),
            'base_url' => rtrim((string) ($this->get('tara', $sandbox ? 'sandbox_base_url' : 'base_url') ?: $purchaseDefault), '/'),
            'refund_base_url' => rtrim((string) ($this->get('tara', $sandbox ? 'sandbox_refund_base_url' : 'refund_base_url') ?: $refundDefault), '/'),
            'refund_principal' => (string) ($this->get('tara', 'refund_principal') ?: ($cfg['refund_principal'] ?? '')),
            'refund_password' => (string) ($this->get('tara', 'refund_password') ?: ($cfg['refund_password'] ?? '')),
            'sandbox_base_url' => (string) ($this->get('tara', 'sandbox_base_url') ?: ($cfg['sandbox_base_url'] ?? '')),
            'production_base_url' => (string) ($this->get('tara', 'base_url') ?: ($cfg['base_url'] ?? '')),
            'sandbox_refund_base_url' => (string) ($this->get('tara', 'sandbox_refund_base_url') ?: ($cfg['sandbox_refund_base_url'] ?? '')),
            'production_refund_base_url' => (string) ($this->get('tara', 'refund_base_url') ?: ($cfg['refund_base_url'] ?? '')),
            'icon' => $this->get('tara', 'icon') ?: null,
        ];
    }

    /** @return array<string, mixed> */
    public function site(): array
    {
        return [
            'name' => $this->get('site', 'name') ?: config('app.name', 'چاپینو'),
            'description' => $this->get('site', 'description', ''),
            'logo' => $this->get('site', 'logo'),
            'favicon' => $this->get('site', 'favicon'),
            'phone' => $this->get('site', 'phone', ''),
            'email' => $this->get('site', 'email', ''),
            'address' => $this->get('site', 'address', ''),
            'instagram' => $this->get('site', 'instagram', ''),
            'telegram' => $this->get('site', 'telegram', ''),
        ];
    }

    /** @return array<string, mixed> */
    public function kavenegar(): array
    {
        return [
            'api_key' => $this->get('kavenegar', 'api_key') ?: config('sms.kavenegar.api_key'),
            'sender' => $this->get('kavenegar', 'sender') ?: config('sms.kavenegar.sender'),
            'otp_template' => $this->get('kavenegar', 'otp_template') ?: config('sms.kavenegar.otp_template'),
            'enabled' => filter_var($this->get('kavenegar', 'enabled', config('sms.driver') === 'kavenegar'), FILTER_VALIDATE_BOOLEAN),
        ];
    }
}
