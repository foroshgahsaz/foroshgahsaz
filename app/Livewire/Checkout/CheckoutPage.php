<?php

namespace App\Livewire\Checkout;

use App\Livewire\Concerns\PromptsLoginModal;
use App\Support\UserDisplayName;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use App\Rules\IranianPostalCode;
use App\Services\Auth\OtpService;
use App\Services\Cache\ShopCacheService;
use App\Services\Checkout\CheckoutService;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Payment\PaymentService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.shop')]
#[Title('تسویه حساب')]
class CheckoutPage extends Component
{
    use PromptsLoginModal;

    public bool $awaitingLogin = false;

    public ?int $addressId = null;

    public ?int $shippingMethodId = null;

    public string $couponCode = '';

    public string $paymentMethod = 'zarinpal';

    public ?string $note = null;

    public bool $showAddressForm = false;

    public string $receiver_name = '';

    public string $receiver_phone = '';

    public ?int $province_id = null;

    public ?int $city_id = null;

    public string $province = '';

    public string $city = '';

    public string $address = '';

    public string $postal_code = '';

    public bool $is_default = false;

    public function mount(PaymentGatewayCatalog $catalog, ShopCacheService $cache): void
    {
        if (! auth()->check()) {
            $this->awaitingLogin = true;
            $this->promptLoginModal(route('checkout'));

            return;
        }

        $this->bootstrapAuthenticatedCheckout($catalog, $cache);
    }

    #[On('shop-user-logged-in')]
    public function onShopUserLoggedIn(PaymentGatewayCatalog $catalog, ShopCacheService $cache): void
    {
        if (! auth()->check()) {
            return;
        }

        $this->bootstrapAuthenticatedCheckout($catalog, $cache);
    }

    #[On('shop-user-profile-updated')]
    public function refreshReceiverFromProfile(PaymentGatewayCatalog $catalog, ShopCacheService $cache): void
    {
        if (! auth()->check()) {
            return;
        }

        auth()->setUser(auth()->user()->fresh());
        $this->bootstrapAuthenticatedCheckout($catalog, $cache, refreshReceiverName: true);
    }

    protected function bootstrapAuthenticatedCheckout(
        PaymentGatewayCatalog $catalog,
        ShopCacheService $cache,
        bool $refreshReceiverName = false,
    ): void {
        $this->awaitingLogin = false;
        $this->paymentMethod = $this->defaultPaymentMethod($catalog);

        $addresses = auth()->user()->addresses;
        $default = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        if ($default) {
            $this->addressId = $default->id;
        } else {
            $this->showAddressForm = true;
            if ($refreshReceiverName) {
                $this->receiver_name = '';
            }
            $this->prefillAddressFromProfile();
        }

        $shipping = $cache->shippingMethods()->sortBy('price')->first();
        if ($shipping) {
            $this->shippingMethodId = $shipping->id;
        }
    }

    public function toggleAddressForm(): void
    {
        $this->showAddressForm = ! $this->showAddressForm;

        if ($this->showAddressForm) {
            $this->prefillAddressFromProfile();

            return;
        }

        $this->resetAddressFields();
    }

    public function updatedProvinceId(): void
    {
        $this->city_id = null;
        $this->province = '';
        $this->city = '';
    }

    public function syncCouponAlertOnLoad(): void
    {
        $this->alertCouponValidationIfNeeded();
    }

    public function updatedCouponCode(): void
    {
        $this->alertCouponValidationIfNeeded();
    }

    public function updatedPaymentMethod(): void
    {
        $this->alertCouponValidationIfNeeded();
    }

    public function saveAddress(OtpService $otp): void
    {
        $this->receiver_name = trim($this->receiver_name);
        $this->receiver_phone = $otp->normalizePhone($this->receiver_phone);

        $validated = $this->validate($this->addressRules());

        $user = auth()->user();

        if (UserDisplayName::isPlaceholder($user->name) && trim($this->receiver_name) !== '') {
            $this->persistUserDisplayName(trim($this->receiver_name));
        }

        $province = Province::query()->findOrFail($this->province_id);
        $city = City::query()
            ->where('province_id', $province->id)
            ->findOrFail($this->city_id);

        $validated['province'] = $province->name;
        $validated['city'] = $city->name;
        $validated['postal_code'] = IranianPostalCode::normalize($validated['postal_code']);
        unset($validated['province_id'], $validated['city_id']);

        $isFirst = $user->addresses()->doesntExist();

        if ($isFirst || $this->is_default) {
            $user->addresses()->update(['is_default' => false]);
            $validated['is_default'] = true;
        }

        $address = $user->addresses()->create($validated);

        $this->addressId = $address->id;
        $this->showAddressForm = false;
        $this->resetAddressFields();
    }

    public function placeOrder(CheckoutService $checkout, PaymentService $payment, PaymentGatewayCatalog $catalog): void
    {
        $this->validate($this->checkoutRules($catalog));

        $isCod = $this->paymentMethod === 'cod';

        try {
            $order = $checkout->placeOrder(
                auth()->user(),
                $this->addressId,
                $this->shippingMethodId,
                $this->couponCode ?: null,
                $isCod ? 'cod' : $this->paymentMethod,
                $this->note
            );
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        if (! $isCod) {
            try {
                $paymentModel = $payment->createForOrder($order, $this->paymentMethod);
                $url = $payment->initiate($paymentModel, $order);
                $this->redirect($url, navigate: false);
            } catch (\Throwable $e) {
                session()->flash('error', $e instanceof \RuntimeException
                    ? $e->getMessage()
                    : 'خطا در اتصال به درگاه پرداخت. لطفاً دوباره تلاش کنید.');
                $this->redirect(route('account.orders.show', $order), navigate: true);
            }

            return;
        }

        $this->redirect(route('account.orders.show', $order), navigate: true);
    }

    public function render(CheckoutService $checkout, ShopCacheService $cache, PaymentGatewayCatalog $catalog)
    {
        if (! auth()->check()) {
            return view('livewire.checkout.checkout-page', [
                'preview' => null,
                'fatalError' => null,
                'couponError' => null,
                'addresses' => collect(),
                'shippingMethods' => $cache->shippingMethods(),
                'creditGateways' => $catalog->credit(),
                'cashGateways' => $catalog->cash(),
                'codPayment' => $catalog->codEnabled() ? $catalog->codDefinition() : null,
                'awaitingLogin' => true,
                'provinces' => collect(),
                'cities' => collect(),
                'checkoutUnlocked' => false,
            ]);
        }

        $preview = null;
        $fatalError = null;

        try {
            $preview = $checkout->preview(
                auth()->user(),
                $this->couponCode ?: null,
                $this->shippingMethodId,
                $this->couponPaymentMethod(),
            );
        } catch (\RuntimeException $e) {
            $fatalError = $e->getMessage();
        }

        return view('livewire.checkout.checkout-page', [
            'preview' => $preview,
            'fatalError' => $fatalError,
            'couponError' => $preview['couponError'] ?? null,
            'addresses' => auth()->user()->addresses()->latest()->get(),
            'shippingMethods' => $cache->shippingMethods(),
            'creditGateways' => $catalog->credit(),
            'cashGateways' => $catalog->cash(),
            'codPayment' => $catalog->codEnabled() ? $catalog->codDefinition() : null,
            'awaitingLogin' => false,
            'provinces' => Province::query()->orderBy('position')->get(['id', 'name']),
            'cities' => $this->checkoutCities(),
            'checkoutUnlocked' => $this->addressId !== null,
        ]);
    }

    /** @return array<string, mixed> */
    protected function checkoutRules(PaymentGatewayCatalog $catalog): array
    {
        $allowed = $catalog->enabledNames();
        if ($catalog->codEnabled()) {
            $allowed[] = 'cod';
        }

        $rules = [
            'addressId' => [
                'required',
                'integer',
                Rule::exists('user_addresses', 'id')->where('user_id', auth()->id()),
            ],
            'paymentMethod' => ['required', Rule::in($allowed)],
        ];

        if (app(CheckoutService::class)->hasActiveShippingMethods()) {
            $rules['shippingMethodId'] = [
                'required',
                'integer',
                Rule::exists('shipping_methods', 'id')->where('is_active', true),
            ];
        } else {
            $rules['shippingMethodId'] = ['nullable', 'integer'];
        }

        return $rules;
    }

    /** @return array<string, mixed> */
    protected function addressRules(): array
    {
        return [
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'province_id' => ['required', 'integer', Rule::exists('provinces', 'id')],
            'city_id' => [
                'required',
                'integer',
                Rule::exists('cities', 'id')->where('province_id', $this->province_id),
            ],
            'address' => ['required', 'string'],
            'postal_code' => ['required', 'string', new IranianPostalCode],
            'is_default' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'addressId.required' => 'آدرس را انتخاب کنید.',
            'addressId.exists' => 'آدرس انتخاب‌شده معتبر نیست.',
            'shippingMethodId.required' => 'روش ارسال را انتخاب کنید.',
            'shippingMethodId.exists' => 'روش ارسال معتبر نیست.',
            'paymentMethod.required' => 'روش پرداخت را انتخاب کنید.',
            'paymentMethod.in' => 'روش پرداخت معتبر نیست.',
            'receiver_name.required' => 'نام گیرنده را وارد کنید.',
            'receiver_phone.required' => 'موبایل گیرنده را وارد کنید.',
            'receiver_phone.regex' => 'موبایل گیرنده باید با 09 شروع شود و ۱۱ رقم باشد.',
            'province_id.required' => 'استان را انتخاب کنید.',
            'province_id.exists' => 'استان انتخاب‌شده معتبر نیست.',
            'city_id.required' => 'شهر را انتخاب کنید.',
            'city_id.exists' => 'شهر انتخاب‌شده معتبر نیست.',
            'address.required' => 'آدرس کامل را وارد کنید.',
            'postal_code.required' => 'کد پستی را وارد کنید.',
        ];
    }

    protected function defaultPaymentMethod(PaymentGatewayCatalog $catalog): string
    {
        $cash = $catalog->cash();
        if ($cash !== []) {
            return $cash[0]['name'];
        }

        $credit = $catalog->credit();
        if ($credit !== []) {
            return $credit[0]['name'];
        }

        if ($catalog->codEnabled()) {
            return 'cod';
        }

        $names = $catalog->enabledNames();

        return $names[0] ?? 'zarinpal';
    }

    protected function prefillAddressFromProfile(): void
    {
        $user = auth()->user()->fresh();

        if ($this->receiver_name === '') {
            $name = trim((string) ($user->name ?? ''));
            if ($name !== '' && ! $this->isPlaceholderUserName($name)) {
                $this->receiver_name = $name;
            }
        }

        if ($this->receiver_phone === '') {
            $this->receiver_phone = (string) ($user->phone ?? '');
        }
    }

    protected function resetAddressFields(): void
    {
        $this->reset([
            'receiver_name',
            'receiver_phone',
            'province_id',
            'city_id',
            'province',
            'city',
            'address',
            'postal_code',
            'is_default',
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, City> */
    protected function checkoutCities(): \Illuminate\Support\Collection
    {
        if ($this->province_id === null) {
            return collect();
        }

        return City::query()
            ->where('province_id', $this->province_id)
            ->orderBy('position')
            ->get(['id', 'name']);
    }

    protected function persistUserDisplayName(string $fullName): void
    {
        $fullName = trim($fullName);

        if ($fullName === '' || ! auth()->check()) {
            return;
        }

        $user = auth()->user();

        if (! UserDisplayName::isPlaceholder($user->name)) {
            return;
        }

        $user->forceFill(['name' => $fullName])->save();
        auth()->setUser($user->fresh());
        $this->dispatch('shop-user-profile-updated');
    }

    protected function isPlaceholderUserName(string $name): bool
    {
        return UserDisplayName::isPlaceholder($name);
    }

    protected function couponPaymentMethod(): string
    {
        return $this->paymentMethod === 'cod' ? 'cod' : $this->paymentMethod;
    }

    protected function alertCouponValidationIfNeeded(): void
    {
        if (trim($this->couponCode) === '') {
            return;
        }

        if (! auth()->check()) {
            return;
        }

        try {
            $preview = app(CheckoutService::class)->preview(
                auth()->user(),
                $this->couponCode,
                $this->shippingMethodId,
                $this->couponPaymentMethod(),
            );
        } catch (\RuntimeException) {
            return;
        }

        if (! empty($preview['couponError'])) {
            $this->dispatch('checkout-coupon-alert', message: $preview['couponError']);
        }
    }
}
