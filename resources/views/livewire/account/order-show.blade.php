@php
    use App\Support\ShopLabels;
@endphp

<x-account-layout title="جزئیات سفارش {{ $order->tracking_code }}" active="orders">
    @if (session('success'))
        <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-xl text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-xl text-sm">{{ session('error') }}</div>
    @endif
    @if (session('payment_status'))
        @php
            $paymentStatus = session('payment_status');
            $remainingFlash = session('payment_remaining');
        @endphp
        <div class="mb-4 p-3 rounded-xl text-sm {{ $paymentStatus === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
            @if ($paymentStatus === 'success' && $remainingFlash)
                پرداخت ثبت شد. مانده سفارش: {{ number_format($remainingFlash) }} تومان. تا تسویه کامل، سفارش تکمیل نمی‌شود.
            @elseif ($paymentStatus === 'success')
                وضعیت پرداخت: موفق
            @else
                وضعیت پرداخت: ناموفق
            @endif
        </div>
    @endif

    <div class="shop-card p-5 mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full">
                    {{ ShopLabels::orderStatus($order->status) }}
                </span>
                <span class="text-xs text-gray-400">{{ $order->created_at->format('Y/m/d H:i') }}</span>
                <span class="text-xs text-gray-500">کد: {{ $order->tracking_code }}</span>
            </div>
            @if ($order->canPayAgain())
                <span class="text-xs bg-amber-50 text-amber-700 px-3 py-1 rounded-full">
                    مانده: {{ number_format($order->remainingAmount()) }} تومان
                </span>
            @endif
        </div>

        <div class="divide-y divide-gray-100">
            @foreach ($order->items as $item)
                <div class="flex justify-between py-3 text-sm gap-4">
                    <div>
                        <span class="font-medium">{{ $item->product_name }}</span>
                        <span class="text-gray-400"> × {{ $item->quantity }}</span>
                        @if($item->sku)
                            <span class="block text-xs text-gray-400 mt-0.5">SKU: {{ $item->sku }}</span>
                        @endif
                    </div>
                    <span class="font-bold shrink-0">{{ number_format($item->total_price) }} تومان</span>
                </div>
            @endforeach
        </div>

        <dl class="border-t border-gray-100 mt-4 pt-4 space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">جمع اقلام</dt><dd>{{ number_format($order->total_amount) }} تومان</dd></div>
            @if($order->discount_amount > 0)
                <div class="flex justify-between text-emerald-600"><dt>تخفیف</dt><dd>−{{ number_format($order->discount_amount) }} تومان</dd></div>
            @endif
            @if($order->shipping_amount > 0)
                <div class="flex justify-between"><dt class="text-gray-500">هزینه ارسال</dt><dd>{{ number_format($order->shipping_amount) }} تومان</dd></div>
            @endif
            <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                <dt class="font-bold text-navy">مبلغ نهایی</dt>
                <dd class="text-lg font-black text-brand-green">{{ number_format($order->final_amount) }} تومان</dd>
            </div>
            @if ($order->paidAmount() > 0)
                <div class="flex justify-between text-emerald-700">
                    <dt>پرداخت‌شده</dt>
                    <dd>{{ number_format($order->paidAmount()) }} تومان</dd>
                </div>
            @endif
            @if ($order->remainingAmount() > 0 && $order->payment_method === 'online')
                <div class="flex justify-between text-amber-700">
                    <dt>مانده</dt>
                    <dd>{{ number_format($order->remainingAmount()) }} تومان</dd>
                </div>
            @endif
        </dl>
    </div>

    @if($order->address || $order->shippingMethod)
        <div class="shop-card p-5 mb-4">
            <h2 class="font-bold text-navy mb-3">آدرس و ارسال</h2>
            @if($order->address)
                <p class="text-sm text-gray-700 leading-7">
                    {{ $order->address->recipient_name }} — {{ $order->address->phone }}<br>
                    {{ $order->address->province }}، {{ $order->address->city }}<br>
                    {{ $order->address->address }}
                    @if($order->address->postal_code)
                        <br>کد پستی: {{ $order->address->postal_code }}
                    @endif
                </p>
            @endif
            @if($order->shippingMethod)
                <p class="text-sm text-gray-500 mt-2">روش ارسال: {{ $order->shippingMethod->name }}</p>
            @endif
            @if($order->shipping_tracking_code)
                <p class="text-sm mt-2">کد رهگیری پست: <strong dir="ltr">{{ $order->shipping_tracking_code }}</strong></p>
            @endif
        </div>
    @endif

    @if($order->payments->isNotEmpty())
        <div class="shop-card p-5 mb-4">
            <h2 class="font-bold text-navy mb-3">پرداخت‌ها</h2>
            <div class="space-y-2">
                @foreach($order->payments as $payment)
                    <div class="flex justify-between text-sm py-2 border-b border-gray-50 last:border-0">
                        <span>{{ $payment->tracking_code }} — {{ ShopLabels::gateway($payment->gateway) }} — {{ ShopLabels::paymentStatus($payment->status) }}</span>
                        <span>{{ number_format($payment->amount) }} تومان</span>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-gray-400 mt-2">روش: {{ ShopLabels::paymentMethod($order->payment_method) }}</p>
        </div>
    @endif

    @if ($order->canPayAgain())
        <div class="shop-card p-5 mb-4">
            <h2 class="font-bold text-navy mb-2">ادامه پرداخت</h2>
            <p class="text-sm text-gray-600 mb-4">
                مانده این سفارش {{ number_format($order->remainingAmount()) }} تومان است.
                تا وقتی مجموع پرداخت اعتباری و نقدی با مبلغ سفارش برابر نشود، سفارش تکمیل نمی‌شود.
            </p>

            @if (count($creditGateways))
                <div class="checkout-pay-group">
                    <h3 class="checkout-pay-group__title">پرداخت اعتباری</h3>
                    @foreach ($creditGateways as $gateway)
                        <label wire:key="remain-pay-{{ $gateway['name'] }}" class="checkout-option {{ $selectedGateway === $gateway['name'] ? 'checkout-option-active' : '' }}">
                            <input type="radio" name="selectedGateway" wire:model.live="selectedGateway" value="{{ $gateway['name'] }}" class="checkout-option__radio">
                            <x-checkout-option-icon :src="$gateway['icon'] ?? null" :alt="$gateway['label']" />
                            <div class="checkout-option__body">
                                <p class="font-bold text-sm">{{ $gateway['label'] }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $gateway['description'] }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            @endif

            @if (count($cashGateways))
                <div class="checkout-pay-group">
                    <h3 class="checkout-pay-group__title">درگاه‌های نقدی</h3>
                    @foreach ($cashGateways as $gateway)
                        <label wire:key="remain-pay-{{ $gateway['name'] }}" class="checkout-option {{ $selectedGateway === $gateway['name'] ? 'checkout-option-active' : '' }}">
                            <input type="radio" name="selectedGateway" wire:model.live="selectedGateway" value="{{ $gateway['name'] }}" class="checkout-option__radio">
                            <x-checkout-option-icon :src="$gateway['icon'] ?? null" :alt="$gateway['label']" />
                            <div class="checkout-option__body">
                                <p class="font-bold text-sm">{{ $gateway['label'] }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $gateway['description'] }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            @endif

            @error('selectedGateway') <span class="text-red-600 text-xs mb-2 block">{{ $message }}</span> @enderror

            <button wire:click="payAgain" wire:loading.attr="disabled"
                class="bg-brand-green hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-bold">
                پرداخت مانده
            </button>
        </div>
    @endif

    @if($order->notes->isNotEmpty())
        <div class="shop-card p-5 mb-4">
            <h2 class="font-bold text-navy mb-3">پیام‌های فروشگاه</h2>
            <ul class="space-y-3">
                @foreach($order->notes as $note)
                    <li class="text-sm border-r-2 border-brand-green pr-3">
                        <time class="text-xs text-gray-400">{{ $note->created_at->format('Y/m/d H:i') }}</time>
                        <p class="mt-1 text-gray-700">{!! nl2br(e($note->message)) !!}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-wrap gap-3">
        @if ($order->canBeCanceled())
            <button wire:click="cancel" wire:confirm="آیا از لغو سفارش مطمئن هستید؟"
                class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold">
                لغو سفارش
            </button>
        @endif
        <a href="{{ route('account.orders') }}" class="inline-flex items-center text-sm text-brand-green font-bold px-2">بازگشت به سفارش‌ها</a>
    </div>
</x-account-layout>
