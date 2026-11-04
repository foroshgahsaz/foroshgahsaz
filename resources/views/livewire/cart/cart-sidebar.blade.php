<div class="flex flex-col h-full min-h-0">
    <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50 shrink-0">
        <div class="flex items-center gap-2 font-bold text-sm sm:text-base">
            <span>سبد خرید</span>
            @if ($summary['item_count'] > 0)
                <span class="bg-emerald-100 text-emerald-700 text-xs px-2 py-0.5 rounded-full">
                    {{ $summary['item_count'] }} کالا
                </span>
            @endif
        </div>
        <button type="button"
                data-close-cart
                class="cart-sidebar-close"
                aria-label="بستن سبد">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M18 6 6 18M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto p-4 scrollbar-hide min-h-0">
        @if ($items->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-center text-gray-500">
                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                    <path d="M3 6h18" />
                    <path d="M16 10a4 4 0 0 1-8 0" />
                </svg>
                <p class="text-sm font-medium">سبد خرید شما خالی است</p>
                <a href="{{ route('products.index') }}"
                   data-close-cart
                   class="mt-4 text-brand-green text-sm font-bold">مشاهده محصولات</a>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($items as $item)
                    @php
                        $variantId = $item['product_variant_id'] ?? null;
                        $variantArg = $variantId !== null ? $variantId : 'null';
                    @endphp
                    <div class="flex gap-3 border-b border-gray-100 pb-4 last:border-0"
                         wire:key="sidebar-cart-{{ $item['product_id'] }}-{{ $variantId ?? 0 }}">
                        <a href="{{ $item['url'] }}" data-close-cart class="shrink-0">
                            <img src="{{ $item['image'] }}"
                                 alt="{{ $item['product_name'] }}"
                                 class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl bg-gray-50 p-1 object-contain">
                        </a>
                        <div class="flex-1 min-w-0">
                            <a href="{{ $item['url'] }}"
                               data-close-cart
                               class="text-xs sm:text-sm font-bold line-clamp-2 hover:text-brand-green">
                                {{ $item['product_name'] }}
                            </a>
                            @if (! empty($item['sku']))
                                <p class="text-[11px] text-gray-400 mt-0.5">کد: {{ $item['sku'] }}</p>
                            @endif
                            <x-shop.price
                                :amount="$item['price']"
                                :compare="$item['compare_price'] ?? null"
                                class="mt-2 items-start text-right"
                            />
                            <div class="flex items-center justify-between mt-2 gap-2">
                                <div class="shop-qty-control">
                                    <button type="button"
                                            wire:click="decrementQuantity({{ $item['product_id'] }}, {{ $variantArg }})"
                                            wire:loading.attr="disabled"
                                            wire:target="decrementQuantity,incrementQuantity,updateQuantity"
                                            class="shop-qty-btn">−</button>
                                    <span class="shop-qty-value">{{ $item['quantity'] }}</span>
                                    <button type="button"
                                            wire:click="incrementQuantity({{ $item['product_id'] }}, {{ $variantArg }})"
                                            wire:loading.attr="disabled"
                                            wire:target="decrementQuantity,incrementQuantity,updateQuantity"
                                            class="shop-qty-btn">+</button>
                                </div>
                                <button type="button"
                                        wire:click="remove({{ $item['product_id'] }}, {{ $variantArg }})"
                                        class="text-red-500 text-xs hover:text-red-700 shrink-0">
                                    حذف
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($items->isNotEmpty())
        <div class="p-4 border-t bg-gray-50 shrink-0 space-y-3">
            <div class="space-y-2 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>جمع کالاها</span>
                    <span>{{ shop_money($summary['subtotal']) }}</span>
                </div>
                @if ($summary['discount'] > 0)
                    <div class="flex justify-between text-emerald-600">
                        <span>تخفیف</span>
                        <span>− {{ shop_money($summary['discount']) }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-gray-600">
                    <span>
                        هزینه ارسال
                        @if ($summary['shipping_method'])
                            <span class="text-[11px] text-gray-400">({{ $summary['shipping_method'] }})</span>
                        @endif
                    </span>
                    <span>
                        @if ($summary['shipping'] === 0)
                            <span class="text-emerald-600 font-medium">رایگان</span>
                        @else
                            {{ shop_money($summary['shipping']) }}
                        @endif
                    </span>
                </div>
                <div class="flex justify-between font-black text-base pt-2 border-t border-gray-200">
                    <span>مبلغ قابل پرداخت</span>
                    <span class="text-brand-green">{{ shop_money($summary['total']) }}</span>
                </div>
            </div>
            <a href="{{ route('checkout') }}"
               class="block w-full bg-brand-gold text-white py-3 rounded-xl text-sm font-bold text-center hover:opacity-90 transition-opacity">
                تکمیل سفارش
            </a>
            <a href="{{ route('cart') }}"
               data-close-cart
               class="block w-full text-center text-xs text-gray-500 hover:text-brand-green py-1">
                مشاهده سبد کامل
            </a>
        </div>
    @endif
</div>
