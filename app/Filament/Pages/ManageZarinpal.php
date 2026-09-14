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

class ManageZarinpal extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'زرین‌پال';

    protected static ?string $slug = 'zarinpal';

    protected static ?string $title = 'تنظیمات درگاه زرین‌پال';

    protected static ?string $navigationGroup = 'درگاه‌ها';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $zarinpal = $settings->zarinpal();

        $this->form->fill([
            'enabled' => $zarinpal['enabled'],
            'merchant_id' => $zarinpal['merchant_id'],
            'sandbox' => $zarinpal['sandbox'],
            'callback_url' => $zarinpal['callback_url'],
            'amount_unit' => $zarinpal['amount_unit'],
            'icon' => ShopIconUpload::forForm($zarinpal['icon'] ?? null),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زرین‌پال')->schema([
                    Forms\Components\Toggle::make('enabled')
                        ->label('فعال‌سازی زرین‌پال')
                        ->helperText('درگاه نقدی آنلاین'),
                    Forms\Components\TextInput::make('merchant_id')
                        ->label('Merchant ID')
                        ->placeholder('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'),
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
                    Forms\Components\TextInput::make('callback_url')
                        ->label('آدرس Callback')
                        ->placeholder('/payment/callback')
                        ->helperText('مسیر بازگشت از درگاه پس از پرداخت'),
                    ShopIconUpload::make('icon', 'gateway-icons', 'آیکون درگاه')
                        ->columnSpanFull(),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(SettingsService $settings): void
    {
        $data = $this->form->getState();

        $settings->setMany('zarinpal', [
            'enabled' => $data['enabled'] ?? false,
            'merchant_id' => $data['merchant_id'] ?? '',
            'sandbox' => $data['sandbox'] ?? true,
            'callback_url' => $data['callback_url'] ?? '/payment/callback',
            'amount_unit' => $data['amount_unit'] ?? 'toman',
            'icon' => ShopIconUpload::fromState($data['icon'] ?? null),
        ]);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات زرین‌پال با موفقیت ذخیره شد.')
            ->send();
    }
}
