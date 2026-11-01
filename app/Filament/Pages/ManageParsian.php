<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\GatewayCheckoutDisplayFields;
use App\Filament\Support\GatewayHomepagePlanFields;
use App\Filament\Support\ShopIconUpload;
use App\Services\Payment\ParsianCallbackUrl;
use App\Services\Settings\SettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageParsian extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'پارسیان';

    protected static ?string $slug = 'parsian';

    protected static ?string $title = 'تنظیمات درگاه پارسیان (PEC)';

    protected static ?string $navigationGroup = 'درگاه‌ها';

    protected static ?int $navigationSort = 5;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $parsian = $settings->parsian();

        $this->form->fill([
            'enabled' => $parsian['enabled'],
            'login_account' => $parsian['login_account'],
            'callback_url' => $parsian['callback_url'],
            'amount_unit' => $parsian['amount_unit'],
            'icon' => ShopIconUpload::forForm($parsian['icon'] ?? null),
            ...GatewayCheckoutDisplayFields::mountState($parsian),
            ...GatewayHomepagePlanFields::mountState($parsian),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تجارت الکترونیک پارسیان (PEC)')->schema([
                    Forms\Components\Toggle::make('enabled')
                        ->label('فعال‌سازی درگاه پارسیان')
                        ->helperText('درگاه نقدی آنلاین (Shaparak IPG)'),
                    Forms\Components\TextInput::make('login_account')
                        ->label('شناسه پذیرنده (LoginAccount / PIN)')
                        ->password()
                        ->revealable()
                        ->columnSpanFull(),
                    Forms\Components\Select::make('amount_unit')
                        ->label('واحد مبلغ فروشگاه')
                        ->options([
                            'toman' => 'تومان (ارسال به درگاه به‌صورت ریال ×۱۰)',
                            'rial' => 'ریال (ارسال بدون تبدیل)',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('callback_url')
                        ->label('آدرس Callback')
                        ->placeholder('/payment/callback/pec')
                        ->helperText('فقط مسیر — باید عین پنل پارسیان باشد (رایج: /payment/callback/pec یا /payment/callback/parsian). بدون ?payment='),
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

        $callbackPath = ParsianCallbackUrl::path([
            'callback_url' => $data['callback_url'] ?? '/payment/callback/pec',
        ]);

        $settings->setMany('parsian', [
            'enabled' => $data['enabled'] ?? false,
            'login_account' => $data['login_account'] ?? '',
            'callback_url' => $callbackPath,
            'amount_unit' => $data['amount_unit'] ?? 'toman',
            'icon' => ShopIconUpload::fromState($data['icon'] ?? null),
            ...GatewayCheckoutDisplayFields::saveState($data),
            ...GatewayHomepagePlanFields::saveState($data),
        ]);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات درگاه پارسیان با موفقیت ذخیره شد.')
            ->send();
    }
}
