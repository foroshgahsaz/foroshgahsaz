<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\GatewayCheckoutDisplayFields;
use App\Filament\Support\GatewayHomepagePlanFields;
use App\Filament\Support\ShopIconUpload;
use App\Services\Settings\SettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageDigiPay extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'دیجی‌پی';

    protected static ?string $slug = 'digipay';

    protected static ?string $title = 'تنظیمات درگاه اعتباری دیجی‌پی';

    protected static ?string $navigationGroup = 'درگاه‌ها';

    protected static ?int $navigationSort = 5;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $digipay = $settings->digipay();

        $this->form->fill([
            'enabled' => $digipay['enabled'],
            'sandbox' => $digipay['sandbox'],
            'username' => $digipay['username'],
            'password' => $digipay['password'],
            'client_id' => $digipay['client_id'],
            'client_secret' => $digipay['client_secret'],
            'amount_unit' => $digipay['amount_unit'],
            'callback_url' => $digipay['callback_url'],
            'ticket_type' => $digipay['ticket_type'],
            'default_product_type' => $digipay['default_product_type'],
            'sandbox_base_url' => $digipay['sandbox_base_url'],
            'base_url' => $digipay['production_base_url'],
            'icon' => ShopIconUpload::forForm($digipay['icon'] ?? null),
            'show_product_installment_promo' => $digipay['show_product_installment_promo'] ?? false,
            'product_installment_promo_text' => $digipay['product_installment_promo_text'] ?? '',
            ...GatewayCheckoutDisplayFields::mountState($digipay),
            ...GatewayHomepagePlanFields::mountState($digipay),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('عمومی')->schema([
                    Forms\Components\Toggle::make('enabled')
                        ->label('فعال‌سازی دیجی‌پی')
                        ->helperText('درگاه اعتباری ۴ قسط دیجی‌پی (UPG)'),
                    Forms\Components\Toggle::make('sandbox')
                        ->label('حالت Sandbox (UAT)')
                        ->default(false)
                        ->helperText('روی سرور واقعی (chinibazar) حتماً خاموش باشد؛ UAT از داخل پاد ممکن است resolve نشود.'),
                    Forms\Components\Select::make('amount_unit')
                        ->label('واحد مبلغ فروشگاه')
                        ->options([
                            'toman' => 'تومان (ارسال به API به‌صورت ریال ×۱۰)',
                            'rial' => 'ریال (ارسال بدون تبدیل)',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('callback_url')
                        ->label('آدرس بازگشت (Callback)')
                        ->placeholder('/payment/callback/digipay')
                        ->helperText('دیجی‌پی با POST به این آدرس برمی‌گردد؛ شناسه payment در query است.'),
                    ShopIconUpload::make('icon', 'gateway-icons', 'آیکون درگاه')
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('show_product_installment_promo')
                        ->label('نمایش بنر اقساطی در صفحه محصول')
                        ->helperText('بالای موجودی در باکس خرید، متن قابل تنظیم زیر به‌همراه آیکون درگاه (۴۸×۴۸) نمایش داده می‌شود.')
                        ->live()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('product_installment_promo_text')
                        ->label('متن بنر صفحه محصول')
                        ->placeholder('این درگاه اعتباری دیجی‌پی در ۴ قسط')
                        ->maxLength(200)
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('show_product_installment_promo'))
                        ->columnSpanFull(),
                ])->columns(2),
                Forms\Components\Section::make('OAuth و API')->schema([
                    Forms\Components\TextInput::make('username')
                        ->label('نام کاربری')
                        ->required(),
                    Forms\Components\TextInput::make('password')
                        ->label('رمز عبور')
                        ->password()
                        ->revealable()
                        ->required(),
                    Forms\Components\TextInput::make('client_id')
                        ->label('Client ID')
                        ->required(),
                    Forms\Components\TextInput::make('client_secret')
                        ->label('Client Secret')
                        ->password()
                        ->revealable()
                        ->required(),
                    Forms\Components\TextInput::make('sandbox_base_url')
                        ->label('Base URL تست')
                        ->placeholder('https://uat.mydigipay.info/digipay/api')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('base_url')
                        ->label('Base URL عملیاتی')
                        ->placeholder('https://api.mydigipay.com/digipay/api')
                        ->columnSpanFull(),
                ])->columns(2),
                GatewayCheckoutDisplayFields::section(),
                GatewayHomepagePlanFields::section(),
                Forms\Components\Section::make('تیکت و سبد')->schema([
                    Forms\Components\TextInput::make('ticket_type')
                        ->label('نوع تیکت (type)')
                        ->numeric()
                        ->default(11)
                        ->helperText('مقدار پیش‌فرض افرسانه ووکامرس: 11'),
                    Forms\Components\TextInput::make('default_product_type')
                        ->label('productType هر قلم')
                        ->numeric()
                        ->default(1),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(SettingsService $settings): void
    {
        $data = $this->form->getState();

        $settings->setMany('digipay', [
            'enabled' => $data['enabled'] ?? false,
            'sandbox' => $data['sandbox'] ?? true,
            'username' => $data['username'] ?? '',
            'password' => $data['password'] ?? '',
            'client_id' => $data['client_id'] ?? '',
            'client_secret' => $data['client_secret'] ?? '',
            'amount_unit' => $data['amount_unit'] ?? 'toman',
            'callback_url' => $data['callback_url'] ?? '/payment/callback/digipay',
            'ticket_type' => (string) ($data['ticket_type'] ?? '11'),
            'default_product_type' => (string) ($data['default_product_type'] ?? '1'),
            'sandbox_base_url' => $data['sandbox_base_url'] ?? '',
            'base_url' => $data['base_url'] ?? '',
            'access_token' => '',
            'refresh_token' => '',
            'icon' => ShopIconUpload::fromState($data['icon'] ?? null),
            'show_product_installment_promo' => $data['show_product_installment_promo'] ?? false,
            'product_installment_promo_text' => trim((string) ($data['product_installment_promo_text'] ?? '')),
            ...GatewayCheckoutDisplayFields::saveState($data),
            ...GatewayHomepagePlanFields::saveState($data),
        ]);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات دیجی‌پی ذخیره شد. توکن‌ها برای احراز هویت مجدد پاک شدند.')
            ->send();
    }
}
