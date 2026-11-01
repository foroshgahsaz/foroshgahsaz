<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\GatewayCheckoutDisplayFields;
use App\Filament\Support\GatewayHomepagePlanFields;
use App\Filament\Support\ShopIconUpload;
use App\Services\Payment\BajetReturnUrl;
use App\Services\Settings\SettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageBajetPay extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'باجت‌پی';

    protected static ?string $slug = 'bajet';

    protected static ?string $title = 'تنظیمات درگاه اعتباری باجت‌پی (جت‌پی)';

    protected static ?string $navigationGroup = 'درگاه‌ها';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $bajet = $settings->bajet();

        $this->form->fill([
            'enabled' => $bajet['enabled'],
            'sandbox' => $bajet['sandbox'],
            'username' => $bajet['username'],
            'password' => $bajet['password'],
            'terminal_id' => $bajet['terminal_id'],
            'amount_unit' => $bajet['amount_unit'],
            'return_url_base' => $bajet['return_url_base'] ?? '',
            'callback_url' => $bajet['callback_url'],
            'sandbox_base_url' => $bajet['sandbox_base_url'],
            'portal_sandbox_base_url' => $bajet['portal_sandbox_base_url'] ?? '',
            'portal_base_url' => $bajet['portal_base_url'] ?? '',
            'base_url' => $bajet['production_base_url'],
            'default_product_type' => $bajet['default_product_type'],
            'default_brand' => $bajet['default_brand'],
            'icon' => ShopIconUpload::forForm($bajet['icon'] ?? null),
            ...GatewayCheckoutDisplayFields::mountState($bajet),
            ...GatewayHomepagePlanFields::mountState($bajet),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('عمومی')->schema([
                    Forms\Components\Toggle::make('enabled')
                        ->label('فعال‌سازی باجت‌پی')
                        ->helperText('درگاه اعتباری جت‌پی (مستندات v1.4.0)'),
                    Forms\Components\Toggle::make('sandbox')
                        ->label('حالت Sandbox (تست)')
                        ->default(true),
                    Forms\Components\Select::make('amount_unit')
                        ->label('واحد مبلغ فروشگاه')
                        ->options([
                            'toman' => 'تومان (ارسال به درگاه به‌صورت ریال ×۱۰)',
                            'rial' => 'ریال (ارسال بدون تبدیل)',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('return_url_base')
                        ->label('آدرس پایه سایت (برای بازگشت از جت‌پی)')
                        ->placeholder('https://www.chinibazar.ir')
                        ->helperText('فقط دامنه سایت با https؛ خالی = مقدار APP_URL در .env. اگر قبلاً www.test.com می‌رفتید، اینجا دامنه واقعی را بگذارید.')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('callback_url')
                        ->label('مسیر callback بازگشت')
                        ->placeholder('/payment/callback/bajet')
                        ->helperText('مسیر روی همان دامنه؛ returnUrl نهایی = آدرس پایه + این مسیر + ?payment=… — جت‌پی پارامترهای id و status را هم اضافه می‌کند.')
                        ->columnSpanFull(),
                    ShopIconUpload::make('icon', 'gateway-icons', 'آیکون درگاه')
                        ->columnSpanFull(),
                ])->columns(2),
                Forms\Components\Section::make('احراز هویت و API')->schema([
                    Forms\Components\TextInput::make('username')
                        ->label('نام کاربری')
                        ->required(),
                    Forms\Components\TextInput::make('password')
                        ->label('رمز عبور')
                        ->password()
                        ->revealable()
                        ->required(),
                    Forms\Components\TextInput::make('terminal_id')
                        ->label('شناسه ترمینال (terminalId)')
                        ->required(),
                    Forms\Components\TextInput::make('sandbox_base_url')
                        ->label('Base URL تست API')
                        ->placeholder('https://host:port')
                        ->helperText('فقط دامنه با https (نه IP خام) — با IP خطای SSL cURL 60 می‌گیرید. مسیرها /api/v1/jetpay/... خودکار اضافه می‌شوند.')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('portal_sandbox_base_url')
                        ->label('آدرس پرتال پرداخت (Sandbox)')
                        ->placeholder('https://sandbox-jetpay.example.ir')
                        ->helperText('فقط وقتی API هاست داخلی برمی‌گرداند؛ دامنهٔ عمومی پرتال — مسیر و id همان پاسخ API حفظ می‌شود.')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('base_url')
                        ->label('Base URL عملیاتی API')
                        ->placeholder('https://host:port')
                        ->helperText('دامنهٔ رسمی باجت از مستند (نه https://45.x.x.x) — با IP گواهی SSL خطا می‌دهد.')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('portal_base_url')
                        ->label('آدرس پرتال پرداخت (عملیاتی)')
                        ->placeholder('https://pay.example.ir')
                        ->columnSpanFull(),
                ])->columns(2),
                Forms\Components\Section::make('سبد خرید (basketItems)')->schema([
                    Forms\Components\TextInput::make('default_product_type')
                        ->label('نوع محصول پیش‌فرض (productType)')
                        ->numeric()
                        ->default(2)
                        ->helperText('عدد ارسالی به API برای هر قلم؛ در صورت نبود برند روی محصول از فیلد برند پیش‌فرض استفاده می‌شود'),
                    Forms\Components\TextInput::make('default_brand')
                        ->label('برند پیش‌فرض')
                        ->default('general')
                        ->maxLength(64),
                ])->columns(2),
                GatewayCheckoutDisplayFields::section(),
                GatewayHomepagePlanFields::section(),
            ])
            ->statePath('data');
    }

    public function save(SettingsService $settings): void
    {
        $data = $this->form->getState();

        foreach (['sandbox_base_url', 'base_url'] as $field) {
            $url = trim((string) ($data[$field] ?? ''));
            if ($url !== '' && self::apiBaseUrlUsesIpHost($url)) {
                Notification::make()
                    ->danger()
                    ->title('آدرس API نامعتبر')
                    ->body('Base URL باید با نام دامنه (hostname) باشد، نه IP. با IP گواهی SSL خطای cURL 60 می‌دهد.')
                    ->send();

                return;
            }
        }

        $settings->setMany('bajet', [
            'enabled' => $data['enabled'] ?? false,
            'sandbox' => $data['sandbox'] ?? true,
            'username' => $data['username'] ?? '',
            'password' => $data['password'] ?? '',
            'terminal_id' => $data['terminal_id'] ?? '',
            'amount_unit' => $data['amount_unit'] ?? 'toman',
            'return_url_base' => BajetReturnUrl::normalizeStoredBase((string) ($data['return_url_base'] ?? '')),
            'callback_url' => $data['callback_url'] ?? '/payment/callback/bajet',
            'sandbox_base_url' => $data['sandbox_base_url'] ?? '',
            'portal_sandbox_base_url' => $data['portal_sandbox_base_url'] ?? '',
            'portal_base_url' => $data['portal_base_url'] ?? '',
            'base_url' => $data['base_url'] ?? '',
            'default_product_type' => (string) ($data['default_product_type'] ?? '2'),
            'default_brand' => $data['default_brand'] ?? 'general',
            'icon' => ShopIconUpload::fromState($data['icon'] ?? null),
            ...GatewayCheckoutDisplayFields::saveState($data),
            ...GatewayHomepagePlanFields::saveState($data),
        ]);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات باجت‌پی با موفقیت ذخیره شد.')
            ->send();
    }

    protected static function apiBaseUrlUsesIpHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && filter_var($host, FILTER_VALIDATE_IP) !== false;
    }
}
