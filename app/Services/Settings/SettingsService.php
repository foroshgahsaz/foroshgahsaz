<?php

namespace App\Services\Settings;

use App\Filament\Support\GatewayHomepagePlanFields;
use App\Models\Setting;
use App\Services\Cache\ShopCacheService;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    protected const CACHE_KEY = 'shop:settings:all';

    /** @var array<string, mixed>|null */
    protected static ?array $siteMemo = null;

    /** @var array<string, mixed>|null */
    protected static ?array $zarinpalMemo = null;

    /** @var array<string, mixed>|null */
    protected static ?array $taraMemo = null;

    /** @var array<string, mixed>|null */
    protected static ?array $bajetMemo = null;

    /** @var array<string, mixed>|null */
    protected static ?array $digipayMemo = null;

    /** @var array<string, mixed>|null */
    protected static ?array $parsianMemo = null;

    /** @var array<string, mixed>|null */
    protected static ?array $sadadMemo = null;

    /** @var array<string, mixed>|null */
    protected static ?array $codMemo = null;

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
        static::$siteMemo = null;
        static::$zarinpalMemo = null;
        static::$taraMemo = null;
        static::$bajetMemo = null;
        static::$digipayMemo = null;
        static::$parsianMemo = null;
        static::$sadadMemo = null;
        static::$codMemo = null;

        if (in_array($group, ['zarinpal', 'tara', 'bajet', 'digipay', 'parsian', 'sadad', 'cod', 'homepage'], true)) {
            app(ShopCacheService::class)->forgetHome();
        }
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
        if (static::$zarinpalMemo !== null) {
            return static::$zarinpalMemo;
        }

        return static::$zarinpalMemo = [
            'merchant_id' => $this->get('zarinpal', 'merchant_id') ?: config('payment.gateways.zarinpal.merchant_id'),
            'sandbox' => filter_var(
                $this->get('zarinpal', 'sandbox', config('payment.gateways.zarinpal.sandbox')),
                FILTER_VALIDATE_BOOLEAN
            ),
            'callback_url' => $this->get('zarinpal', 'callback_url') ?: config('payment.gateways.zarinpal.callback_url'),
            'amount_unit' => $this->get('zarinpal', 'amount_unit') ?: config('payment.gateways.zarinpal.amount_unit', 'toman'),
            'enabled' => filter_var($this->get('zarinpal', 'enabled', true), FILTER_VALIDATE_BOOLEAN),
            'icon' => $this->get('zarinpal', 'icon') ?: null,
            ...$this->gatewayCheckoutDisplayFields('zarinpal'),
            ...$this->gatewayHomepagePlanFields('zarinpal'),
        ];
    }

    /** @return array<string, mixed> */
    public function tara(): array
    {
        if (static::$taraMemo !== null) {
            return static::$taraMemo;
        }

        $cfg = config('payment.gateways.tara', []);
        $sandbox = filter_var($this->get('tara', 'sandbox', $cfg['sandbox'] ?? true), FILTER_VALIDATE_BOOLEAN);

        $purchaseDefault = $sandbox
            ? ($cfg['sandbox_base_url'] ?? 'https://stage-pay.tara360.ir/pay')
            : ($cfg['base_url'] ?? 'https://pay.tara360.ir/pay');
        $refundDefault = $sandbox
            ? ($cfg['sandbox_refund_base_url'] ?? 'https://stage.tara-club.ir/club')
            : ($cfg['refund_base_url'] ?? 'https://club.tara-club.ir/club');

        return static::$taraMemo = [
            'enabled' => filter_var($this->get('tara', 'enabled', false), FILTER_VALIDATE_BOOLEAN),
            'sandbox' => $sandbox,
            'username' => (string) ($this->get('tara', 'username') ?: ($cfg['username'] ?? '')),
            'password' => (string) ($this->get('tara', 'password') ?: ($cfg['password'] ?? '')),
            'service_id' => (string) ($this->get('tara', 'service_id') ?: ($cfg['service_id'] ?? '')),
            'amount_unit' => (string) ($this->get('tara', 'amount_unit') ?: ($cfg['amount_unit'] ?? 'toman')),
            'callback_url' => (string) ($this->get('tara', 'callback_url') ?: ($cfg['callback_url'] ?? '/payment/callback/tara')),
            'client_ip' => (string) ($this->get('tara', 'client_ip') ?: ($cfg['client_ip'] ?? '')),
            'default_group' => (string) ($this->get('tara', 'default_group') ?: ($cfg['default_group'] ?? '15')),
            'default_group_title' => (string) ($this->get('tara', 'default_group_title') ?: ($cfg['default_group_title'] ?? 'خانه و آشپزخانه')),
            'base_url' => rtrim((string) ($this->get('tara', $sandbox ? 'sandbox_base_url' : 'base_url') ?: $purchaseDefault), '/'),
            'refund_base_url' => rtrim((string) ($this->get('tara', $sandbox ? 'sandbox_refund_base_url' : 'refund_base_url') ?: $refundDefault), '/'),
            'refund_principal' => (string) ($this->get('tara', 'refund_principal') ?: ($cfg['refund_principal'] ?? '')),
            'refund_password' => (string) ($this->get('tara', 'refund_password') ?: ($cfg['refund_password'] ?? '')),
            'sandbox_base_url' => (string) ($this->get('tara', 'sandbox_base_url') ?: ($cfg['sandbox_base_url'] ?? '')),
            'production_base_url' => (string) ($this->get('tara', 'base_url') ?: ($cfg['base_url'] ?? '')),
            'sandbox_refund_base_url' => (string) ($this->get('tara', 'sandbox_refund_base_url') ?: ($cfg['sandbox_refund_base_url'] ?? '')),
            'production_refund_base_url' => (string) ($this->get('tara', 'refund_base_url') ?: ($cfg['refund_base_url'] ?? '')),
            'icon' => $this->get('tara', 'icon') ?: null,
            ...$this->gatewayCheckoutDisplayFields('tara'),
            ...$this->gatewayHomepagePlanFields('tara'),
        ];
    }

    /** @return array<string, mixed> */
    public function bajet(): array
    {
        if (static::$bajetMemo !== null) {
            return static::$bajetMemo;
        }

        $cfg = config('payment.gateways.bajet', []);
        $sandbox = filter_var($this->get('bajet', 'sandbox', $cfg['sandbox'] ?? true), FILTER_VALIDATE_BOOLEAN);

        $apiDefault = $sandbox
            ? ($cfg['sandbox_base_url'] ?? '')
            : ($cfg['base_url'] ?? '');

        return static::$bajetMemo = [
            'enabled' => filter_var($this->get('bajet', 'enabled', false), FILTER_VALIDATE_BOOLEAN),
            'sandbox' => $sandbox,
            'username' => (string) ($this->get('bajet', 'username') ?: ($cfg['username'] ?? '')),
            'password' => (string) ($this->get('bajet', 'password') ?: ($cfg['password'] ?? '')),
            'terminal_id' => (string) ($this->get('bajet', 'terminal_id') ?: ($cfg['terminal_id'] ?? '')),
            'amount_unit' => (string) ($this->get('bajet', 'amount_unit') ?: ($cfg['amount_unit'] ?? 'toman')),
            'callback_url' => (string) ($this->get('bajet', 'callback_url') ?: ($cfg['callback_url'] ?? '/payment/callback/bajet')),
            'return_url_base' => (string) ($this->get('bajet', 'return_url_base') ?: ($cfg['return_url_base'] ?? '')),
            'default_product_type' => (int) ($this->get('bajet', 'default_product_type') ?: ($cfg['default_product_type'] ?? 2)),
            'default_brand' => (string) ($this->get('bajet', 'default_brand') ?: ($cfg['default_brand'] ?? 'general')),
            'base_url' => rtrim((string) ($this->get('bajet', $sandbox ? 'sandbox_base_url' : 'base_url') ?: $apiDefault), '/'),
            'sandbox_base_url' => (string) ($this->get('bajet', 'sandbox_base_url') ?: ($cfg['sandbox_base_url'] ?? '')),
            'production_base_url' => (string) ($this->get('bajet', 'base_url') ?: ($cfg['base_url'] ?? '')),
            'portal_sandbox_base_url' => (string) ($this->get('bajet', 'portal_sandbox_base_url') ?: ($cfg['portal_sandbox_base_url'] ?? '')),
            'portal_base_url' => (string) ($this->get('bajet', 'portal_base_url') ?: ($cfg['portal_base_url'] ?? '')),
            'icon' => $this->get('bajet', 'icon') ?: null,
            ...$this->gatewayCheckoutDisplayFields('bajet'),
            ...$this->gatewayHomepagePlanFields('bajet'),
        ];
    }

    /** @return array<string, mixed> */
    public function digipay(): array
    {
        if (static::$digipayMemo !== null) {
            return static::$digipayMemo;
        }

        $cfg = config('payment.gateways.digipay', []);
        $sandbox = filter_var($this->get('digipay', 'sandbox', $cfg['sandbox'] ?? true), FILTER_VALIDATE_BOOLEAN);

        $apiDefault = $sandbox
            ? ($cfg['sandbox_base_url'] ?? 'https://uat.mydigipay.info/digipay/api')
            : ($cfg['base_url'] ?? 'https://api.mydigipay.com/digipay/api');

        return static::$digipayMemo = [
            'enabled' => filter_var($this->get('digipay', 'enabled', false), FILTER_VALIDATE_BOOLEAN),
            'sandbox' => $sandbox,
            'username' => (string) ($this->get('digipay', 'username') ?: ($cfg['username'] ?? '')),
            'password' => (string) ($this->get('digipay', 'password') ?: ($cfg['password'] ?? '')),
            'client_id' => (string) ($this->get('digipay', 'client_id') ?: ($cfg['client_id'] ?? '')),
            'client_secret' => (string) ($this->get('digipay', 'client_secret') ?: ($cfg['client_secret'] ?? '')),
            'amount_unit' => (string) ($this->get('digipay', 'amount_unit') ?: ($cfg['amount_unit'] ?? 'toman')),
            'callback_url' => (string) ($this->get('digipay', 'callback_url') ?: ($cfg['callback_url'] ?? '/payment/callback/digipay')),
            'ticket_type' => (int) ($this->get('digipay', 'ticket_type') ?: ($cfg['ticket_type'] ?? 11)),
            'default_product_type' => (int) ($this->get('digipay', 'default_product_type') ?: ($cfg['default_product_type'] ?? 1)),
            'access_token' => (string) ($this->get('digipay', 'access_token') ?: ''),
            'refresh_token' => (string) ($this->get('digipay', 'refresh_token') ?: ''),
            'base_url' => rtrim((string) ($this->get('digipay', $sandbox ? 'sandbox_base_url' : 'base_url') ?: $apiDefault), '/'),
            'sandbox_base_url' => (string) ($this->get('digipay', 'sandbox_base_url') ?: ($cfg['sandbox_base_url'] ?? '')),
            'production_base_url' => (string) ($this->get('digipay', 'base_url') ?: ($cfg['base_url'] ?? '')),
            'icon' => $this->get('digipay', 'icon') ?: null,
            'show_product_installment_promo' => filter_var(
                $this->get('digipay', 'show_product_installment_promo', false),
                FILTER_VALIDATE_BOOLEAN
            ),
            'product_installment_promo_text' => (string) ($this->get('digipay', 'product_installment_promo_text')
                ?: 'این درگاه اعتباری دیجی‌پی در ۴ قسط'),
            ...$this->gatewayCheckoutDisplayFields('digipay'),
            ...$this->gatewayHomepagePlanFields('digipay'),
        ];
    }

    /** @return array<string, mixed> */
    public function parsian(): array
    {
        if (static::$parsianMemo !== null) {
            return static::$parsianMemo;
        }

        $cfg = config('payment.gateways.parsian', []);

        return static::$parsianMemo = [
            'enabled' => filter_var($this->get('parsian', 'enabled', false), FILTER_VALIDATE_BOOLEAN),
            'login_account' => (string) ($this->get('parsian', 'login_account') ?: ($cfg['login_account'] ?? '')),
            'amount_unit' => (string) ($this->get('parsian', 'amount_unit') ?: ($cfg['amount_unit'] ?? 'toman')),
            'callback_url' => (string) ($this->get('parsian', 'callback_url') ?: ($cfg['callback_url'] ?? '/payment/callback/pec')),
            'icon' => $this->get('parsian', 'icon') ?: null,
            ...$this->gatewayCheckoutDisplayFields('parsian'),
            ...$this->gatewayHomepagePlanFields('parsian'),
        ];
    }

    /** @return array<string, mixed> */
    public function sadad(): array
    {
        if (static::$sadadMemo !== null) {
            return static::$sadadMemo;
        }

        $cfg = config('payment.gateways.sadad', []);

        return static::$sadadMemo = [
            'enabled' => filter_var($this->get('sadad', 'enabled', false), FILTER_VALIDATE_BOOLEAN),
            'merchant_id' => (string) ($this->get('sadad', 'merchant_id') ?: ($cfg['merchant_id'] ?? '')),
            'terminal_id' => (string) ($this->get('sadad', 'terminal_id') ?: ($cfg['terminal_id'] ?? '')),
            'terminal_key' => (string) ($this->get('sadad', 'terminal_key') ?: ($cfg['terminal_key'] ?? '')),
            'amount_unit' => (string) ($this->get('sadad', 'amount_unit') ?: ($cfg['amount_unit'] ?? 'toman')),
            'callback_url' => (string) ($this->get('sadad', 'callback_url') ?: ($cfg['callback_url'] ?? '/payment/callback/sadad')),
            'icon' => $this->get('sadad', 'icon') ?: null,
            ...$this->gatewayCheckoutDisplayFields('sadad'),
            ...$this->gatewayHomepagePlanFields('sadad'),
        ];
    }

    /** @return array<string, mixed> */
    public function cod(): array
    {
        if (static::$codMemo !== null) {
            return static::$codMemo;
        }

        return static::$codMemo = [
            'enabled' => filter_var($this->get('cod', 'enabled', true), FILTER_VALIDATE_BOOLEAN),
            'display_label' => (string) ($this->get('cod', 'display_label') ?: ''),
            'display_description' => (string) ($this->get('cod', 'display_description') ?: ''),
            'icon' => $this->get('cod', 'icon') ?: null,
        ];
    }

    /** @return array<string, mixed> */
    protected function gatewayCheckoutDisplayFields(string $group): array
    {
        return [
            'display_label' => (string) ($this->get($group, 'display_label') ?: ''),
            'display_description' => (string) ($this->get($group, 'display_description') ?: ''),
        ];
    }

    /** @return array<string, mixed> */
    protected function gatewayHomepagePlanFields(string $group): array
    {
        return [
            'show_on_homepage' => filter_var($this->get($group, 'show_on_homepage', false), FILTER_VALIDATE_BOOLEAN),
            'homepage_intro' => (string) ($this->get($group, 'homepage_intro') ?: GatewayHomepagePlanFields::DEFAULT_INTRO),
            'homepage_plan_url' => (string) ($this->get($group, 'homepage_plan_url') ?: ''),
        ];
    }

    public function isMaintenanceMode(): bool
    {
        return filter_var($this->get('site', 'maintenance_mode', false), FILTER_VALIDATE_BOOLEAN);
    }

    /** @return array<string, mixed> */
    public function site(): array
    {
        if (static::$siteMemo !== null) {
            return static::$siteMemo;
        }

        return static::$siteMemo = [
            'name' => $this->get('site', 'name') ?: config('app.name', 'چینی بازار'),
            'description' => $this->get('site', 'description', ''),
            'logo' => $this->get('site', 'logo'),
            'favicon' => $this->get('site', 'favicon'),
            'phone' => $this->get('site', 'phone', ''),
            'email' => $this->get('site', 'email', ''),
            'address' => $this->get('site', 'address', ''),
            'instagram' => $this->get('site', 'instagram', ''),
            'telegram' => $this->get('site', 'telegram', ''),
            'maintenance_mode' => $this->isMaintenanceMode(),
            'maintenance_message' => $this->get(
                'site',
                'maintenance_message',
                'سایت در حال بروزرسانی می‌باشد. لطفاً کمی بعد دوباره سر بزنید.'
            ),
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

    /** @return array<string, mixed> */
    public function smsIr(): array
    {
        $driverIsSmsIr = in_array(config('sms.driver'), ['smsir', 'sms.ir'], true);

        return [
            'api_key' => $this->get('smsir', 'api_key') ?: config('sms.smsir.api_key'),
            'template_id' => $this->get('smsir', 'template_id') ?: config('sms.smsir.template_id'),
            'otp_parameter' => $this->get('smsir', 'otp_parameter') ?: config('sms.smsir.otp_parameter', 'Code'),
            'line_number' => $this->get('smsir', 'line_number') ?: config('sms.smsir.line_number'),
            'resend_minutes' => $this->otpResendMinutes(),
            'enabled' => filter_var($this->get('smsir', 'enabled', $driverIsSmsIr), FILTER_VALIDATE_BOOLEAN),
        ];
    }

    public function otpResendMinutes(): int
    {
        $minutes = (int) ($this->get('smsir', 'resend_minutes') ?: config('sms.smsir.resend_minutes', 2));

        return max(1, min(15, $minutes));
    }

    public function otpResendSeconds(): int
    {
        return $this->otpResendMinutes() * 60;
    }
}
