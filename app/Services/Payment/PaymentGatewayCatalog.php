<?php

namespace App\Services\Payment;

use App\Filament\Support\GatewayHomepagePlanFields;
use App\Services\Settings\SettingsService;
use App\Support\ShopMedia;
use RuntimeException;

class PaymentGatewayCatalog
{
    public const TYPE_CASH = 'cash';

    public const TYPE_CREDIT = 'credit';

    /** @var array<string, array<string, mixed>>|null */
    protected ?array $allMemo = null;

    /** @var list<array<string, mixed>>|null */
    protected ?array $enabledMemo = null;

    public function __construct(
        protected SettingsService $settings,
    ) {}

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        if ($this->allMemo !== null) {
            return $this->allMemo;
        }

        $gateways = [];

        foreach (array_keys(config('payment.gateways', [])) as $name) {
            $gateways[$name] = $this->definition($name);
        }

        return $this->allMemo = $gateways;
    }

    /** @return array<string, mixed> */
    public function definition(string $name): array
    {
        $config = config("payment.gateways.{$name}", []);
        $settings = $this->settingsForGateway($name);

        $label = trim((string) ($settings['display_label'] ?? ''));
        if ($label === '') {
            $label = (string) ($config['label'] ?? $name);
        }

        $description = trim((string) ($settings['display_description'] ?? ''));
        if ($description === '') {
            $description = (string) ($config['description'] ?? '');
        }

        return [
            'name' => $name,
            'type' => $config['type'] ?? self::TYPE_CASH,
            'label' => $label,
            'description' => $description,
            'enabled' => $this->isEnabled($name),
            'icon' => $this->iconUrl($name),
        ];
    }

    public function codEnabled(): bool
    {
        return (bool) ($this->settings->cod()['enabled'] ?? true);
    }

    /** @return array<string, mixed> */
    public function codDefinition(): array
    {
        $cod = $this->settings->cod();

        $label = trim((string) ($cod['display_label'] ?? ''));
        if ($label === '') {
            $label = 'پرداخت در محل';
        }

        $description = trim((string) ($cod['display_description'] ?? ''));
        if ($description === '') {
            $description = 'هنگام تحویل';
        }

        $iconPath = $cod['icon'] ?? null;

        return [
            'name' => 'cod',
            'type' => self::TYPE_CASH,
            'label' => $label,
            'description' => $description,
            'enabled' => $this->codEnabled(),
            'icon' => ShopMedia::url(is_string($iconPath) && $iconPath !== '' ? $iconPath : null),
        ];
    }

    public function isEnabled(string $name): bool
    {
        return match ($name) {
            'zarinpal' => (bool) ($this->settings->zarinpal()['enabled'] ?? false),
            'tara' => (bool) ($this->settings->tara()['enabled'] ?? false),
            'bajet' => (bool) ($this->settings->bajet()['enabled'] ?? false),
            'digipay' => (bool) ($this->settings->digipay()['enabled'] ?? false),
            'parsian' => (bool) ($this->settings->parsian()['enabled'] ?? false),
            'sadad' => (bool) ($this->settings->sadad()['enabled'] ?? false),
            default => false,
        };
    }

    public function exists(string $name): bool
    {
        return is_string(config("payment.gateways.{$name}.driver"));
    }

    /** @return list<array<string, mixed>> */
    public function enabled(?string $type = null): array
    {
        if ($type === null && $this->enabledMemo !== null) {
            return $this->enabledMemo;
        }

        $items = [];

        foreach ($this->all() as $gateway) {
            if (! $gateway['enabled']) {
                continue;
            }

            if ($type !== null && $gateway['type'] !== $type) {
                continue;
            }

            $items[] = $gateway;
        }

        if ($type === null) {
            $this->enabledMemo = $items;
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    public function cash(): array
    {
        return $this->enabled(self::TYPE_CASH);
    }

    /** @return list<array<string, mixed>> */
    public function credit(): array
    {
        return $this->enabled(self::TYPE_CREDIT);
    }

    /**
     * کارت‌های طرح درگاه برای صفحه اصلی (فقط درگاه فعال + تیک نمایش + URL طرح).
     *
     * @return list<array{name: string, label: string, intro: string, plan_url: string, icon: ?string}>
     */
    public function homepagePlanCards(): array
    {
        $cards = [];

        foreach (array_keys(config('payment.gateways', [])) as $name) {
            if (! $this->isEnabled($name)) {
                continue;
            }

            $settings = match ($name) {
                'zarinpal' => $this->settings->zarinpal(),
                'tara' => $this->settings->tara(),
                'bajet' => $this->settings->bajet(),
                'digipay' => $this->settings->digipay(),
                'parsian' => $this->settings->parsian(),
                'sadad' => $this->settings->sadad(),
                default => [],
            };

            if (! (bool) ($settings['show_on_homepage'] ?? false)) {
                continue;
            }

            $planUrl = trim((string) ($settings['homepage_plan_url'] ?? ''));
            if ($planUrl === '') {
                continue;
            }

            if (! str_starts_with($planUrl, 'http://') && ! str_starts_with($planUrl, 'https://')) {
                $planUrl = url($planUrl);
            }

            $intro = trim((string) ($settings['homepage_intro'] ?? ''));
            if ($intro === '') {
                $intro = GatewayHomepagePlanFields::DEFAULT_INTRO;
            }

            $cards[] = [
                'name' => $name,
                'label' => (string) ($this->definition($name)['label'] ?? $name),
                'intro' => $intro,
                'plan_url' => $planUrl,
                'icon' => $this->iconUrl($name),
            ];
        }

        return $cards;
    }

    /** @return list<string> */
    public function enabledNames(): array
    {
        return array_column($this->enabled(), 'name');
    }

    public function iconUrl(string $name): ?string
    {
        $path = match ($name) {
            'zarinpal' => $this->settings->zarinpal()['icon'] ?? null,
            'tara' => $this->settings->tara()['icon'] ?? null,
            'bajet' => $this->settings->bajet()['icon'] ?? null,
            'digipay' => $this->settings->digipay()['icon'] ?? null,
            'parsian' => $this->settings->parsian()['icon'] ?? null,
            'sadad' => $this->settings->sadad()['icon'] ?? null,
            default => null,
        };

        if (! is_string($path) || $path === '') {
            $path = config("payment.gateways.{$name}.icon");
        }

        return ShopMedia::url(is_string($path) ? $path : null);
    }

    public function assertEnabled(string $name): void
    {
        if (! $this->exists($name)) {
            throw new RuntimeException('درگاه پرداخت پشتیبانی نمی‌شود.');
        }

        if (! $this->isEnabled($name)) {
            throw new RuntimeException('این درگاه پرداخت فعال نیست.');
        }
    }

    /** @return array<string, mixed> */
    protected function settingsForGateway(string $name): array
    {
        return match ($name) {
            'zarinpal' => $this->settings->zarinpal(),
            'tara' => $this->settings->tara(),
            'bajet' => $this->settings->bajet(),
            'digipay' => $this->settings->digipay(),
            'parsian' => $this->settings->parsian(),
            'sadad' => $this->settings->sadad(),
            default => [],
        };
    }
}
