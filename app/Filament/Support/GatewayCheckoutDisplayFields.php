<?php

namespace App\Filament\Support;

use Filament\Forms;

class GatewayCheckoutDisplayFields
{
    /** @param  array<string, mixed>  $gatewaySettings */
    public static function mountState(array $gatewaySettings): array
    {
        return [
            'display_label' => (string) ($gatewaySettings['display_label'] ?? ''),
            'display_description' => (string) ($gatewaySettings['display_description'] ?? ''),
        ];
    }

    /** @return array<string, mixed> */
    public static function saveState(array $formData): array
    {
        return [
            'display_label' => trim((string) ($formData['display_label'] ?? '')),
            'display_description' => trim((string) ($formData['display_description'] ?? '')),
        ];
    }

    public static function section(): Forms\Components\Section
    {
        return Forms\Components\Section::make('نمایش در تسویه حساب')
            ->description('عنوان و توضیح کوتاه روی صفحه checkout و لیست سفارش‌ها. خالی = پیش‌فرض config.')
            ->schema([
                Forms\Components\TextInput::make('display_label')
                    ->label('نام نمایشی درگاه')
                    ->placeholder('مثلاً زرین‌پال')
                    ->maxLength(120),
                Forms\Components\TextInput::make('display_description')
                    ->label('توضیح کوتاه')
                    ->placeholder('مثلاً پرداخت آنلاین با کارت بانکی')
                    ->maxLength(200)
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->collapsible();
    }
}
