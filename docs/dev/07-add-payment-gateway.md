# آموزش: افزودن درگاه پرداخت جدید

مرجع کوتاه در [DEVELOPER_GUIDE §9.12](../DEVELOPER_GUIDE.md#912-افزودن-درگاه-جدید-recipe). این فایل **گام‌به‌گام با نام فایل** است.

## قبل از شروع

- الگوهای موجود: `ZarinpalGateway` (نقدی REST)، `ParsianGateway` (نقدی SOAP / PEC)، `SadadGateway` (نقدی JSON / سداد)، `TaraGateway` (اعتباری + redirect میانی).
- مستندات درگاه سداد در repo: `docs/PHP-master.zip`، `docs/15470-51A234D3-CD9B-4DEB-8D6A-F9D72518FCB9.pdf`؛ مسیر مستعار `/payment/callback/bmi` همان سداد است اگر پنل بانک فقط این URL را بپذیرد.
- درگاه **نوپی (CPG)**: `docs/CPG Ver 2-5.pdf` — `NopayGateway`، `RequestToken` + `VerifyTransaction`.
- قرارداد: `app/Services/Payment/PaymentGatewayInterface.php`
  - `initiate(Payment, Order): string` — URL یا مسیر redirect
  - `verify(Payment, authority, status): GatewayVerificationResult`

---

## گام ۱ — ثبت در config

**فایل:** `config/payment.php`

```php
'mygateway' => [
    'driver' => MyGateway::class,
    'type' => 'cash', // یا credit — برای فیلتر checkout
    'label' => 'نام فارسی',
    'description' => '...',
    // کلیدهای env پیش‌فرض...
],
```

**فایل:** `.env.example` — متغیرهای env با پیشوند مناسب.

---

## گام ۲ — کلاس درگاه

**فایل:** `app/Services/Payment/MyGateway.php`

- `implements PaymentGatewayInterface`
- تنظیمات را از `SettingsService` بخوانید (مثل `zarinpal()` / `tara()`).
- مبالغ: دیتابیس **تومان**؛ برای API ریال از `AmountConverter`.

`PaymentGatewayManager` از `config('payment.gateways.{name}.driver')` نمونه می‌سازد — تغییر Manager لازم نیست اگر driver در config باشد.

---

## گام ۳ — فعال بودن در UI (حیاتی)

**فایل:** `app/Services/Payment/PaymentGatewayCatalog.php`

بدون این، درگاه در checkout **هرگز enabled نمی‌شود** (`default => false` در `isEnabled`):

1. در `isEnabled(string $name)` یک `case 'mygateway':` اضافه کنید.
2. در `iconUrl()` در صورت آپلود از settings، `match` را گسترش دهید.

`all()` و `enabled()` از config به‌صورت خودکار کلیدهای `gateways` را می‌خوانند.

---

## گام ۴ — تنظیمات ادمین (Settings DB)

**فایل:** `app/Services/Settings/SettingsService.php`

- متد `mygateway(): array` با `enabled`, `merchant_*`, `callback_url`, `icon`, ...
- از `$this->get('mygateway', 'key', default)` استفاده کنید.
- در انتهای آرایه: `...$this->gatewayCheckoutDisplayFields('mygateway')` برای نام/توضیح checkout.

**صفحه Filament (الگو):**

- کپی از `app/Filament/Pages/ManageZarinpal.php` → `ManageMyGateway.php`
- در فرم: `GatewayCheckoutDisplayFields::section()` + `mountState` / `saveState` (مثل زرین‌پال)
- ثبت در `app/Providers/Filament/AdminPanelProvider.php` → `->pages([...])`
- لینک در `resources/views/filament/partials/admin-sidebar.blade.php` (پنل «درگاه‌ها»)
- فقط **مدیر کامل** — مدیر فروش به درگاه‌ها دسترسی ندارد.

---

## گام ۵ — Callback و Route

**فایل:** `routes/web.php` — route GET/POST callback (با `?payment={tracking_code}` مثل موجود).

**فایل:** `app/Http/Controllers/PaymentController.php`

- متد public برای callback یا استفاده از `handleCallback` مشترک.
- `PaymentService::verify($payment, $authority, $status)` را صدا بزنید.

**فایل:** `bootstrap/app.php`

```php
$middleware->validateCsrfTokens(except: [
    'payment/callback',
    'payment/callback/tara',
    'payment/callback/mygateway', // اضافه
]);
```

اگر redirect میانی لازم است (مثل تارا): view در `resources/views/payments/` + متد در Controller.

---

## گام ۶ — مدل Payment

فیلد `payments.gateway` روی نام config (`mygateway`) ذخیره می‌شود — `PaymentService::createForOrder` و `initiate` از `payment_method` سفارش استفاده می‌کنند. مقدار enum/validation در checkout را بررسی کنید:

- `app/Livewire/Checkout/CheckoutPage.php` → `checkoutRules()` — `Rule::in($catalog->enabledNames())`

---

## گام ۷ — جاهایی که درگاه لیست می‌شود

| محل | فایل |
|-----|------|
| Checkout فروشگاه | `CheckoutPage` + `PaymentGatewayCatalog` |
| پرداخت مجدد سفارش | `Livewire/Account/OrderShow.php` |
| ویزارد نماینده | `OrderWizard.php` — `enabled()` |
| پیش‌فاکتور / ادمین سفارش | `ViewOrder.php`, `RepresentativeProformaPaymentService` |

معمولاً بعد از `isEnabled` در Catalog کافی است؛ اگر validation سخت‌گیرانه دارید، همان فایل‌ها را grep کنید: `enabledNames`, `payment_method`.

---

## گام ۸ — تست

- `tests/Feature/` — الگو: `CheckoutAndPaymentLaunchTest.php`, `TaraSplitPaymentTest.php`
- Sandbox درگاه + `SMS_DRIVER=log`
- بعد از deploy: callback واقعی با `APP_URL` درست

---

## گام ۹ — مستندات

- [DEVELOPER_GUIDE §9](../DEVELOPER_GUIDE.md) — پاراگراف درگاه جدید
- این فایل — اگر گام جدید اضافه شد
- `docs/dev/04-code-map.md` — نام کلاس
- `docs/PROJECT-PHASES.md` — اگر بخش محصولی است

---

## خلاصه یک نگاه

```
config/payment.php → MyGateway class
       ↓
PaymentGatewayCatalog::isEnabled + iconUrl  ← فراموش نشود
       ↓
SettingsService + Manage* Filament page
       ↓
routes + PaymentController + CSRF except
       ↓
تست + docs
```
