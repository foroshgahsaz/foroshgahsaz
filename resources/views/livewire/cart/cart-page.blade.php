@php
    $itemCount = (int) ($summary['item_count'] ?? $items->count());
@endphp

<div class="shop-page-wrap @if(!$items->isEmpty()) shop-page-wrap--has-mobile-bar @endif">
    <div class="max-w-site mx-auto px-4 py-6 md:py-10">
        <nav class="flex items-center gap-2 text-xs text-gray-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-brand-green">خانه</a>
            <span>/</span>
            <span class="text-gray-600">سبد خرید</span>
        </nav>

        @if (session('success'))
            <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-xl text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-xl text-sm">{{ session('error') }}</div>
        @endif

        @if ($items->isEmpty())
            <div class="cart-invoice cart-invoice--empty">
                <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                    <path d="M3 6h18" /><path d="M16 10a4 4 0 0 1-8 0" />
                </svg>
                <p class="text-gray-600 mb-2 font-medium">سبد خرید شما خالی است</p>
                <p class="text-sm text-gray-400 mb-6">محصولات مورد علاقه‌تان را به سبد اضافه کنید</p>
                <a href="{{ route('products.index') }}" class="shop-btn-primary inline-flex">مشاهده محصولات</a>
            </div>
        @else
            <div class="cart-invoice">
                <div class="cart-invoice__head" style="border-bottom: 0; margin-bottom: 0.5rem;">
                    <a href="{{ route('products.index') }}" class="cart-invoice__continue">ادامه خرید</a>
                </div>

                <div class="si-document--cart-wrap">
                    @include('components.sales-invoice.document', ['document' => $invoice, 'context' => 'web'])
                </div>

                <div class="cart-invoice__cart-actions">
                    @foreach ($items as $item)
                        @php $variantArg = ($item['product_variant_id'] ?? null) !== null ? $item['product_variant_id'] : 'null'; @endphp
                        <div class="cart-invoice__cart-row" wire:key="cart-actions-{{ $item['product_id'] }}-{{ $item['product_variant_id'] ?? 0 }}">
                            <span class="cart-invoice__cart-row-title">{{ $item['product_name'] }}</span>
                            <div class="shop-qty-control">
                                <button type="button"
                                        wire:click="decrementQuantity({{ $item['product_id'] }}, {{ $variantArg }})"
                                        class="shop-qty-btn">−</button>
                                <span class="shop-qty-value">{{ $item['quantity'] }}</span>
                                <button type="button"
                                        wire:click="incrementQuantity({{ $item['product_id'] }}, {{ $variantArg }})"
                                        class="shop-qty-btn">+</button>
                            </div>
                            <button type="button"
                                    wire:click="remove({{ $item['product_id'] }}, {{ $variantArg }})"
                                    class="cart-invoice__remove">
                                حذف
                            </button>
                        </div>
                    @endforeach
                </div>

                <div class="cart-invoice__totals cart-invoice__totals--compact">
                    <a href="{{ route('checkout') }}" class="shop-btn-gold cart-invoice__checkout">
                        @if(shop_prices_hidden())
                            ادامه و تسویه حساب
                        @else
                            ادامه و تسویه حساب ({{ shop_money($summary['total']) }})
                        @endif
                    </a>
                </div>
            </div>

            <div class="shop-mobile-bar lg:hidden" aria-label="خلاصه سبد خرید">
                <div class="shop-mobile-bar__info">
                    <span class="shop-mobile-bar__label">مبلغ قابل پرداخت</span>
                    <span class="shop-mobile-bar__price">{{ shop_money($summary['total']) }}</span>
                </div>
                <a href="{{ route('checkout') }}" class="shop-mobile-bar__btn">تسویه حساب</a>
            </div>
        @endif
    </div>
</div>
