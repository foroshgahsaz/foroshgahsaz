<div class="shop-page-wrap shop-page-wrap--checkout @if($preview) shop-page-wrap--has-mobile-bar @endif">
    <div class="max-w-site mx-auto px-4 py-6 md:py-10">
        <nav class="flex items-center gap-2 text-xs text-gray-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-brand-green">خانه</a>
            <span>/</span>
            <a href="{{ route('cart') }}" class="hover:text-brand-green">سبد خرید</a>
            <span>/</span>
            <span class="text-gray-600">تسویه حساب</span>
        </nav>

        <h1 class="text-xl md:text-2xl font-black text-navy mb-6">تسویه حساب</h1>

        @if ($error)
            <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-xl text-sm">{{ $error }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-xl text-sm">{{ session('error') }}</div>
        @endif

        @if ($preview)
            <form wire:submit="placeOrder" id="checkoutForm" class="checkout-layout">
                <div class="checkout-row">
                    <section class="shop-card checkout-card">
                        <div class="checkout-card__head">
                            <h2 class="shop-section-title">آدرس تحویل</h2>
                            @if ($addresses->isNotEmpty())
                                <button type="button" class="checkout-card__action" wire:click="toggleAddressForm">
                                    {{ $showAddressForm ? 'بستن فرم' : 'افزودن آدرس' }}
                                </button>
                            @endif
                        </div>

                        @if ($showAddressForm)
                            <div class="checkout-address-form">
                                <div class="checkout-address-form__row">
                                    <div>
                                        <label class="shop-label">نام گیرنده</label>
                                        <input type="text" wire:model="receiver_name" class="shop-input" placeholder="نام و نام خانوادگی">
                                        @error('receiver_name') <span class="checkout-field-error">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="shop-label">موبایل گیرنده</label>
                                        <input type="text" wire:model="receiver_phone" class="shop-input" placeholder="0912..." dir="ltr">
                                        @error('receiver_phone') <span class="checkout-field-error">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="checkout-address-form__row">
                                    <div>
                                        <label class="shop-label">استان</label>
                                        <input type="text" wire:model="province" class="shop-input" placeholder="استان">
                                        @error('province') <span class="checkout-field-error">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="shop-label">شهر</label>
                                        <input type="text" wire:model="city" class="shop-input" placeholder="شهر">
                                        @error('city') <span class="checkout-field-error">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div>
                                    <label class="shop-label">آدرس کامل</label>
                                    <textarea wire:model="address" class="shop-input" rows="2" placeholder="خیابان، پلاک، واحد"></textarea>
                                    @error('address') <span class="checkout-field-error">{{ $message }}</span> @enderror
                                </div>
                                <div class="checkout-address-form__row">
                                    <div>
                                        <label class="shop-label">کد پستی</label>
                                        <input type="text" wire:model="postal_code" class="shop-input" placeholder="۱۰ رقم" dir="ltr">
                                        @error('postal_code') <span class="checkout-field-error">{{ $message }}</span> @enderror
                                    </div>
                                    <label class="checkout-address-form__default">
                                        <input type="checkbox" wire:model="is_default">
                                        <span>آدرس پیش‌فرض</span>
                                    </label>
                                </div>
                                <div class="checkout-address-form__actions">
                                    <button type="button" wire:click="saveAddress" class="shop-btn-primary checkout-address-form__save">
                                        ذخیره آدرس
                                    </button>
                                    @if ($addresses->isNotEmpty())
                                        <button type="button" wire:click="toggleAddressForm" class="shop-btn-outline">انصراف</button>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @forelse ($addresses as $item)
                            <label class="checkout-option {{ $addressId == $item->id ? 'checkout-option-active' : '' }}">
                                <input type="radio" name="addressId" wire:model="addressId" value="{{ $item->id }}" class="checkout-option__radio">
                                <div class="checkout-option__body">
                                    <p class="font-bold text-sm text-navy">{{ $item->receiver_name }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $item->province }}، {{ $item->city }}، {{ $item->address }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $item->receiver_phone }}@if ($item->postal_code) — کد پستی: {{ $item->postal_code }}@endif</p>
                                    @if ($item->is_default)
                                        <span class="checkout-option__badge">پیش‌فرض</span>
                                    @endif
                                </div>
                            </label>
                        @empty
                            @unless ($showAddressForm)
                                <p class="text-sm text-gray-600">آدرسی ثبت نشده. از فرم بالا آدرس را اضافه کنید.</p>
                            @endunless
                        @endforelse
                        @error('addressId') <span class="checkout-field-error">{{ $message }}</span> @enderror
                    </section>

                    <section class="shop-card checkout-card">
                        <h2 class="shop-section-title">نحوه ارسال</h2>
                        @forelse ($shippingMethods as $method)
                            <label class="checkout-option {{ $shippingMethodId == $method->id ? 'checkout-option-active' : '' }}">
                                <input type="radio" name="shippingMethodId" wire:model.live="shippingMethodId" value="{{ $method->id }}" class="checkout-option__radio">
                                <x-checkout-option-icon :src="$method->iconUrl()" :alt="$method->name" />
                                <div class="checkout-option__body checkout-option__body--split">
                                    <div>
                                        <p class="font-medium text-sm">{{ $method->name }}</p>
                                        @if ($method->description)
                                            <p class="text-xs text-gray-400 mt-0.5">{{ $method->description }}</p>
                                        @endif
                                        @if ($method->estimated_days)
                                            <p class="text-xs text-gray-400 mt-0.5">حدود {{ $method->estimated_days }} روز</p>
                                        @endif
                                    </div>
                                    <span class="checkout-option__price">{{ $method->price === 0 ? 'رایگان' : number_format($method->price).' تومان' }}</span>
                                </div>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">روش ارسالی تعریف نشده است.</p>
                        @endforelse
                        @error('shippingMethodId') <span class="checkout-field-error">{{ $message }}</span> @enderror
                    </section>
                </div>

                <div class="checkout-row checkout-row--summary">
                    <div class="checkout-pay-stack">
                        <section class="shop-card checkout-card">
                            <h2 class="shop-section-title">کد تخفیف</h2>
                            <label class="shop-label">کد تخفیف</label>
                            <input type="text" wire:model.live="couponCode" placeholder="کد تخفیف را وارد کنید" class="shop-input">
                            <div class="checkout-note">
                                <label class="shop-label">یادداشت (اختیاری)</label>
                                <textarea wire:model="note" class="shop-input" rows="2" placeholder="توضیحات سفارش..."></textarea>
                            </div>
                        </section>

                        <section class="shop-card checkout-card">
                            <h2 class="shop-section-title">انتخاب درگاه</h2>
                            <p class="checkout-pay-hint">پرداخت اعتباری و نقدی جدا هستند. می‌توانید الان یکی را بزنید و مانده را بعداً از صفحه سفارش پرداخت کنید.</p>

                            @if (count($creditGateways))
                                <div class="checkout-pay-group">
                                    <h3 class="checkout-pay-group__title">پرداخت اعتباری</h3>
                                    @foreach ($creditGateways as $gateway)
                                        <label wire:key="pay-{{ $gateway['name'] }}" class="checkout-option {{ $paymentMethod === $gateway['name'] ? 'checkout-option-active' : '' }}">
                                            <input type="radio" name="paymentMethod" wire:model.live="paymentMethod" value="{{ $gateway['name'] }}" class="checkout-option__radio">
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
                                        <label wire:key="pay-{{ $gateway['name'] }}" class="checkout-option {{ $paymentMethod === $gateway['name'] ? 'checkout-option-active' : '' }}">
                                            <input type="radio" name="paymentMethod" wire:model.live="paymentMethod" value="{{ $gateway['name'] }}" class="checkout-option__radio">
                                            <x-checkout-option-icon :src="$gateway['icon'] ?? null" :alt="$gateway['label']" />
                                            <div class="checkout-option__body">
                                                <p class="font-bold text-sm">{{ $gateway['label'] }}</p>
                                                <p class="text-xs text-gray-400 mt-0.5">{{ $gateway['description'] }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @endif

                            <div class="checkout-pay-group">
                                <h3 class="checkout-pay-group__title">سایر روش‌ها</h3>
                                <label class="checkout-option {{ $paymentMethod === 'cod' ? 'checkout-option-active' : '' }}">
                                    <input type="radio" name="paymentMethod" wire:model.live="paymentMethod" value="cod" class="checkout-option__radio">
                                    <span class="checkout-option__icon checkout-option__icon--cod" aria-hidden="true"></span>
                                    <div class="checkout-option__body">
                                        <p class="font-bold text-sm">پرداخت در محل</p>
                                        <p class="text-xs text-gray-400 mt-0.5">هنگام تحویل</p>
                                    </div>
                                </label>
                            </div>
                            @error('paymentMethod') <span class="checkout-field-error">{{ $message }}</span> @enderror
                        </section>
                    </div>

                    <aside class="checkout-summary-col">
                        <div class="shop-card checkout-card checkout-summary">
                            <h2 class="font-bold text-navy border-b border-gray-100 pb-3">خلاصه سفارش</h2>
                            <div class="checkout-summary__lines">
                                <div class="checkout-summary__line">
                                    <span>جمع کالا</span>
                                    <span>{{ number_format($preview['subtotal']) }} تومان</span>
                                </div>
                                @if ($preview['discount'] > 0)
                                    <div class="checkout-summary__line checkout-summary__line--discount">
                                        <span>تخفیف</span>
                                        <span>− {{ number_format($preview['discount']) }} تومان</span>
                                    </div>
                                @endif
                                <div class="checkout-summary__line">
                                    <span>ارسال</span>
                                    <span>{{ $preview['shipping'] === 0 ? 'رایگان' : number_format($preview['shipping']).' تومان' }}</span>
                                </div>
                            </div>
                            <div class="checkout-summary__total">
                                <span class="font-bold text-navy">مبلغ نهایی</span>
                                <span class="text-lg font-black text-brand-green">{{ number_format($preview['total']) }} تومان</span>
                            </div>
                            <button type="submit" wire:loading.attr="disabled" class="shop-btn-gold checkout-summary__submit">
                                <span wire:loading.remove wire:target="placeOrder">ثبت و پرداخت سفارش</span>
                                <span wire:loading wire:target="placeOrder">در حال پردازش...</span>
                            </button>
                            <a href="{{ route('cart') }}" class="checkout-summary__back">بازگشت به سبد</a>
                        </div>
                    </aside>
                </div>

                <div class="shop-mobile-bar checkout-mobile-bar" aria-label="ثبت سفارش">
                    <div class="shop-mobile-bar__info">
                        <span class="shop-mobile-bar__label">مبلغ نهایی</span>
                        <span class="shop-mobile-bar__price">{{ number_format($preview['total']) }} تومان</span>
                    </div>
                    <button type="submit" form="checkoutForm" wire:loading.attr="disabled" class="shop-mobile-bar__btn">
                        <span wire:loading.remove wire:target="placeOrder">ثبت سفارش</span>
                        <span wire:loading wire:target="placeOrder">...</span>
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
