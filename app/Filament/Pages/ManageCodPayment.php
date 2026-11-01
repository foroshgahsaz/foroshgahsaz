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

class ManageCodPayment extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'پرداخت در محل';

    protected static ?string $slug = 'cod';

    protected static ?string $title = 'پرداخت در محل (COD)';

    protected static ?string $navigationGroup = 'درگاه‌ها';

    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $cod = $settings->cod();

        $this->form->fill([
            'enabled' => $cod['enabled'],
            'display_label' => $cod['display_label'],
            'display_description' => $cod['display_description'],
            'icon' => ShopIconUpload::forForm($cod['icon'] ?? null),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('پرداخت در محل')->schema([
                    Forms\Components\Toggle::make('enabled')
                        ->label('فعال در تسویه حساب')
                        ->helperText('غیرفعال = گزینه COD در checkout و کوپن‌ها نمایش داده نمی‌شود.'),
                    Forms\Components\TextInput::make('display_label')
                        ->label('نام نمایشی')
                        ->placeholder('پرداخت در محل')
                        ->maxLength(120),
                    Forms\Components\TextInput::make('display_description')
                        ->label('توضیح کوتاه')
                        ->placeholder('هنگام تحویل')
                        ->maxLength(200)
                        ->columnSpanFull(),
                    ShopIconUpload::make('icon', 'gateway-icons', 'آیکون (اختیاری)')
                        ->helperText('در صورت خالی بودن، آیکون پیش‌فرض checkout استفاده می‌شود.')
                        ->columnSpanFull(),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(SettingsService $settings): void
    {
        $data = $this->form->getState();

        $settings->setMany('cod', [
            'enabled' => $data['enabled'] ?? false,
            'display_label' => trim((string) ($data['display_label'] ?? '')),
            'display_description' => trim((string) ($data['display_description'] ?? '')),
            'icon' => ShopIconUpload::fromState($data['icon'] ?? null),
        ]);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات پرداخت در محل با موفقیت ذخیره شد.')
            ->send();
    }
}
