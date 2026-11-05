<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\GatewayCheckoutDisplayFields;
use App\Filament\Support\GatewayHomepagePlanFields;
use App\Filament\Support\ShopIconUpload;
use App\Services\Payment\NopayReturnUrl;
use App\Services\Settings\SettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageNopay extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'نوپی';

    protected static ?string $slug = 'nopay';

    protected static ?string $title = 'تنظیمات درگاه نوپی (CPG)';

    protected static ?string $navigationGroup = 'درگاه‌ها';

    protected static ?int $navigationSort = 7;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $nopay = $settings->nopay();

        $this->form->fill([
            'enabled' => $nopay['enabled'],
            'api_base_url' => $nopay['api_base_url'],
            'service_username' => $nopay['service_username'],
            'service_password' => '',
            'service_password_encrypted' => $nopay['service_password_encrypted'],
            'merchant_number' => $nopay['merchant_number'],
            'public_key' => $nopay['public_key'],
            'amount_unit' => $nopay['amount_unit'],
            'callback_url' => $nopay['callback_url'],
            'test_mode' => $nopay['test_mode'],
            'test_cell_number' => $nopay['test_cell_number'],
            'icon' => ShopIconUpload::forForm($nopay['icon'] ?? null),
            ...GatewayCheckoutDisplayFields::mountState($nopay),
            ...GatewayHomepagePlanFields::mountState($nopay),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('درگاه نوپی (CPG Ver 2-5)')->schema([
                    Forms\Components\Toggle::make('enabled')
                        ->label('فعال‌سازی درگاه نوپی')
                        ->helperText('مستند: docs/CPG Ver 2-5.pdf — RequestToken و VerifyTransaction'),
                    Forms\Components\TextInput::make('api_base_url')
                        ->label('آدرس پایه API')
                        ->placeholder('https://example.com')
                        ->helperText('طبق نامه/onboarding نوپی — POST با ServiceName در body')
                        ->columnSpanFull()
                        ->required(),
                    Forms\Components\TextInput::make('service_username')
                        ->label('serviceUserName')
                        ->required(),
                    Forms\Components\TextInput::make('service_password')
                        ->label('servicePassword (متن — اختیاری)')
                        ->password()
                        ->revealable()
                        ->helperText('در صورت خالی بودن «رمز رمزنگاری‌شده»، با publicKey رمزنگاری می‌شود.'),
                    Forms\Components\Textarea::make('service_password_encrypted')
                        ->label('servicePassword رمزنگاری‌شده')
                        ->rows(2)
                        ->columnSpanFull()
                        ->helperText('خروجی ابزار CPG — در production ترجیحاً فقط همین فیلد پر شود.'),
                    Forms\Components\TextInput::make('merchant_number')
                        ->label('merchantNumber')
                        ->required(),
                    Forms\Components\Textarea::make('public_key')
                        ->label('publicKey')
                        ->rows(2)
                        ->columnSpanFull()
                        ->helperText('پیش‌فرض مستند CPG؛ برای رمزنگاری رمز سرویس.'),
                    Forms\Components\Select::make('amount_unit')
                        ->label('واحد مبلغ فروشگاه')
                        ->options([
                            'toman' => 'تومان (ارسال به API به‌صورت ریال ×۱۰)',
                            'rial' => 'ریال (ارسال بدون تبدیل)',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('callback_url')
                        ->label('returnURL / Callback')
                        ->placeholder('/payment/callback/nopay'),
                    Forms\Components\Toggle::make('test_mode')
                        ->label('محیط تست')
                        ->helperText('در تست از test_cell_number استفاده می‌شود؛ OTP نمونه در مستند: 123456'),
                    Forms\Components\TextInput::make('test_cell_number')
                        ->label('موبایل تست')
                        ->placeholder('09xxxxxxxxx'),
                    ShopIconUpload::make('icon', 'gateway-icons', 'آیکون درگاه')
                        ->columnSpanFull(),
                ])->columns(2),
                GatewayCheckoutDisplayFields::section(),
                GatewayHomepagePlanFields::section(),
            ])
            ->statePath('data');
    }

    public function save(SettingsService $settings): void
    {
        $data = $this->form->getState();

        $callbackPath = NopayReturnUrl::path([
            'callback_url' => $data['callback_url'] ?? '/payment/callback/nopay',
        ]);

        $payload = [
            'enabled' => $data['enabled'] ?? false,
            'api_base_url' => rtrim((string) ($data['api_base_url'] ?? ''), '/'),
            'service_username' => $data['service_username'] ?? '',
            'merchant_number' => $data['merchant_number'] ?? '',
            'public_key' => $data['public_key'] ?? '',
            'amount_unit' => $data['amount_unit'] ?? 'toman',
            'callback_url' => $callbackPath,
            'test_mode' => $data['test_mode'] ?? false,
            'test_cell_number' => $data['test_cell_number'] ?? '',
            'icon' => ShopIconUpload::fromState($data['icon'] ?? null),
            ...GatewayCheckoutDisplayFields::saveState($data),
            ...GatewayHomepagePlanFields::saveState($data),
        ];

        if (filled($data['service_password_encrypted'] ?? null)) {
            $payload['service_password_encrypted'] = trim((string) $data['service_password_encrypted']);
        }

        if (filled($data['service_password'] ?? null)) {
            $payload['service_password'] = (string) $data['service_password'];
        }

        $settings->setMany('nopay', $payload);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات درگاه نوپی با موفقیت ذخیره شد.')
            ->send();
    }
}
