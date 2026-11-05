<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\AmountConverter;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Settings\SettingsService;

class ShopLabels
{
    public static function orderStatus(?string $status): string
    {
        return match ($status) {
            Order::STATUS_DRAFT => 'پیش‌نویس نماینده',
            Order::STATUS_PROFORMA => 'پیش‌فاکتور',
            Order::STATUS_PENDING => 'در انتظار',
            Order::STATUS_PROCESSING => 'در حال پردازش',
            Order::STATUS_WAREHOUSE_PROCESSING => 'پردازش انبار',
            Order::STATUS_SHIPPED => 'ارسال شده',
            Order::STATUS_DELIVERED => 'تحویل شده',
            Order::STATUS_CANCELED => 'لغو شده',
            Order::STATUS_RETURNED => 'مرجوعی',
            default => $status ?? '—',
        };
    }

    public static function paymentStatus(?string $status): string
    {
        return match ($status) {
            Payment::STATUS_PENDING => 'در انتظار',
            Payment::STATUS_SUCCESS => 'موفق',
            Payment::STATUS_FAILED => 'ناموفق',
            Payment::STATUS_CANCELED => 'لغو شده',
            Payment::STATUS_REFUNDED => 'مسترد شده',
            default => $status ?? '—',
        };
    }

    public static function paymentMethod(?string $method): string
    {
        if ($method === null || $method === '') {
            return '—';
        }

        return match ($method) {
            'online' => 'پرداخت آنلاین',
            'cod' => app(PaymentGatewayCatalog::class)->codDefinition()['label'],
            default => self::gatewayLabelFromCatalog($method),
        };
    }

    protected static function gatewayLabelFromCatalog(string $method): string
    {
        $catalog = app(PaymentGatewayCatalog::class);

        if ($catalog->exists($method)) {
            return (string) ($catalog->definition($method)['label'] ?? self::gateway($method));
        }

        $label = config("payment.gateways.{$method}.label");

        return is_string($label) && $label !== '' ? $label : self::gateway($method);
    }

    /** برچسب روش پرداخت سفارش (لیست ادمین): درگاه واقعی از payments اگر orders.payment_method=online باشد. */
    public static function orderPaymentMethodLabel(Order $order): string
    {
        $method = (string) ($order->payment_method ?? '');

        if ($method !== '' && $method !== 'online') {
            return self::paymentMethod($method);
        }

        if ($method === 'online') {
            $payment = $order->relationLoaded('payments')
                ? $order->payments->sortByDesc('id')->first()
                : $order->payments()->latest('id')->first();

            if ($payment !== null && filled($payment->gateway)) {
                return self::gateway($payment->gateway);
            }
        }

        return self::paymentMethod($method);
    }

    public static function gateway(?string $gateway): string
    {
        return match ($gateway) {
            'zarinpal' => 'زرین‌پال',
            'tara' => 'تارا',
            'bajet' => 'باجت‌پی',
            'digipay' => 'دیجی‌پی',
            'parsian' => 'پارسیان',
            'sadad' => 'سداد',
            'nopay' => 'نوپی',
            'toopi' => 'نوپی',
            default => $gateway ?? '—',
        };
    }

    public static function orderNoteType(?string $type): string
    {
        return match ($type) {
            'system' => 'سیستم',
            'private' => 'یادداشت خصوصی',
            'customer' => 'یادداشت مشتری',
            default => $type ?? '—',
        };
    }

    public static function formatMoney(?int $amount): string
    {
        if ($amount === null) {
            return '—';
        }

        return number_format($amount).' تومان';
    }

    public static function shopPricesHidden(): bool
    {
        return filter_var(
            app(SettingsService::class)->get('site', 'hide_shop_prices', false),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /** قیمت فروشگاه (UI) — ریال؛ در صورت «عدم نمایش قیمت» خط تیره. */
    public static function formatShopMoney(?int $shopAmountTomans): string
    {
        if (self::shopPricesHidden()) {
            return '—';
        }

        return self::formatInvoiceMoney($shopAmountTomans);
    }

    /** @return int|null مبلغ ریال برای نمایش در UI فروشگاه */
    public static function shopDisplayAmountInRial(?int $shopAmountTomans): ?int
    {
        if ($shopAmountTomans === null || self::shopPricesHidden()) {
            return null;
        }

        return self::invoiceAmountInRial($shopAmountTomans);
    }

    /** مبلغ ذخیره‌شده در سیستم (تومان) → عدد ریال برای نمایش روی فاکتور. */
    public static function invoiceAmountInRial(int $shopAmount): int
    {
        return AmountConverter::toGateway($shopAmount, AmountConverter::UNIT_TOMAN);
    }

    /** قالب مبلغ روی فاکتور / PDF فروش (ریال، ×۱۰ نسبت به تومان داخلی). */
    public static function formatInvoiceMoney(?int $shopAmount): string
    {
        if ($shopAmount === null) {
            return '—';
        }

        return number_format(self::invoiceAmountInRial($shopAmount)).' ریال';
    }
}
