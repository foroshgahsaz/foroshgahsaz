<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\ShopIconUpload;
use App\Services\Settings\SettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageTara extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'تارا';

    protected static ?string $slug = 'tara';

    protected static ?string $title = 'تنظیمات درگاه اعتباری تارا';

    protected static ?string $navigationGroup = 'درگاه‌ها';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $tara = $settings->tara();

        $this->form->fill([
            'enabled' => $tara['enabled'],
            'sandbox' => $tara['sandbox'],
            'username' => $tara['username'],
            'password' => $tara['password'],
            'service_id' => $tara['service_id'],
            'amount_unit' => $tara['amount_unit'],
            'callback_url' => $tara['callback_url'],
            'client_ip' => $tara['client_ip'],
            'default_group' => $tara['default_group'],
            'default_group_title' => $tara['default_group_title'],
            'sandbox_base_url' => $tara['sandbox_base_url'],
            'base_url' => $tara['production_base_url'],
            'refund_principal' => $tara['refund_principal'],
            'refund_password' => $tara['refund_password'],
            'sandbox_refund_base_url' => $tara['sandbox_refund_base_url'],
            'refund_base_url' => $tara['production_refund_base_url'],
            'icon' => ShopIconUpload::forForm($tara['icon'] ?? null),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('عمومی')->schema([
                    Forms\Components\Toggle::make('enabled')
                        ->label('فعال‌سازی تارا')
                        ->helperText('درگاه اعتباری؛ کاربر می‌تواند بخشی از مبلغ را با اعتبار تارا بپردازد'),
                    Forms\Components\Toggle::make('sandbox')
                        ->label('حالت Sandbox (تست)')
                        ->default(true),
                    Forms\Components\Select::make('amount_unit')
                        ->label('واحد مبلغ فروشگاه')
                        ->options([
                            'toman' => 'تومان (ارسال به تارا به‌صورت ریال ×۱۰)',
                            'rial' => 'ریال (ارسال بدون تبدیل)',
                        ])
                        ->required()
                        ->helperText('مبالغ فروشگاه تومان است؛ برای تارا معمولاً تومان را انتخاب کنید'),
                    Forms\Components\TextInput::make('callback_url')
                        ->label('آدرس Callback')
                        ->placeholder('/payment/callback/tara'),
                    Forms\Components\TextInput::make('client_ip')
                        ->label('IP سرور پذیرنده')
                        ->placeholder('مثال: 1.2.3.4')
                        ->helperText('IP سفیدشده نزد تارا. اگر خالی باشد از IP درخواست استفاده می‌شود'),
                    ShopIconUpload::make('icon', 'gateway-icons', 'آیکون درگاه')
                        ->columnSpanFull(),
                ])->columns(2),
                Forms\Components\Section::make('خرید آنلاین')->schema([
                    Forms\Components\TextInput::make('username')
                        ->label('نام کاربری (Username)')
                        ->placeholder('tara_ipg'),
                    Forms\Components\TextInput::make('password')
                        ->label('رمز عبور')
                        ->password()
                        ->revealable(),
                    Forms\Components\TextInput::make('service_id')
                        ->label('Service ID')
                        ->placeholder('101'),
                    Forms\Components\TextInput::make('default_group')
                        ->label('کد گروه کالایی تارا')
                        ->placeholder('1'),
                    Forms\Components\TextInput::make('default_group_title')
                        ->label('عنوان گروه کالایی')
                        ->placeholder('عمومی'),
                    Forms\Components\TextInput::make('sandbox_base_url')
                        ->label('Base URL تست خرید')
                        ->placeholder('https://stage-pay.tara360.ir/pay')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('base_url')
                        ->label('Base URL عملیاتی خرید')
                        ->placeholder('https://pay.tara360.ir/pay')
                        ->columnSpanFull(),
                ])->columns(2),
                Forms\Components\Section::make('مرجوعی')->schema([
                    Forms\Components\TextInput::make('refund_principal')
                        ->label('Principal مرجوعی')
                        ->placeholder('ipg_refund'),
                    Forms\Components\TextInput::make('refund_password')
                        ->label('رمز مرجوعی')
                        ->password()
                        ->revealable(),
                    Forms\Components\TextInput::make('sandbox_refund_base_url')
                        ->label('Base URL تست مرجوعی')
                        ->placeholder('https://stage.tara-club.ir/club')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('refund_base_url')
                        ->label('Base URL عملیاتی مرجوعی')
                        ->placeholder('https://club.tara-club.ir/club')
                        ->columnSpanFull(),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(SettingsService $settings): void
    {
        $data = $this->form->getState();

        $settings->setMany('tara', [
            'enabled' => $data['enabled'] ?? false,
            'sandbox' => $data['sandbox'] ?? true,
            'username' => $data['username'] ?? '',
            'password' => $data['password'] ?? '',
            'service_id' => $data['service_id'] ?? '',
            'amount_unit' => $data['amount_unit'] ?? 'toman',
            'callback_url' => $data['callback_url'] ?? '/payment/callback/tara',
            'client_ip' => $data['client_ip'] ?? '',
            'default_group' => $data['default_group'] ?? '1',
            'default_group_title' => $data['default_group_title'] ?? 'عمومی',
            'sandbox_base_url' => $data['sandbox_base_url'] ?? '',
            'base_url' => $data['base_url'] ?? '',
            'refund_principal' => $data['refund_principal'] ?? '',
            'refund_password' => $data['refund_password'] ?? '',
            'sandbox_refund_base_url' => $data['sandbox_refund_base_url'] ?? '',
            'refund_base_url' => $data['refund_base_url'] ?? '',
            'icon' => ShopIconUpload::fromState($data['icon'] ?? null),
        ]);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات تارا با موفقیت ذخیره شد.')
            ->send();
    }
}
