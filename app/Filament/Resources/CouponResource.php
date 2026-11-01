<?php

namespace App\Filament\Resources;

use App\Filament\Pages\CouponAnalyticsPage;
use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Support\AdminAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'کدهای تخفیف';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static ?int $navigationSort = 5;

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return AdminAccess::canManageShopInAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->label('کد')->required()->unique(ignoreRecord: true),
            Forms\Components\Select::make('type')->label('نوع')->options(['percent' => 'درصدی', 'fixed' => 'مبلغ ثابت'])->required(),
            Forms\Components\TextInput::make('value')->label('مقدار')->numeric()->required(),
            Forms\Components\TextInput::make('max_discount')->label('سقف تخفیف')->numeric(),
            Forms\Components\TextInput::make('usage_limit')->label('محدودیت کل')->numeric(),
            Forms\Components\TextInput::make('usage_per_user')->label('محدودیت هر کاربر')->numeric()->default(1),
            Forms\Components\TextInput::make('minimum_order_amount')->label('حداقل سفارش')->numeric(),
            Forms\Components\DateTimePicker::make('starts_at')->label('شروع'),
            Forms\Components\DateTimePicker::make('expires_at')->label('پایان'),
            Forms\Components\Toggle::make('is_active')->label('فعال')->default(true),
            Forms\Components\Toggle::make('stackable_with_other_coupons')
                ->label('قابل ترکیب با کدهای تخفیف دیگر')
                ->helperText('اگر غیرفعال باشد، در آینده هنگام اعمال چند کد هم‌زمان این کد با سایر کدها ترکیب نمی‌شود.')
                ->default(false),
            Forms\Components\Select::make('allowed_payment_gateways')
                ->label('درگاه‌های مجاز')
                ->multiple()
                ->searchable()
                ->options(fn () => self::paymentGatewayOptions())
                ->helperText('خالی بگذارید = همه درگاه‌های فعال و پرداخت در محل.')
                ->placeholder('یک گزینه را انتخاب کنید')
                ->columnSpanFull()
                ->dehydrateStateUsing(fn (?array $state): ?array => filled($state) ? array_values($state) : null)
                ->afterStateHydrated(function (Forms\Components\Select $component, $state): void {
                    $component->state(is_array($state) ? $state : []);
                }),
            Forms\Components\TextInput::make('description')->label('توضیح'),
        ]);
    }

    /** @return array<string, string> */
    protected static function paymentGatewayOptions(): array
    {
        $catalog = app(PaymentGatewayCatalog::class);
        $options = [];

        if ($catalog->codEnabled()) {
            $options['cod'] = (string) $catalog->codDefinition()['label'];
        }

        foreach ($catalog->enabled() as $gateway) {
            $options[$gateway['name']] = (string) $gateway['label'];
        }

        return $options;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->label('کد')->searchable(),
            Tables\Columns\TextColumn::make('type')->label('نوع'),
            Tables\Columns\TextColumn::make('value')->label('مقدار'),
            Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
        ])->actions([
            Tables\Actions\Action::make('analytics')
                ->label('آنالیز')
                ->icon('heroicon-o-chart-bar')
                ->url(fn (Coupon $record): string => CouponAnalyticsPage::getUrl(['record' => $record->getKey()])),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
