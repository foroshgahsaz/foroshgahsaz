<?php

namespace App\Services\Checkout;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Support\ShopLabels;
use Carbon\Carbon;
use RuntimeException;

class CouponService
{
    public function __construct(
        protected PaymentGatewayCatalog $gateways,
    ) {}

    public function findValid(string $code, User $user, int $orderTotal, ?string $paymentMethod = null): Coupon
    {
        $result = $this->validate($code, $user, $orderTotal, $paymentMethod);

        if (! $result['valid']) {
            throw new RuntimeException($result['message'] ?? 'کد تخفیف معتبر نیست.');
        }

        return $result['coupon'];
    }

    /**
     * @return array{valid: bool, coupon: ?Coupon, message: ?string}
     */
    public function validate(string $code, User $user, int $orderTotal, ?string $paymentMethod = null): array
    {
        $normalized = strtoupper(trim($code));

        if ($normalized === '') {
            return ['valid' => true, 'coupon' => null, 'message' => null];
        }

        $coupon = Coupon::query()->where('code', $normalized)->first();

        if (! $coupon) {
            return [
                'valid' => false,
                'coupon' => null,
                'message' => 'کد تخفیف یافت نشد. املای کد را بررسی کنید.',
            ];
        }

        $message = $this->failureMessage($coupon, $user, $orderTotal, $paymentMethod);

        if ($message !== null) {
            return ['valid' => false, 'coupon' => null, 'message' => $message];
        }

        return ['valid' => true, 'coupon' => $coupon, 'message' => null];
    }

    public function failureMessage(Coupon $coupon, ?User $user, int $orderTotal, ?string $paymentMethod = null): ?string
    {
        if (! $coupon->is_active) {
            return 'این کد تخفیف غیرفعال است.';
        }

        $now = Carbon::now();

        if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
            return 'زمان استفاده از این کد هنوز فرا نرسیده است (شروع: '.$coupon->starts_at->shopJalali().').';
        }

        if ($coupon->expires_at && $now->gt($coupon->expires_at)) {
            return 'مهلت استفاده از این کد تخفیف به پایان رسیده است.';
        }

        if ($coupon->minimum_order_amount && $orderTotal < $coupon->minimum_order_amount) {
            return 'حداقل مبلغ سبد برای این کد '
                .ShopLabels::formatMoney($coupon->minimum_order_amount)
                .' است؛ مبلغ فعلی سبد '
                .ShopLabels::formatMoney($orderTotal)
                .' است.';
        }

        if ($coupon->usage_limit !== null && $coupon->usages()->count() >= $coupon->usage_limit) {
            return 'سقف استفاده از این کد تخفیف تکمیل شده است.';
        }

        if ($user && $coupon->usage_per_user !== null) {
            $userUsageCount = $coupon->usages()->where('user_id', $user->id)->count();
            if ($userUsageCount >= $coupon->usage_per_user) {
                return 'شما قبلاً حداکثر دفعات مجاز ('.$coupon->usage_per_user.') از این کد را استفاده کرده‌اید.';
            }
        }

        if ($paymentMethod !== null && $paymentMethod !== '') {
            $allowed = $coupon->allowedPaymentGateways();
            if ($allowed !== [] && ! in_array($paymentMethod, $allowed, true)) {
                return 'این کد برای درگاه پرداخت انتخاب‌شده معتبر نیست. درگاه‌های مجاز: '
                    .$this->formatGatewayLabels($allowed).'.';
            }
        }

        return null;
    }

    public function isValidFor(Coupon $coupon, ?User $user, int $orderTotal, ?string $paymentMethod = null): bool
    {
        return $this->failureMessage($coupon, $user, $orderTotal, $paymentMethod) === null;
    }

    public function recordUsage(Coupon $coupon, User $user, Order $order): void
    {
        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'used_at' => now(),
        ]);
    }

    /** @param  list<string>  $gatewayNames */
    protected function formatGatewayLabels(array $gatewayNames): string
    {
        $labels = [];
        foreach ($gatewayNames as $name) {
            if ($name === 'cod') {
                $labels[] = $this->gateways->codDefinition()['label'];
                continue;
            }
            $labels[] = $this->gateways->definition($name)['label'] ?? $name;
        }

        return implode('، ', $labels);
    }
}
