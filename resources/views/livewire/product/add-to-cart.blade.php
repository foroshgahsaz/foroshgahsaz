<div>

    @if (session('success'))

        <div class="mb-2 p-2 bg-emerald-50 text-emerald-700 rounded text-sm">{{ session('success') }}</div>

    @endif

    @if (session('error'))

        <div class="mb-2 p-2 bg-red-50 text-red-700 rounded text-sm">{{ session('error') }}</div>

    @endif



    @if ($selectedLabel)

        <div class="mb-3 p-3 bg-gray-50 rounded-xl border border-gray-100">

            <p class="text-[11px] text-gray-400 mb-1">انتخاب شما</p>

            <p class="text-sm font-bold text-gray-800 leading-relaxed">{{ $selectedLabel }}</p>

        </div>

    @endif



    @if ($selectedPrice !== null)

        <div class="py-3 border-t border-b border-gray-100 mb-4">
            <x-shop.price
                :amount="$selectedPrice"
                :compare="$comparePrice"
                class="items-start text-right product-buy-box__price-wrap"
            />
        </div>

    @endif



    @if ($showDigipayInstallmentPromo ?? false)

        <div class="product-digipay-promo py-3 border-b border-gray-100 mb-3">

            <div class="product-digipay-promo__inner">

                @if ($digipayIconUrl ?? null)

                    <img src="{{ $digipayIconUrl }}" alt="" width="48" height="48" class="product-digipay-promo__icon" loading="lazy" decoding="async">

                @endif

                <p class="product-digipay-promo__text">{{ $digipayInstallmentPromoText }}</p>

            </div>

        </div>

    @endif



    @if ($selectedStock !== null && ! ($shopPricesHidden ?? false))

        <p class="text-xs text-gray-500 mb-3">

            موجودی:

            <strong class="{{ $selectedStock > 0 ? 'text-emerald-600' : 'text-red-600' }}">

                {{ $selectedStock > 0 ? 'موجود' : 'ناموجود' }}

            </strong>

        </p>

    @endif



    @php
        $purchaseBlocked = ($shopPricesHidden ?? false) || $selectedStock === 0;
    @endphp

    <div class="flex items-center gap-3 flex-wrap">

        @if (! ($shopPricesHidden ?? false))
            <div class="product-qty">

                <button type="button" wire:click="decrementQuantity" aria-label="کم کردن">−</button>

                <span class="product-qty__value" aria-live="polite">{{ $quantity }}</span>

                <button type="button" wire:click="incrementQuantity" aria-label="زیاد کردن">+</button>

            </div>
        @endif

        @if ($shopPricesHidden ?? false)
            @if(filled($sitePhone ?? ''))
                <a
                    href="tel:{{ preg_replace('/[^\d+]/', '', $sitePhone) }}"
                    class="product-add-cart flex-1 min-w-[200px] text-center product-add-cart--contact"
                >
                    تماس بگیرید
                </a>
            @else
                <button
                    type="button"
                    disabled
                    class="product-add-cart flex-1 min-w-[200px] opacity-60 cursor-not-allowed product-add-cart--contact"
                >
                    تماس بگیرید
                </button>
            @endif
        @else
            <button type="button"

                    wire:click="add"

                    wire:loading.attr="disabled"

                    wire:target="add"

                    @disabled($selectedStock === 0)

                    class="product-add-cart flex-1 min-w-[200px] @if($selectedStock === 0) opacity-50 cursor-not-allowed @endif">

                <span wire:loading.remove wire:target="add">افزودن به سبد خرید</span>

                <span wire:loading wire:target="add">در حال افزودن...</span>

            </button>
        @endif

    </div>

</div>

