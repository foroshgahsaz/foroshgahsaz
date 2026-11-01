<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\GatewayCheckoutDisplayFields;
use App\Filament\Support\GatewayHomepagePlanFields;
use App\Filament\Support\ShopIconUpload;
use App\Services\Payment\SadadReturnUrl;
use App\Services\Settings\SettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageSadad extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'سداد';

    protected static ?string $slug = 'sadad';

    protected static ?string $title = 'تنظیمات درگاه سداد (بانک ملی)';

    protected static ?string $navigationGroup = 'درگاه‌ها';

    protected static ?int $navigationSort = 6;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $sadad = $settings->sadad();

        $this->form->fill([
            'enabled' => $sadad['enabled'],
            'merchant_id' => $sadad['merchant_id'],
            'terminal_id' => $sadad['terminal_id'],
            'terminal_key' => $sadad['terminal_key'],
            'callback_url' => $sadad['callback_url'],
            'amount_unit' => $sadad['amount_unit'],
            'icon' => ShopIconUpload::forForm($sadad['icon'] ?? null),
            ...GatewayCheckoutDisplayFields::mountState($sadad),
            ...GatewayHomepagePlanFields::mountState($sadad),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('درگاه پرداخت اینترنتی سداد')->schema([
                    Forms\Components\Toggle::make('enabled')
                        ->label('فعال‌سازی درگاه سداد')
                        ->helperText('IPG بانک ملی — مرجع: docs/PHP-master.zip و sadadpsp.ir'),
                    Forms\Components\TextInput::make('merchant_id')
                        ->label('MerchantId')
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('terminal_id')
                        ->label('TerminalId')
                        ->required(),
                    Forms\Components\TextInput::make('terminal_key')
                        ->label('کلید ترمینال (Base64)')
                        ->password()
                        ->revealable()
                        ->columnSpanFull()
                        ->helperText('همان TerminalKey که در پنل سداد برای SignData داده شده است.'),
                    Forms\Components\Select::make('amount_unit')
                        ->label('واحد مبلغ فروشگاه')
                        ->options([
                            'toman' => 'تومان (ارسال به درگاه به‌صورت ریال ×۱۰)',
                            'rial' => 'ریال (ارسال بدون تبدیل)',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('callback_url')
                        ->label('آدرس ReturnUrl / Callback')
                        ->placeholder('/payment/callback/sadad')
                        ->helperText('مسیر بازگشت از درگاه؛ OrderId برابر payments.id است.'),
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

        $callbackPath = SadadReturnUrl::path([
            'callback_url' => $data['callback_url'] ?? '/payment/callback/sadad',
        ]);

        $settings->setMany('sadad', [
            'enabled' => $data['enabled'] ?? false,
            'merchant_id' => $data['merchant_id'] ?? '',
            'terminal_id' => $data['terminal_id'] ?? '',
            'terminal_key' => $data['terminal_key'] ?? '',
            'callback_url' => $callbackPath,
            'amount_unit' => $data['amount_unit'] ?? 'toman',
            'icon' => ShopIconUpload::fromState($data['icon'] ?? null),
            ...GatewayCheckoutDisplayFields::saveState($data),
            ...GatewayHomepagePlanFields::saveState($data),
        ]);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات درگاه سداد با موفقیت ذخیره شد.')
            ->send();
    }
}
