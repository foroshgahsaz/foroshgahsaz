<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Support\AdminTable;
use App\Filament\Support\UserTableColumns;
use App\Filament\Support\OrderDeleteActions;
use App\Models\Order;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Support\AdminAccess;
use App\Support\ShopLabels;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'سفارش‌ها';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool
    {
        return AdminAccess::canAccessAdminResource(static::class);
    }

    public static function canDelete($record): bool
    {
        return AdminAccess::canManageShopInAdmin();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'representative', 'payments']);
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->recordUrl(fn (Order $record): string => static::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('tracking_code')
                    ->label('کد سفارش')
                    ->searchable()
                    ->copyable()
                    ->weight('medium')
                    ->color('primary'),
                UserTableColumns::nameOnly(),
                Tables\Columns\TextColumn::make('representative.name')
                    ->label('نماینده')
                    ->placeholder('—')
                    ->formatStateUsing(fn (Order $record): string => $record->representativeLabel())
                    ->toggleable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('final_amount')
                    ->label('مبلغ')
                    ->alignEnd()
                    ->formatStateUsing(fn (?int $state) => ShopLabels::formatMoney($state)),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('روش پرداخت')
                    ->badge()
                    ->formatStateUsing(fn (Order $record): string => $record->paymentMethodLabel()),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => ShopLabels::orderStatus($state)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ')
                    ->shopJalaliDateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_gateway')
                    ->label('روش پرداخت')
                    ->options(fn (): array => static::paymentGatewayFilterOptions())
                    ->query(fn (Builder $query, array $data): Builder => static::applyPaymentGatewayFilter(
                        $query,
                        $data['value'] ?? null,
                    )),
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(static::statusFilterOptions()),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('جزئیات')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->button()
                    ->outlined(),
                OrderDeleteActions::tableDeleteAction(),
            ])
            ->actionsColumnLabel('عملیات')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    OrderDeleteActions::tableBulkDeleteAction(),
                ]),
            ]);
    }

    /** @return array<string, string> */
    protected static function paymentGatewayFilterOptions(): array
    {
        $options = [];

        foreach (app(PaymentGatewayCatalog::class)->enabled() as $gateway) {
            $name = (string) ($gateway['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $options[$name] = (string) ($gateway['label'] ?? ShopLabels::gateway($name));
        }

        if (app(PaymentGatewayCatalog::class)->codEnabled()) {
            $options['cod'] = ShopLabels::paymentMethod('cod');
        }

        return $options;
    }

    protected static function applyPaymentGatewayFilter(Builder $query, mixed $gateway): Builder
    {
        if (! is_string($gateway) || $gateway === '') {
            return $query;
        }

        if ($gateway === 'cod') {
            return $query->where('payment_method', 'cod');
        }

        return $query->where(function (Builder $inner) use ($gateway): void {
            $inner->where('payment_method', $gateway)
                ->orWhere(function (Builder $online) use ($gateway): void {
                    $online->where('payment_method', 'online')
                        ->whereHas('payments', fn (Builder $payments): Builder => $payments->where('gateway', $gateway));
                });
        });
    }

    /** @return array<string, string> */
    protected static function statusFilterOptions(): array
    {
        return [
            Order::STATUS_DRAFT => ShopLabels::orderStatus(Order::STATUS_DRAFT),
            Order::STATUS_PROFORMA => ShopLabels::orderStatus(Order::STATUS_PROFORMA),
            Order::STATUS_PENDING => ShopLabels::orderStatus(Order::STATUS_PENDING),
            Order::STATUS_PROCESSING => ShopLabels::orderStatus(Order::STATUS_PROCESSING),
            Order::STATUS_WAREHOUSE_PROCESSING => ShopLabels::orderStatus(Order::STATUS_WAREHOUSE_PROCESSING),
            Order::STATUS_SHIPPED => ShopLabels::orderStatus(Order::STATUS_SHIPPED),
            Order::STATUS_DELIVERED => ShopLabels::orderStatus(Order::STATUS_DELIVERED),
            Order::STATUS_CANCELED => ShopLabels::orderStatus(Order::STATUS_CANCELED),
            Order::STATUS_RETURNED => ShopLabels::orderStatus(Order::STATUS_RETURNED),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
