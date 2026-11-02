# راهنمای جامع توسعه — فروشگاه چینی بازار (ExportOS / mainshop)

> این سند برای زمانی است که به AI دسترسی ندارید. هدف: پیدا کردن سریع فایل‌ها، رفع مشکل، و افزودن قابلیت جدید بدون گم شدن در پروژه.
>
> **فهرست ۰ تا ۱۰۰ و اسناد تکمیلی:** [docs/README.md](README.md) · نقش‌ها/URL: [docs/dev/01-roles-and-auth.md](dev/01-roles-and-auth.md) · Production/K8s: [docs/dev/03-production-k8s-liara.md](dev/03-production-k8s-liara.md)

**Stack:** Laravel 12 · Livewire 3 · Filament 3 · Redis · PHP 8.2+  
**زبان UI:** فارسی (RTL) · **Admin brand:** چینی بازار

---

## فهرست

1. [شروع سریع](#1-شروع-سریع)
2. [نقشه پروژه](#2-نقشه-پروژه)
3. [معماری کلی](#3-معماری-کلی)
4. [فروشگاه (Frontend)](#4-فروشگاه-frontend)
5. [پنل ادمین (Filament)](#5-پنل-ادمین-filament)
6. [مدل‌ها و دیتابیس](#6-مدل‌ها-و-دیتابیس)
7. [لایه سرویس (Business Logic)](#7-لایه-سرویس-business-logic)
8. [سبد، Checkout و سفارش](#8-سبد-checkout-و-سفارش)
9. [پرداخت و درگاه‌ها (جزئیات کامل)](#9-پرداخت-و-درگاه‌ها-جزئیات-کامل)
10. [احراز هویت و OTP](#10-احراز-هویت-و-otp)
11. [Cache و Performance](#11-cache-و-performance)
12. [فایل، تصویر و آپلود](#12-فایل-تصویر-و-آپلود)
13. [SMS و Notification](#13-sms-و-notification)
14. [Config و Environment](#14-config-و-environment)
15. [Deploy (Runflare / Production)](#15-deploy-runflare--production)
16. [عیب‌یابی (Troubleshooting)](#16-عیب‌یابی-troubleshooting)
17. [دستورالعمل افزودن Feature جدید](#17-دستورالعمل-افزودن-feature-جدید)
18. [قراردادهای توسعه](#18-قراردادهای-توسعه)
19. [Variant، Attribute و موجودی](#19-variant-attribute-و-موجودی)
20. [کوپن تخفیف](#20-کوپن-تخفیف)
21. [روش ارسال (Shipping)](#21-روش-ارسال-shipping)
22. [سیستم Settings (DB)](#22-سیستم-settings-db)
23. [محتوا، SEO، منو و اسلایدر](#23-محتوا-seo-منو-و-اسلایدر)
24. [مدیریت سفارش و پرداخت در ادمین](#24-مدیریت-سفارش-و-پرداخت-در-ادمین)
25. [Support، Middleware و Blade Components](#25-support-middleware-و-blade-components)
26. [فهرست کامل تست‌ها](#26-فهرست-کامل-تست‌ها)
27. [چک‌لیست پوشش راهنما](#27-چک‌لیست-پوشش-راهنما)
28. [مستندات تکمیلی (فهرست ۰ تا ۱۰۰)](#28-مستندات-تکمیلی-فهرست-۰-تا-۱۰۰)

---

## 1. شروع سریع

### نصب محلی

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
```

### اجرا

```bash
# همه‌چیز با هم
composer dev

# یا جداگانه
php artisan serve
php artisan queue:listen
```

### تست

```bash
composer test
# یا
php artisan test
php artisan test --filter=Checkout
```

### ورود ادمین (بعد از seed)

| فیلد | مقدار |
|------|--------|
| URL | `/admin` |
| موبایل | `09120000000` |
| رمز | مقدار `ADMIN_PASSWORD` در `.env` |

---

## 2. نقشه پروژه

```
mainshop/
├── app/
│   ├── Console/Commands/     # دستورات artisan سفارشی shop:*
│   ├── Contracts/            # Interfaceها (SmsSender)
│   ├── Filament/             # پنل ادمین
│   │   ├── Resources/        # CRUD هر موجودیت
│   │   ├── Pages/            # داشبورد، تنظیمات، ورود
│   │   ├── Widgets/          # نمودار و آمار
│   │   └── Support/          # helperهای Filament
│   ├── Http/Controllers/     # کنترلرهای صفحات ساده فروشگاه
│   ├── Livewire/             # کامپوننت‌های تعاملی فروشگاه
│   ├── Models/               # Eloquent models
│   ├── Notifications/        # ایمیل/دیتابیس notification
│   ├── Observers/            # invalidate cache بعد از save
│   ├── Policies/             # دسترسی user به order/payment
│   ├── Providers/            # AppServiceProvider, AdminPanelProvider
│   ├── Services/             # ⭐ منطق اصلی کسب‌وکار
│   └── Support/              # helperهای کوچک (SEO، format، placeholder)
├── bootstrap/
│   ├── app.php               # routing, middleware, health
│   └── overrides/            # override کلاس Livewire (آپلود)
├── config/
│   ├── shop.php              # TTL کش، OTP، cart، checkout
│   ├── payment.php           # زرین‌پال، تارا
│   ├── sms.php
│   ├── livewire.php
│   └── filesystems.php
├── database/migrations/      # schema
├── database/seeders/         # داده demo
├── docs/                     # همین راهنما
├── public/shop/              # CSS/JS/images فروشگاه (prebuilt)
├── resources/views/
│   ├── shop/                 # blade صفحات controller
│   ├── livewire/             # blade کامپوننت Livewire
│   ├── filament/             # override view ادمین
│   └── layouts/shop.blade.php
├── routes/web.php            # همه route فروشگاه
└── tests/Feature|Unit/
```

### قانون طلایی

| کار | کجا انجام شود |
|-----|----------------|
| منطق کسب‌وکار | `app/Services/` |
| UI تعاملی فروشگاه | `app/Livewire/` + `resources/views/livewire/` |
| CRUD ادمین | `app/Filament/Resources/` |
| صفحات ساده (لیست SEO) | `app/Http/Controllers/` + `resources/views/shop/` |
| تنظیمات | `config/*.php` + جدول `settings` + `.env` |

**Repository Pattern نداریم** — عمداً. از Action/Service استفاده کنید.

---

## 3. معماری کلی

```
[مرورگر]
    ↓
routes/web.php
    ↓
┌─────────────────┬──────────────────────┐
│ Controller      │ Livewire Component   │
│ (صفحه ساده)     │ (سبد، checkout، ...) │
└────────┬────────┴──────────┬───────────┘
         ↓                   ↓
    ShopCacheService    Services (Cart, Checkout, Payment, ...)
         ↓                   ↓
    Models / DB / Redis / Storage
```

### جریان خرید (Happy Path)

```
AddToCart → CartPage → CheckoutPage → placeOrder()
    → PaymentService::initiate() → درگاه
    → callback → verify → Order confirmed → Notification/SMS
```

### جریان cache

```
Model save → Observer → ShopCacheService::forget*()
Request → ShopCacheService::remember() → Redis
```

---

## 4. فروشگاه (Frontend)

### 4.1 Routeها — `routes/web.php`

| URL | Handler | توضیح |
|-----|---------|--------|
| `/` | `HomeController` | صفحه اصلی |
| `/products` | `ProductController@index` | لیست محصول |
| `/products/{slug}` | `ProductController@show` | جزئیات |
| `/categories/{slug}` | `CategoryController` | محصولات دسته |
| `/brands/{slug}` | `BrandController` | محصولات برند |
| `/blog`, `/blog/{slug}` | `BlogController` | وبلاگ |
| `/pages/{slug}` | `PageController` | صفحات CMS |
| `/cart` | `Livewire\Cart\CartPage` | سبد |
| `/checkout` | `Livewire\Checkout\CheckoutPage` | تسویه (auth) |
| `/login`, `/register` | Livewire Auth | OTP |
| `/account/*` | Livewire Account | پنل کاربر |
| `/payment/callback` | `PaymentController` | بازگشت زرین‌پال |
| `/payment/callback/tara` | `PaymentController` | بازگشت تارا |
| `/sitemap.xml` | `SitemapController` | SEO |

**API جدا نداریم** — همه چیز web + Livewire.

### 4.2 Controllerها — `app/Http/Controllers/`

| فایل | کار |
|------|-----|
| `HomeController` | home payload از cache |
| `ProductController` | listing + show |
| `CategoryController`, `BrandController` | فیلتر محصول |
| `PaymentController` | callback درگاه‌ها |
| `CartAddController` | POST افزودن سریع به سبد |
| `SitemapController` | sitemap XML |
| `Concerns/ListsShopProducts.php` | logic مشترک لیست |

### 4.3 Livewire — `app/Livewire/`

| پوشه | کامپوننت | کار |
|------|----------|-----|
| `Auth/` | `Login`, `Register`, `LoginModal` | OTP + رمز |
| `Cart/` | `CartPage`, `CartSidebar`, `CartCounter` | سبد |
| `Checkout/` | `CheckoutPage` | آدرس، ارسال، کوپن، درگاه |
| `Product/` | `AddToCart`, `VariantSelector`, `Reviews`, `Questions`, `ToggleWishlist` | محصول |
| `Account/` | `OrderList`, `OrderShow`, `AddressManager`, ... | حساب کاربر |
| `Layout/` | `HeaderAuth` | وضعیت login در header |

**Layout پیش‌فرض:** `resources/views/layouts/shop.blade.php`

### 4.4 Viewها — `resources/views/`

```
shop/
  home.blade.php
  products/index.blade.php, show.blade.php
  partials/header.blade.php, footer.blade.php, overlays.blade.php
livewire/
  checkout/checkout-page.blade.php
  cart/cart-page.blade.php
  ...
components/shop/
  product-card.blade.php, deal-card.blade.php
```

### 4.5 Assetهای CSS/JS

| مسیر | توضیح |
|------|--------|
| `public/shop/css/tailwind.css` | Tailwind اصلی (production) |
| `public/shop/css/custom.css` | override و layout (checkout، sidebar) |
| `public/shop/js/main.js` | JS فروشگاه |
| `public/shop/css/product.css` + `public/shop/js/product.js` | صفحه محصول: گالری، **لنز ذره‌بین** روی تصویر (دسکتاپ)، لایت‌باکس با دکمه زوم و تصویر وسط‌چین |
| `resources/css/app.css` | Vite — کم‌استفاده |

> **نکته:** layoutهای جدید را ترجیحاً در `custom.css` بنویسید؛ `tailwind.css` prebuilt است و ممکن است utilityهای `lg:` کامل نباشند.

---

## 5. پنل ادمین (Filament)

### 5.1 تنظیمات پنل

**فایل:** `app/Providers/Filament/AdminPanelProvider.php`

| تنظیم | مقدار |
|-------|--------|
| URL | `/admin` |
| Panel ID | `admin` |
| ورود | **`/admin/login`** — `App\Filament\Pages\Auth\Login` |
| دسترسی | `User::canAccessPanel(admin)` → `status && (is_admin \|\| is_sales_manager)` |
| مدیر فروش | همان پنل؛ محدودیت در `AdminAccess` + `RestrictSalesManagerAdminAccess` — جزئیات [dev/01-roles-and-auth.md](dev/01-roles-and-auth.md) |

### 5.2 Resourceها — `app/Filament/Resources/`

| Resource | Model | گروه منو |
|----------|-------|----------|
| `ProductResource` | Product | فروشگاه |
| `CategoryResource` | Category | فروشگاه |
| `BrandResource` | Brand | فروشگاه |
| `AttributeResource` | Attribute | فروشگاه |
| `OrderResource` | Order | فروشگاه |
| `PaymentResource` | Payment | فروشگاه |
| `CouponResource` | Coupon | فروشگاه |
| `ShippingMethodResource` | ShippingMethod | تنظیمات |
| `UserResource` | User | کاربران |
| `PostResource`, `PageResource` | Post, Page | محتوا |
| `HomeSliderResource`, `MenuItemResource` | ... | محتوا |
| `ProductReviewResource`, `ProductQuestionResource` | ... | محتوا |

### 5.3 Relation Managerهای محصول

| فایل | کار |
|------|-----|
| `ImagesRelationManager` | گالری تصویر |
| `VariantsRelationManager` | variant + قیمت + موجودی |
| `AttributesRelationManager` | ویژگی‌های محصول |

### 5.4 صفحات سفارشی

| Page | مسیر admin | کار |
|------|------------|-----|
| `ManageGeneralSettings` | `/admin/manage-general-settings` | نام سایت، لوگو |
| `ManageIntegrations` | `/admin/manage-integrations` | زرین‌پال، تارا، کاوه‌نگار |

### 5.5 Sidebar سفارشی

`resources/views/filament/partials/admin-sidebar.blade.php` — منوی چندپanel (فروشگاه، درگاه‌ها، ...)

### 5.6 Helperهای Filament — `app/Filament/Support/`

| کلاس | کار |
|------|-----|
| `AdminImageColumn` | ستون تصویر جدول |
| `SeoFormSchema` | فیلدهای SEO مشترک |
| `RichContentEditor` | ادیتور محتوا |
| `CrudSuccessNotification` | پیام فارسی موفقیت |
| `ShopIconUpload` | آپلود آیکون درگاه/ارسال |

---

## 6. مدل‌ها و دیتابیس

### 6.1 مدل‌های اصلی — `app/Models/`

```
Product ─┬─ ProductImage (hasMany)
         ├─ ProductVariant (hasMany)
         ├─ Category, Brand (belongsTo)
         └─ Attribute (belongsToMany)

Order ─┬─ OrderItem (hasMany)
       ├─ Payment (hasMany)
       ├─ User, UserAddress, ShippingMethod, Coupon
       └─ OrderNote (timeline)

User ─ orders, addresses, cart, wishlist, payments
```

### 6.2 وضعیت‌های مهم

**Order:** `pending` → `processing` → `shipped` → `delivered` | `canceled` | `returned`

**Payment:** `pending` → `success` | `failed` | `canceled` | `refunded`

### 6.3 Migrationها

```
database/migrations/
  2026_04_19_*   # جداول اصلی
  2026_05_31_*   # SEO، reviews، settings، sliders
  2026_08_*      # stock_reserved، icons
```

```bash
php artisan migrate
php artisan migrate:status
php artisan migrate:rollback --step=1
```

### 6.4 Seederها

| Seeder | کار |
|--------|-----|
| `DatabaseSeeder` | admin + orchestrate |
| `ShopDemoSeeder` | دسته، برند، slider |
| `SampleProductsSeeder` | محصول demo |
| `SampleOrdersSeeder` | سفارش نمونه |
| `TestShopSeeder` | 2000 محصول (`SEED_HEAVY=true`) |

---

## 7. لایه سرویس (Business Logic)

**همیشه logic را اینجا بگذارید، نه در Controller/Livewire.**

### `app/Services/`

| پوشه | سرویس | مسئولیت |
|------|--------|---------|
| `Cart/` | `CartService` | سبد مهمان (Redis) + user (DB) |
| `Cart/` | `StockService` | رزرو/کاهش/آزادسازی موجودی |
| `Checkout/` | `CheckoutService` | preview، `placeOrder()` |
| `Checkout/` | `CouponService` | اعتبارسنجی کوپن |
| `Order/` | `OrderService` | cancel، ship، expire |
| `Order/` | `OrderActivityLogger` | timeline سفارش |
| `Payment/` | `PaymentService` | create، initiate، verify |
| `Payment/` | `ZarinpalGateway`, `TaraGateway` | درگاه |
| `Payment/` | `PaymentGatewayCatalog` | لیست درگاه برای checkout |
| `Payment/` | `TaraRefundService` | استرداد تارا |
| `Cache/` | `ShopCacheService` | کش فروشگاه |
| `Settings/` | `SettingsService` | تنظیمات DB + env |
| `Auth/` | `OtpService` | OTP |
| `Auth/` | `ShopLoginGuard` | جدا کردن login ادمین/فروشگاه |
| `Sms/` | `KavenegarSmsSender`, `LogSmsSender` | SMS |
| `Sms/` | `OrderSmsNotifier` | SMS سفارش |
| `Product/` | `VariantGenerator` | ساخت variant از attribute |

### تزریق در Livewire

```php
public function mount(CheckoutService $checkout, ShopCacheService $cache) { ... }
```

---

## 8. سبد، Checkout و سفارش

### سبد مهمان vs لاگین

| حالت | محل ذخیره |
|------|-----------|
| مهمان | Redis/cache — prefix: `cart:guest:` |
| لاگین | جدول `shopping_cart` |

**Merge:** بعد از login سبد مهمان به DB منتقل می‌شود (`CartService`).

### Checkout — `app/Livewire/Checkout/CheckoutPage.php`

1. انتخاب/ثبت آدرس (inline) — استان/شهر از `provinces`/`cities` (select، نه متن آزاد)، کد پستی با `IranianPostalCode`
2. تا وقتی `addressId` انتخاب/ذخیره نشده، بخش کوپن/درگاه/ثبت سفارش غیرفعال است
3. نام گیرنده (اجباری `*`) از `users.name` پر می‌شود؛ موبایل گیرنده (اجباری `*`) با الگوی `09xxxxxxxxx`. نام فقط در مودال OTP (مرحله `profile`) گرفته می‌شود — کارت جدا در checkout وجود ندارد
4. روش ارسال
5. کوپن
6. درگاه پرداخت
7. `CheckoutService::placeOrder()`
8. redirect به درگاه

**سبد کناری:** لینک «مشاهده سبد کامل» به `/cart` می‌رود؛ در `public/shop/js/main.js` فقط برای غیر-`<a>` با `data-close-cart` از `preventDefault` استفاده می‌شود.

**OTP / پروفایل:** مایگریت یا پاک‌کردن کش لازم نیست — پس از OTP، مرحله `profile` یک فیلد **نام و نام خانوادگی** (`users.name`) و **کد ملی** (`users.national_code`، `IranianNationalCode`) می‌گیرد. `layouts/shop.blade.php` مودال را برای مهمان **و** کاربر لاگین با نام placeholder (`UserDisplayName::isPlaceholder` — «کاربر 1090») رندر می‌کند؛ در این حالت `LoginModal::mount` مستقیم مرحله `profile` را باز می‌کند، دکمه بستن و کلیک بیرون غیرفعال است و تا تکمیل پروفایل در هیچ صفحه‌ای نمی‌توان ادامه داد. `profileUserId` (Locked) در صورت از دست رفتن session بین OTP و profile، کاربر را دوباره login می‌کند. بعد از `saveProfile` redirect با full reload انجام می‌شود.

### سفارش unpaid

- TTL: `SHOP_UNPAID_TTL_MINUTES` (پیش‌فرض 60)
- Scheduler: `shop:expire-pending-orders` هر 5 دقیقه — سفارش فروشگاه با `payment_method != cod` و بدون پرداخت موفق
- موجودی رزرو شده آزاد می‌شود

---

## 9. پرداخت و درگاه‌ها (جزئیات کامل)

### 9.1 نمای کلی

```
CheckoutPage (انتخاب درگاه)
    ↓
CheckoutService::placeOrder()
    ↓
PaymentService::createForOrder()  → رکورد Payment (pending)
    ↓
PaymentService::initiate()        → redirect URL درگاه
    ↓
[کاربر در سایت درگاه پرداخت می‌کند]
    ↓
PaymentController::callback / taraCallback
    ↓
PaymentService::verify()
    ↓
Order paid / partial paid → Notification + SMS
```

### 9.2 فایل‌های کلیدی

| فایل | مسئولیت |
|------|---------|
| `config/payment.php` | تعریف درگاه‌ها، driver class، env |
| `app/Services/Payment/PaymentGatewayInterface.php` | قرارداد: `initiate()`, `verify()` |
| `app/Services/Payment/PaymentGatewayManager.php` | resolve driver از config |
| `app/Services/Payment/PaymentGatewayCatalog.php` | لیست enabled، type، icon برای UI |
| `app/Services/Payment/PaymentService.php` | create، initiate، verify، idempotent |
| `app/Services/Payment/ZarinpalGateway.php` | درگاه نقدی |
| `app/Services/Payment/TaraGateway.php` | درگاه اعتباری |
| `app/Services/Payment/TaraRefundService.php` | استرداد تارا |
| `app/Services/Payment/AmountConverter.php` | تومان ↔ ریال |
| `app/Services/Payment/PaymentActivityLogger.php` | timeline پرداخت |
| `app/Http/Controllers/PaymentController.php` | callback + redirect تارا |
| `resources/views/payments/tara-redirect.blade.php` | صفحه میانی redirect تارا |

### 9.3 درگاه زرین‌پال (cash / نقدی)

| مورد | مقدار |
|------|--------|
| نام config | `zarinpal` |
| type | `cash` |
| کلاس | `ZarinpalGateway` |
| Admin | `/admin/zarinpal` → `ManageZarinpal.php` |
| Callback | `/payment/callback?payment={tracking_code}` |
| CSRF | exempt در `bootstrap/app.php` |

**تنظیمات (اولویت: DB → .env):**

| کلید settings | env |
|---------------|-----|
| `zarinpal.enabled` | — |
| `zarinpal.merchant_id` | `ZARINPAL_MERCHANT_ID` |
| `zarinpal.sandbox` | `ZARINPAL_SANDBOX` |
| `zarinpal.callback_url` | `ZARINPAL_CALLBACK_URL` |
| `zarinpal.amount_unit` | `ZARINPAL_AMOUNT_UNIT` |
| `zarinpal.icon` | — (آپلود admin) |

**API:** Zarinpal v4 — sandbox: `sandbox.zarinpal.com` / production: `payment.zarinpal.com`

### 9.4 درگاه تارا (credit / اعتباری)

| مورد | مقدار |
|------|--------|
| نام config | `tara` |
| type | `credit` |
| کلاس | `TaraGateway` |
| Admin | `/admin/tara` → `ManageTara.php` |
| Callback | `/payment/callback/tara?payment={tracking_code}` |
| Redirect میانی | `/payment/tara/{tracking}` |
| Refund | `TaraRefundService` — API جدا (club) |

**تنظیمات:**

| کلید settings | env |
|---------------|-----|
| `tara.enabled` | — |
| `tara.username` | `TARA_USERNAME` |
| `tara.password` | `TARA_PASSWORD` |
| `tara.service_id` | `TARA_SERVICE_ID` |
| `tara.sandbox` | `TARA_SANDBOX` |
| `tara.base_url` / `sandbox_base_url` | `TARA_BASE_URL` / `TARA_SANDBOX_BASE_URL` |
| `tara.refund_*` | `TARA_REFUND_*` |
| `tara.client_ip` | `TARA_CLIENT_IP` |
| `tara.default_group` | `TARA_DEFAULT_GROUP` |
| `tara.icon` | — (آپلود admin) |

**جریان تارا:** OAuth token → `/api/getToken` → redirect → callback → verify

### 9.5 پرداخت ترکیبی (Split Payment)

سناریو: کاربر بخشی با **تارا (اعتبار)** و مابقی با **زرین‌پال (نقدی)** می‌پردازد.

| مفهوم | فایل/متد |
|--------|-----------|
| مبلغ باقی‌مانده | `Order::remainingAmount()` |
| چند Payment روی یک Order | `payments` relation |
| پرداخت مجدد از حساب کاربر | `Livewire/Account/OrderShow.php` |
| UI انتخاب درگاه باقی‌مانده | `resources/views/livewire/account/order-show.blade.php` |
| تست | `tests/Feature/TaraSplitPaymentTest.php` |

```
سفارش 1,000,000 تومان
  → Payment#1 tara 400,000 success
  → remainingAmount = 600,000
  → Payment#2 zarinpal 600,000 → درگاه
```

### 9.6 PaymentGatewayCatalog — UI checkout

`PaymentGatewayCatalog` درگاه‌های **فعال** را برای checkout برمی‌گرداند:

```php
$catalog->cash();     // فقط type=cash (زرین‌پال)
$catalog->credit();   // فقط type=credit (تارا)
$catalog->enabled();  // همه enabled
$catalog->iconUrl('zarinpal');  // از settings + ShopMedia
$catalog->codEnabled();  // پرداخت در محل (COD) — از settings گروه `cod`
$catalog->codDefinition();  // label/description/icon COD برای checkout
```

**نام نمایشی:** فیلدهای `display_label` / `display_description` در settings هر درگاه (بخش «نمایش در تسویه حساب» در admin)؛ اگر خالی باشد از `config/payment.php` استفاده می‌شود.

**Checkout:** `CheckoutPage` + `PaymentGatewayCatalog` — COD فقط وقتی `cod.enabled` در admin روشن باشد  
**Account pay again:** `OrderShow` — cash/credit جدا

### 9.7 آیکون درگاه و ارسال

| Component | فایل |
|-----------|------|
| آپلود آیکون | `app/Filament/Support/ShopIconUpload.php` |
| مسیر ذخیره | `gateway-icons/` روی disk public |
| URL | `ShopMedia::url()` |
| checkout UI | `resources/views/components/checkout-option-icon.blade.php` |
| shipping icon | `ShippingMethodResource` — فیلد icon |

### 9.8 Admin — منوی درگاه‌ها

Sidebar: `resources/views/filament/partials/admin-sidebar.blade.php` — panel «درگاه‌ها»

| صفحه | slug admin |
|------|------------|
| پرداخت در محل (COD) | `/admin/cod` |
| زرین‌پال | `/admin/zarinpal` |
| تارا | `/admin/tara` |
| سداد | `/admin/sadad` |
| یکپارچه‌سازی (legacy) | `/admin/manage-integrations` |

ثبت در `AdminPanelProvider.php`: `ManageZarinpal`, `ManageTara`, `ManageSadad`, ...

### 9.9 CSRF و امنیت callback

`bootstrap/app.php`:

```php
$middleware->validateCsrfTokens(except: [
    'payment/callback',
    'payment/callback/tara',
    'payment/callback/sadad',
]);
```

**verify idempotent:** اگر Payment قبلاً `success` بود، دوباره پردازش نمی‌شود (`PaymentService::verify`).

### 9.10 واحد پول

| لایه | واحد |
|------|------|
| DB / Order / Cart | **تومان** |
| ارسال به درگاه | ریال (×10) via `AmountConverter` |
| config | `amount_unit`: `toman` یا `rial` |

### 9.11 عیب‌یابی درگاه

| مشکل | بررسی |
|------|--------|
| درگاه در checkout نیست | `enabled` در admin + `PaymentGatewayCatalog::isEnabled()` |
| verify fail | `storage/logs/laravel.log`، sandbox vs merchant |
| callback 419 | CSRF exempt؟ URL callback در admin درست؟ |
| تارا redirect نمی‌شود | `TARA_CLIENT_IP`، sandbox URL |
| split payment گیر کرد | `Order::remainingAmount()`، status payments |
| آیکون نمی‌آید | `storage:link`، path در settings |

### 9.12 افزودن درگاه جدید (recipe)

**آموزش کامل گام‌به‌گام:** [dev/07-add-payment-gateway.md](dev/07-add-payment-gateway.md)

خلاصه:

1. کلاس جدید implements `PaymentGatewayInterface` در `app/Services/Payment/`
2. ثبت در `config/payment.php` → `gateways.{name}`
3. **`PaymentGatewayCatalog::isEnabled()` و `iconUrl()`** — `match` برای نام درگاه (بدون این در checkout دیده نمی‌شود)
4. `SettingsService::{gateway}()` + صفحه Filament مثل `ManageZarinpal`
5. Route callback در `routes/web.php` + `PaymentController` + CSRF exempt در `bootstrap/app.php`
6. Validation در `CheckoutPage::checkoutRules()` از `enabledNames()`
7. Feature test + به‌روزرسانی مستندات ([00-doc-maintenance.md](dev/00-doc-maintenance.md))

**نمونه SOAP (PEC / پارسیان):** `ParsianGateway`، `PecIpgClient`، مستند نمونه در `docs/PHP.Samples.zip` — `OrderId` = `payments.id`، تأیید با `ConfirmPayment` پس از callback POST.

### 9.13 درگاه سداد (cash / نقدی — بانک ملی)

| مورد | مقدار |
|------|--------|
| نام config | `sadad` |
| type | `cash` |
| کلاس | `SadadGateway`، `SadadIpgClient`، `SadadSign` |
| Admin | `/admin/sadad` → `ManageSadad.php` |
| Callback | `/payment/callback/sadad` (POST — `token`, `ResCode`, `OrderId`) |
| مستندات پروژه | `docs/PHP-master.zip`، `docs/15470-51A234D3-CD9B-4DEB-8D6A-F9D72518FCB9.pdf`؛ [راهنمای سداد](https://sadadpsp.ir/ipg-dargah-pardakht) |

**جریان:** `PaymentRequest` (JSON + SignData Triple-DES) → redirect `sadad.shaparak.ir/VPG/Purchase?Token=…` → callback → `Advice/Verify`.

**تنظیمات:** `merchant_id`, `terminal_id`, `terminal_key` (Base64), `amount_unit`, `callback_url`, `icon`.

**OrderId** = `payments.id` (مثل پارسیان).

---

## 10. احراز هویت و OTP

| فایل | کار |
|------|-----|
| `OtpService` | generate، send، verify |
| `config/shop.php` → `otp` | length، expire، throttle |
| Cache key | `otp:{phone}` |

**Shop login:** مشتری — مودال/`/login` (staff نباید از این مسیر برای ادمین استفاده کند).  
**Admin login:** `/admin/login` — `AdminLoginGuard`: `is_admin` یا `is_sales_manager` + `status`. جزئیات: [dev/01-roles-and-auth.md](dev/01-roles-and-auth.md)

---

## 11. Cache و Performance

### `ShopCacheService` — `app/Services/Cache/ShopCacheService.php`

| Key | TTL پیش‌فرض | محتوا |
|-----|-------------|--------|
| `shop:home:payload` | 600s | slider، دسته، محصولات |
| `shop:categories:*` | 3600s | دسته‌ها |
| `shop:product:{slug}` | 900s | جزئیات محصول |
| `shop:shipping:active` | 3600s | روش ارسال |

### Invalidation — `app/Observers/`

هر save روی Product، Category، Brand، MenuItem، ... → `ShopCacheService::forget*()`

### دستورات

```bash
php artisan shop:cache-warm      # پر کردن cache
php artisan cache:clear          # پاک کردن
```

### Production (الزامی)

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

```bash
php artisan config:cache
php artisan route:cache
php artisan shop:cache-warm
```

---

## 12. فایل، تصویر و آپلود

### مسیر ذخیره

| نوع | disk | directory |
|-----|------|-----------|
| تصویر محصول | `public` | `products/` |
| variant | `public` | `products/variants/` |
| Livewire temp | `livewire-tmp` | `livewire-tmp/` |

### URL تصاویر

- Helper: `App\Support\ShopMedia::url($path)`
- DB path نسبی: `products/xxx.jpg`
- URL عمومی: `{FILESYSTEM_PUBLIC_URL}/products/xxx.jpg`

### Runflare (volume `/data`)

```env
FILESYSTEM_PUBLIC_ROOT=/data
FILESYSTEM_PUBLIC_URL=https://fm-cgtg-foroshgahsaz.runflare.cloud/data
LIVEWIRE_TEMP_DISK=livewire-tmp
LIVEWIRE_TEMP_ROOT=/data
```

```bash
php artisan shop:ensure-product-images   # placeholder برای تصاویر گم‌شده
php artisan shop:diagnose-uploads        # بررسی path و writable (اگر دستور موجود است)
composer dump-autoload -o                # برای override Livewire
```

### Override آپلود Livewire

`bootstrap/overrides/Livewire/TemporaryUploadedFile.php` — via `bootstrap/livewire-upload.php`

---

## 13. SMS و Notification

### SMS (OTP و تراکنشی)

| Driver | کلاس | زمان |
|--------|------|------|
| `log` | `LogSmsSender` | dev |
| `kavenegar` / sms.ir | `KavenegarSmsSender`, `SmsIrSmsSender` | production |

تنظیم درگاه از ادمین (یکپارچه‌سازی‌ها) یا `.env` — `config/sms.php`

### پیامک‌های تراکنشی (قابل ویرایش)

- ادمین: `/admin/transactional-sms` — `ManageTransactionalSms`
- پیش‌فرض: `config/transactional-sms.php`؛ ذخیره در `settings.transactional_sms`
- ارسال: `TransactionalSmsDispatcher` → `SendTransactionalSmsJob` (`afterResponse()` + queue)
- سفارش/پرداخت: `OrderSmsNotifier` از `CheckoutService`, `PaymentController`, `OrderService`, سرویس‌های نماینده

جزئیات رویدادها و افزودن قالب جدید: [`docs/dev/09-transactional-sms.md`](dev/09-transactional-sms.md)

### Notification — `app/Notifications/`

همه `ShouldQueue` — **queue worker لازم است**

| Notification | رویداد |
|--------------|--------|
| `OrderPlacedNotification` | ثبت سفارش |
| `PaymentSuccessNotification` | پرداخت موفق |
| `OrderShippedNotification` | ارسال |
| `OrderCanceledNotification` | لغو |

```bash
php artisan queue:listen
```

---

## 14. Config و Environment

### فایل‌های مهم

| فایل | محتوا |
|------|--------|
| `config/shop.php` | cache TTL، OTP، cart، unpaid TTL |
| `config/payment.php` | درگاه‌ها |
| `config/sms.php` | SMS |
| `config/livewire.php` | آپلود موقت |
| `config/filesystems.php` | diskها |

### Settings DB

جدول `settings` — groups: `site`, `zarinpal`, `tara`, `kavenegar`  
`SettingsService` اول DB را می‌خواند، بعد `.env`

---

## 15. Deploy (Runflare / Production)

### Checklist

```bash
git pull origin master
composer install --no-dev -o
php artisan migrate --force
php artisan storage:link
composer dump-autoload -o
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan shop:cache-warm
php artisan optimize:clear   # فقط اگر config cache مشکل داشت
chmod -R ug+rwx storage bootstrap/cache
```

### Scheduler (cron)

```bash
* * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1
```

### Queue worker

```bash
php artisan queue:work --sleep=3 --tries=3
```

### Multi-pod Kubernetes

- Redis و DB مشترک
- `/data` volume برای فایل‌ها
- `LIVEWIRE_TEMP_ROOT=/data` — **الزامی**

---

## 16. عیب‌یابی (Troubleshooting)

### مشکل → کجا را چک کنم

| مشکل | بررسی |
|------|--------|
| صفحه 500 | `storage/logs/laravel.log` |
| تصویر نمایش نمی‌شود | `storage:link`، `FILESYSTEM_PUBLIC_*`، `shop:ensure-product-images` |
| failed to upload | `shop:diagnose-uploads`، `.env` Runflare، multi-pod |
| سبد خالی می‌شود | `SESSION_DRIVER=redis`، cookie domain |
| OTP نمی‌رسد | `SMS_DRIVER`، Kavenegar settings، `storage/logs` |
| پرداخت verify fail | `storage/logs`، sandbox vs production merchant |
| cache قدیمی | Observer ثبت شده؟ `AppServiceProvider` |
| checkout کند | Redis فعال؟ `shop:cache-warm` |
| notification نمی‌آید | queue worker |
| سفارش unpaid expire نمی‌شود | scheduler/cron |
| Filament 403 | `is_admin=1` و `status=1` |

### دستورات debug

```bash
php artisan route:list --path=admin
php artisan route:list --path=payment
php artisan tinker
php artisan config:show filesystems.disks.public
tail -f storage/logs/laravel.log
```

### تست‌های مرتبط

```bash
php artisan test --filter=Checkout
php artisan test --filter=Tara
php artisan test --filter=ShopLivewire
php artisan test --filter=Seo
```

---

## 17. دستورالعمل افزودن Feature جدید

### A) فیلد جدید به محصول

1. Migration: `php artisan make:migration add_x_to_products_table`
2. Model: `$fillable` + `casts()` در `Product.php`
3. Filament: `ProductResource.php` → form + table
4. Frontend: view/Livewire در صورت نیاز
5. Observer: اگر روی listing اثر دارد → `ProductObserver`
6. Test: Feature test

### B) صفحه جدید فروشگاه (ساده)

1. Route در `routes/web.php`
2. Controller + view در `resources/views/shop/`
3. لینک در `MenuItem` (admin) یا header/footer
4. SEO: `SeoPresenter` یا `@section('meta')`

### C) کامپوننت Livewire جدید

```bash
php artisan make:livewire Shop/MyComponent
```

1. Logic در component یا inject Service
2. View: `resources/views/livewire/shop/my-component.blade.php`
3. Route: `Route::get('/path', MyComponent::class)`

### D) Resource ادمین جدید

```bash
php artisan make:filament-resource MyModel --generate
```

1. Model در `app/Models/`
2. Migration
3. Resource auto-discover می‌شود
4. Sidebar: `admin-sidebar.blade.php` در صورت نیاز

### E) درگاه پرداخت جدید

1. `app/Services/Payment/MyGateway.php` implements `PaymentGatewayInterface`
2. ثبت در `config/payment.php`
3. `PaymentGatewayManager` / `PaymentGatewayCatalog`
4. Route callback در `routes/web.php` + CSRF exempt در `bootstrap/app.php`
5. Feature test

### F) SMS/Notification جدید

1. Notification class در `app/Notifications/`
2. فراخوانی از `OrderService` یا `PaymentService`
3. queue worker

### G) کوپن / تخفیف جدید

1. Logic در `CouponService`
2. `CheckoutService::preview()` و `placeOrder()`
3. Admin: `CouponResource`

---

## 18. قراردادهای توسعه

### باید

- منطق در **Service**
- Validation در **Form Request** یا Filament form rules
- Authorization در **Policy**
- Side effect (cache) در **Observer**
- نام فارسی در UI
- commit message فارسی و واضح
- تست برای flow مالی و auth

### نباید

- Logic سنگین در Blade
- Query مستقیم در Livewire (بدون Service/Model scope)
- Repository اضافی
- TODO ناقص commit کنید
- over-engineering

### ساختار commit

```
verb + توضیح کوتاه فارسی

مثال: رفع خطای آپلود تصویر در Filament relation manager
```

### Branch naming (Cloud Agent)

```
cursor/feature-name-fb08
```

---

## 19. Variant، Attribute و موجودی

### جریان محصول متغیر

```
Product
  → AttributesRelationManager (ویژگی + is_variation)
  → VariantGenerator::generate()  (ترکیب cartesian)
  → VariantsRelationManager (قیمت، stock، SKU، تصویر)
```

| فایل | کار |
|------|-----|
| `AttributesRelationManager` | اتصال attribute به محصول |
| `ValuesRelationManager` | مقادیر هر attribute |
| `VariantsRelationManager` | CRUD variant |
| `VariantGenerator` | ساخت خودکار variant از ترکیب attribute |
| `StockService` | assert، reserve، decrement، release |
| `ProductVariantMatrix` | helper matrix در frontend |
| `Livewire/Product/VariantSelector.php` | انتخاب variant در صفحه محصول |

### موجودی در سفارش

1. `placeOrder()` → `StockService::reserve()` — `orders.stock_reserved = true`
2. پرداخت موفق → `decrement()`
3. لغo / expire → `release()`
4. Migration: `stock_reserved` روی `orders`

---

## 20. کوپن تخفیف

| فایل | کار |
|------|-----|
| `app/Models/Coupon.php` | code، type، amount، min_order، expires |
| `app/Services/Checkout/CouponService.php` | validate، apply، record usage |
| `app/Filament/Resources/CouponResource.php` | admin CRUD |
| `CheckoutPage` | فیلد coupon + preview |
| ویزارد نماینده (جمع‌بندی) | `RepresentativeDraftCouponService` + `CouponService` — همان قوانین کوپن برای مشتری نماینده |

**انواع:** درصد / مبلغ ثابت — متد `Coupon::calculateDiscount()`

**Usage:** جدول `coupon_usages` — یک بار per user (بسته به rule)

---

## 21. روش ارسال (Shipping)

| فایل | کار |
|------|-----|
| `app/Models/ShippingMethod.php` | name، price، icon، is_active |
| `ShippingMethodResource` | admin + آپلود icon |
| `ShopCacheService::shippingMethods()` | cache |
| `ShippingMethodObserver` | invalidate |
| `CheckoutPage` | انتخاب روش ارسال |

**Migration icon:** `2026_08_16_215200_add_icon_to_shipping_methods_table.php`

---

## 22. سیستم Settings (DB)

### جدول `settings`

| column | مثال |
|--------|------|
| group | `zarinpal`, `tara`, `kavenegar`, `site` |
| key | `merchant_id`, `enabled`, `icon` |
| value | string |

### SettingsService — `app/Services/Settings/SettingsService.php`

```php
$settings->get('zarinpal', 'merchant_id');
$settings->set('zarinpal', 'enabled', true);
$settings->zarinpal();  // merge DB + config
$settings->tara();
$settings->kavenegar();
$settings->site();       // نام سایت، لوگو، ...
```

**Cache key:** `shop:settings:all` (TTL 3600)

**Admin pages:**
- `ManageGeneralSettings.php` — `/admin/manage-general-settings`
- `ManageZarinpal.php` — `/admin/zarinpal`
- `ManageTara.php` — `/admin/tara`
- `ManageIntegrations.php` — legacy یکجا

---

## 23. محتوا، SEO، منو و اسلایدر

### SEO

| فایل | کار |
|------|-----|
| `app/Support/SeoPresenter.php` | meta، OG tags |
| `app/Filament/Support/SeoFormSchema.php` | فیلدهای SEO در admin |
| `resources/views/components/seo-meta.blade.php` | render |
| `SitemapController` | `/sitemap.xml` |
| `robots.txt` | route در `web.php` |

**فیلدهای SEO:** `meta_title`, `meta_description`, `canonical`, ... روی Product، Category، Post، Page

### CMS

| موجودیت | Resource | Route |
|---------|----------|-------|
| Post (blog) | `PostResource` | `/blog/{slug}` |
| Page | `PageResource` | `/pages/{slug}` |
| HomeSlider | `HomeSliderResource` | home view |
| MenuItem | `MenuItemResource` | header/nav cache |

### MenuItem types

`link`, `mega_trigger`, `mega_promo`, `accordion` — `MenuItemObserver` → cache nav

### Rich content

`RichContentEditor` + `public/shop/css/rich-content.css` — HTML محصول/بلاگ/صفحات؛ دکمه **«بارگذاری و درج ویدئو»** (فایل MP4/WebM یا URL) → `<figure class="rich-content-video">` — `shop.rich_content.video_max_size_kb`

---

## 24. مدیریت سفارش و پرداخت در ادمین

### OrderResource

| Page | URL | کار |
|------|-----|-----|
| List | `/admin/orders` | لیست + فیلتر — ستون کاربر فقط **نام**؛ فیلتر **روش پرداخت** = درگاه‌های فعال (`PaymentGatewayCatalog`) + پرداخت در محل |
| View | `/admin/orders/{id}` | جزئیات، timeline، items؛ دکمه **PDF فاکتور** (همان قالب `pdf/sales-invoice` نماینده) برای سفارش‌های سایت و پیش‌فاکتور |

**View custom:** `resources/views/filament/orders/` — items table، notes

**ستون روش پرداخت:** `ShopLabels::orderPaymentMethodLabel()` — برای سفارش‌های قدیمی با `payment_method=online` نام درگاه از آخرین رکورد `payments.gateway` (مثلاً باجت‌پی)؛ سفارش‌های جدید فروشگاه slug درگاه (`bajet`، `tara`، …) را در `orders.payment_method` ذخیره می‌کنند.

**ستون نماینده (خرید مستقیم سایت):** `SHOP_DIRECT_REPRESENTATIVE_ID` (پیش‌فرض `81` — چینی بازار) در `CheckoutService::placeOrder` روی `orders.representative_id` ست می‌شود. این سفارش‌ها `isRepresentativeOrder()` نیستند (`isDirectShopRepresentativeAttribution()`). سفارش‌های قدیمی بدون نماینده: `php artisan shop:assign-direct-shop-representative` (یا `--dry-run`).

### PaymentResource

| Page | URL |
|------|-----|
| List | `/admin/payments` |
| View | `/admin/payments/{id}` |

### Activity timeline

| Logger | Model | انواع note |
|--------|-------|-----------|
| `OrderActivityLogger` | `OrderNote` | system، private، customer |
| `PaymentActivityLogger` | `PaymentNote` | status changes |

**Backfill:** `php artisan shop:backfill-order-notes`

### Widgets داشبورد

| Widget | فایل |
|--------|------|
| آمار KPI | `ShopStatsOverview.php` |
| نمودار سفارش | `OrdersTrendChart.php` |
| نقشه | `OrdersMapWidget.php` + `IranCityCoordinates.php` |
| آخرین سفارش‌ها | `LatestOrdersTable.php` — ستون مشتری فقط نام (بدون موبایل) |
| موجودی کم | `LowStockProductsTable.php` |

---

## 25. Support، Middleware و Blade Components

### Support — `app/Support/`

| کلاس | کار |
|------|-----|
| `ShopMedia.php` | URL تصاویر/storage |
| `ShopFormatter.php` | format عدد/پول |
| `ShopLabels.php` | label وضعیت سفارش/پرداخت/درگاه؛ `formatMoney` (تومان UI)؛ `formatInvoiceMoney` / `invoiceAmountInRial` (فاکتور PDF و `sales-invoice`، ×۱۰) |
| `SeoPresenter.php` | SEO |
| `ProductPlaceholder.php` | SVG placeholder تصویر |
| `ProductSpecRows.php` | جدول مشخصات |
| `ProductVariantMatrix.php` | variant matrix |
| `IranCityCoordinates.php` | نقشه admin |

### Middleware

| فایل | کار |
|------|-----|
| `SetPersianLocale.php` | locale=fa |
| `bootstrap/app.php` | trustProxies، CSRF except payment |

### Blade Components — `resources/views/components/`

| Component | کار |
|-----------|-----|
| `shop/product-card.blade.php` | کارت محصول |
| `shop/deal-card.blade.php` | کارت تخفیف |
| `checkout-option-icon.blade.php` | آیکون در checkout |
| `seo-meta.blade.php` | meta tags |

### Filament Support

| کلاس | کار |
|------|-----|
| `FileUploadSanitizer.php` | fix آپلود invalid قبل validation |
| `AdminImageColumn.php` | ستون تصویر |
| `PriceRangeFilter.php` | فیلتر قیمت admin |
| `ShopIconUpload.php` | آپلود icon |

---

## 26. فهرست کامل تست‌ها

| فایل | پوشش |
|------|------|
| `CheckoutAndPaymentLaunchTest.php` | checkout کامل + زرین‌پال |
| `TaraSplitPaymentTest.php` | پرداخت ترکیبی تارا + refund |
| `CheckoutPageTest.php` | layout checkout، icon درگاه/ارسال |
| `ShopLivewireTest.php` | OTP، cart، add-to-cart |
| `ShopAuthSecurityTest.php` | admin نتواند shop login |
| `SeoRoutesTest.php` | slug routes، sitemap، redirect |
| `LivewireFileUploadControllerTest.php` | آپلود temp |
| `TemporaryUploadedFileOverrideTest.php` | override Livewire |
| `FileUploadSanitizerTest.php` | sanitizer |
| `LivewireUploadFilenameTest.php` | نام فایل کوتاه |
| `ShopMediaTest.php` | URL media |

```bash
php artisan test                    # همه
php artisan test --filter=Tara      # تارا
php artisan test --filter=Checkout  # checkout
```

---

## 27. چک‌لیست پوشش راهنما

| موضوع | بخش |
|-------|-----|
| شروع پروژه | §1 |
| ساختار پوشه‌ها | §2 |
| Routes فروشگاه | §4 |
| Livewire components | §4.3 |
| Filament admin + Resources | §5 |
| Models + migrations | §6 |
| Services layer | §7 |
| سبد و checkout | §8 |
| **زرین‌پال (کامل)** | §9.3 |
| **تارا (کامل)** | §9.4 |
| **پرداخت ترکیبی** | §9.5 |
| **آیکون درگاه** | §9.7 |
| **Admin درگاه‌ها** | §9.8 |
| OTP و auth | §10 |
| Cache + Redis | §11 |
| آپلود + Runflare `/data` | §12 |
| SMS + Kavenegar | §13 |
| .env کامل | §14 |
| Deploy + cron + queue | §15 |
| Troubleshooting | §16 |
| Recipes feature جدید | §17 |
| **Variant + stock** | §19 |
| **کوپن** | §20 |
| **Shipping** | §21 |
| **Settings DB** | §22 |
| **SEO + CMS + Menu** | §23 |
| **Order/Payment admin** | §24 |
| Support helpers | §25 |
| Tests | §26 |

---

## پیوست: فایل‌های کلیدی (Quick Reference)

```
routes/web.php
app/Providers/AppServiceProvider.php
app/Providers/Filament/AdminPanelProvider.php
app/Services/Checkout/CheckoutService.php
app/Services/Payment/PaymentService.php
app/Services/Cart/CartService.php
app/Services/Cache/ShopCacheService.php
app/Livewire/Checkout/CheckoutPage.php
app/Filament/Resources/ProductResource.php
app/Filament/Resources/ProductResource/RelationManagers/ImagesRelationManager.php
config/shop.php
config/payment.php
config/filesystems.php
.env.example
tests/Feature/CheckoutAndPaymentLaunchTest.php
public/shop/css/custom.css
resources/views/layouts/shop.blade.php
```

---

## 28. مستندات تکمیلی (فهرست ۰ تا ۱۰۰)

| سند | محتوا |
|-----|--------|
| [docs/README.md](README.md) | فهرست کامل فازهای ۰–۱۰۰ با لینک |
| [dev/01-roles-and-auth.md](dev/01-roles-and-auth.md) | مشتری / ادمین / مدیر فروش / نماینده |
| [dev/02-local-setup.md](dev/02-local-setup.md) | نصب لوکال |
| [dev/03-production-k8s-liara.md](dev/03-production-k8s-liara.md) | deploy، env بدون `.env`، Redis، `/data` |
| [dev/04-code-map.md](dev/04-code-map.md) | نقشه فایل و `shop:*` |
| [dev/05-troubleshooting-playbook.md](dev/05-troubleshooting-playbook.md) | عیب‌یابی copy-paste |
| [dev/06-feature-recipes.md](dev/06-feature-recipes.md) | الگوی فیچر جدید |
| [dev/07-add-payment-gateway.md](dev/07-add-payment-gateway.md) | افزودن درگاه پرداخت |
| [dev/08-add-limited-access.md](dev/08-add-limited-access.md) | دسترسی محدود / مدیر فروش |
| [dev/00-doc-maintenance.md](dev/00-doc-maintenance.md) | سیاست به‌روز doc با هر PR |
| [PROJECT-PHASES.md](PROJECT-PHASES.md) | وضعیت فازهای محصول |
| [representative/PHASE-PLAN-FA.md](representative/PHASE-PLAN-FA.md) | پنل نماینده |
| [../AGENTS.md](../AGENTS.md) | اشاره برای Cursor |

---

## Changelog این سند

| تاریخ | توضیح |
|-------|--------|
| 2026-11-02 | محصولات ادمین: نمایش محصول، ستون‌ها، تیک چک‌باکس، padding سلول‌ها؛ **چیدمان پنل:** محتوا (`content-area`، نوار بالا، هدر/جدول لیست) بدون فاصلهٔ سفید کنار سایدبار (`padding-inline-start: 0`، `filament-overrides.css`) |
| 2026-11-01 | **COD و نام درگاه:** `/admin/cod` (فعال/غیرفعال، عنوان، توضیح، آیکون)؛ هر درگاه → بخش «نمایش در تسویه حساب» (`display_label` / `display_description`)؛ `PaymentGatewayCatalog` + checkout + فیلتر سفارش/کوپن |
| 2026-08-23 | نسخه اول — راهنمای جامع offline |
| 2026-08-23 | نسخه 1.1 — تکمیل درگاه‌ها، variant، coupon، settings، SEO، tests |
| 2026-10-01 | نسخه 1.2 — لینک به docs/README، مدیر فروش، `/admin/login` |
| 2026-10-02 | نسخه 1.3 — dev/07 درگاه، dev/08 دسترسی، PROJECT-PHASES، سیاست doc |
| 2026-10-02 | نسخه 1.4 — پیامک تراکنشی (dev/09)، ادمین ریسپانسیو |
| 2026-10-12 | داشبورد پنل نماینده: `RepresentativeDashboardService` + ویجت‌های Filament در `/representative` |
| 2026-10-12 | داشبوردها بدون سایه `shadow-sm` (override در `filament-overrides.css` روی `.fi-dashboard-page`) |
| 2026-10-12 | رفع 500 داشبورد نماینده (تایم‌زون Jalalian) + `livewire-session-guard.js` و `/session/ping` برای حذف دیالوگ 419 در کل سایت (dev/05 §A) |
| 2026-10-12 | داشبورد نماینده: دکمه «مشتری جدید» کنار «سفارش جدید» |
| 2026-10-12 | درخواست رزرو مجدد پیش‌فاکتور: جدول `proforma_reservation_requests` + `representative_profiles.sales_manager_id` (مدیر منطقه)، `ProformaReservationRequestService`، Resource ادمین «درخواست‌ها» (`ReservationRequestResource`، مجاز برای مدیر فروش، badge تعداد در انتظار در سایدبار) |
| 2026-10-12 | داشبورد ادمین/مدیر فروش: ویجت‌های آمار، نمودار و آخرین سفارش‌ها eager (فقط نقشه lazy) تا درخواست‌های موازی Livewire و 419 کم شود؛ لاگ هشدار fallback ردیس؛ guard 419 تا ۳ reload در دقیقه و بعد پیام غیرمسدودکننده به‌جای دیالوگ |
| 2026-10-12 | مدیر منطقه (مدیر فروش): ایجاد نماینده (`UserResource::canCreate`، فرم ساده، `sales_manager_id` = خودش)، دکمه «افزودن نماینده» روی داشبورد `/admin`، لیست فقط نمایندگان تحت مدیریت خودش |
| 2026-10-14 | رفع ذخیره `/admin/search-popup-settings`: `array_merge` به‌جای `array_replace_recursive` برای لیست جستجوهای پرتکرار |
| 2026-10-14 | `/admin/products`: ستون **تصویر** (تصویر اصلی محصول در لیست) |
| 2026-10-14 | `/admin/products`: ستون قیمت با **تومان** (`ShopLabels::formatMoney` به‌جای `money('irr')`) |
| 2026-10-14 | `/admin/products`: جدول لیست **فشرده** — padding کمتر، عرض ستون‌ها، تصویر ۴۴px، نام دوخط (`lineClamp`) |
| 2026-10-14 | رفع نمایش باریک تصویر در جدول‌های ادمین (`AdminImageColumn` + `width`/`square`، CSS thumb) |
| 2026-10-14 | `/admin/database-backups`: بک‌آپ **فایل‌ها** (دستی + `shop:files-backup` روزانه) — `FilesBackupService`، نگه‌داری ۱ نسخه، منبع `FILESYSTEM_PUBLIC_ROOT` |
| 2026-10-14 | `/admin/sales-regions`: لیست — همهٔ استان‌های هر منطقه (badge، بدون محدودیت ۴تایی)؛ ویرایش — جدول **نمایندگان این منطقه** (`SalesRegionService::representativesForRegion`) |
| 2026-10-12 | **مناطق فروش**: جدول `sales_regions` + `provinces.sales_region_id` (هر استان فقط یک منطقه، هر منطقه چند استان، هر مدیر چند منطقه)؛ CRUD ادمین `/admin/sales-regions` (`SalesRegionResource`، منطق `SalesRegionService`)؛ تعریف نماینده: مدیر منطقه الزامی + استان فقط از مناطق همان مدیر؛ مدیر منطقه هنگام ساخت نماینده = خودش؛ لیست «نمایندگان من» = `sales_manager_id` خودش یا استان در مناطق خودش؛ تغییر مدیر منطقه → نمایندگان آن استان‌ها منتقل می‌شوند؛ ستون/فیلتر «مدیر منطقه» در لیست کاربران؛ رفع ارتقای نقش (مدیر فروش دیگر نمی‌تواند `is_admin`/`user_kind`/نقش‌ها را تغییر دهد). تست: `tests/Feature/SalesRegionTest.php` (MariaDB) |
| 2026-10-12 | رفع migration مناطق فروش روی MySQL (خطای 1824 `Failed to open the referenced table 'sales_regions'`): migration idempotent — `sales_regions` با InnoDB (و تبدیل اگر MyISAM ساخته شده)، ستون/ایندکس جدا، FKها فقط اگر نبودند و با try/catch؛ `SalesRegion::deleting` استان‌ها را آزاد می‌کند. اجرای دوبارهٔ `php artisan migrate --force` کافی است |
| 2026-10-12 | منوی **مناطق فروش** جدا از کاربران: آیکون و پنل مستقل در `admin-sidebar` (لیست مناطق، افزودن منطقه) — فقط مدیر کل |
| 2026-10-12 | پنل ادمین تماماً سفید: پس‌زمینه body/shell، هدر و pagination جدول، تب‌های کاربر، صفحه ورود (`filament-overrides.css`، `styles.css`) |
| 2026-10-17 | صفحه محصول — باکس خرید: بنر اقساط دیجی‌پی (آیکون ۴۸×۴۸، متن پیش‌فرض «این درگاه اعتباری دیجی‌پی در ۴ قسط»)؛ ادمین → دیجی‌پی → `show_product_installment_promo` + `product_installment_promo_text` |
| 2026-10-17 | فاکتور و جزئیات سفارش: **کد ملی** خریدار؛ با ثبت `national_code` → ایمیل `{کد}@chinibazar.ir` و رمز `/*{کد}/*` (`UserNationalCodeCredentials`) |
| 2026-10-17 | مودال OTP فروشگاه: پس از کد — **یک فیلد نام کامل** + **کد ملی** در `users.name` / `users.national_code` (`HandlesOtpLogin::saveProfile`) |
| 2026-10-14 | نام کاربر OTP فقط در مودال اجباری (رندر برای کاربر placeholder هم)؛ حذف کارت «تکمیل نام حساب» از checkout؛ `*` روی نام/موبایل گیرنده |
| 2026-10-14 | لیست‌های ادمین: ستون/زیرنویس **موبایل** برای کاربر (`UserTableColumns`، `/admin/users` و سفارش/پرداخت/نظرات و …) |
| 2026-10-14 | `/admin/users`: تب‌های مشتریان، مدیران مناطق، مدیران سیستم، نمایندگان، نویسندگان + ستون/فیلتر **دسته** (`adminListSegment`) |
| 2026-10-14 | `/admin/orders`: ستون کاربر فقط نام — بدون نمایش موبایل زیر نام |
| 2026-10-17 | دیجی‌پی `purchases/deliver`: فیلد `products` = عناوین فارسی `order_items.product_name` (×تعداد) |
| 2026-10-17 | `/admin/orders/{id}` و جزئیات پرداخت: **لغو دیجی‌پی** — عودت API (`refunds?type=`) via `DigiPayRefundService` |
| 2026-10-20 | فوتر و صفحات استاتیک: حذف متن دمو؛ migration footer (cache key)؛ حذف chapino از تماس/درباره؛ seeders بدون overwrite؛ `<title>` از `meta_title`/`title` همان صفحه در ادمین |
| 2026-10-18 | صفحه محصول: **ذره‌بین روی تصویر** (لنز سبز + پیش‌نمایش ۴.۴× داخل لنز، بدون پنل کنار)؛ لایت‌باکس: دکمه بزرگنمایی + pan — `product.js` |
| 2026-10-18 | `RichContentEditor`: دکمه **بارگذاری و درج ویدئو**؛ فروشگاه: `RichContentPresenter` — ویدئوها **بالای** متن در `x-shop.rich-content` |
| 2026-10-18 | صفحه اصلی — لینک‌های خانواده: repeater **نام/URL** در ویرایش خانواده (`product_family_homepage_popup_items`); migration `2026_10_18_100000_add_homepage_popup_to_product_families`; `shop:diagnose-family-homepage-popups` |
| 2026-10-21 | پیش‌فاکتور نماینده: قیمت ردیف بدون مالیات، ردیف ارزش افزوده، **جمع کل** (بدون ردیف جمع اقلام بدون مالیات) — `document.blade.php` |
| 2026-10-17 | `/admin/orders/{id}`: دو PDF فاکتور — **مشتری** و **مالی** (`?financial=1`)؛ نسخه مالی: ردیف جدول **ارزش افزوده** بعد از «جمع اقلام» — `shop.invoice.vat_percent` |
| 2026-10-17 | `/admin/orders`: فیلتر روش پرداخت بر اساس **درگاه‌های فعال** (باجت‌پی، دیجی‌پی، …) + `cod`؛ تطبیق `payment_method` و سفارش‌های `online` با `payments.gateway` |
| 2026-10-17 | **فاکتور** (`components/sales-invoice/document`، PDF، رسید پرداخت): نمایش مبالغ **ریال** (`ShopLabels::formatInvoiceMoney`، تومان داخلی ×۱۰) |
| 2026-10-17 | وضعیت سفارش **پردازش انبار** (`warehouse_processing`) بعد از «در حال پردازش»؛ migration `2026_10_17_120000_add_warehouse_processing_status_to_orders` (ENUM MySQL)؛ دکمه تأیید پرداخت؛ SMS `order_warehouse_processing` |
| 2026-10-17 | `/admin/product-price-stock-update` — **بروزرسانی قیمت و موجودی** (فیلتر دسته/موجودی، ویرایش سطری، درصد گروهی قیمت، جمع موجودی) — `ProductPriceStockUpdateService` |
| 2026-10-17 | بروزرسانی قیمت: **اعمال گروهی جدا** — «اعمال درصد قیمت» و «اعمال موجودی» (`bulkAdjustPriceOnQuery` / `bulkAdjustStockOnQuery`) |
| 2026-10-17 | `/admin/products` — UI لیست محصولات هماهنگ با صفحه بروزرسانی قیمت (`public/css/admin-product-list.css`، badge برای ID/دسته) |
| 2026-10-14 | لیست/جزئیات سفارش ادمین: برچسب روش پرداخت = نام درگاه (`orderPaymentMethodLabel`)؛ checkout slug درگاه را در `orders.payment_method` ذخیره می‌کند |
| 2026-10-14 | سفارش مستقیم فروشگاه: `representative_id` = `SHOP_DIRECT_REPRESENTATIVE_ID` (پیش‌فرض ۸۱ چینی بازار)؛ backfill `shop:assign-direct-shop-representative` |
| 2026-10-23 | OTP فروشگاه: کاربر با نام+کد ملی کامل دوباره مودال پروفایل نمی‌بیند؛ جستجوی موبایل `User::findByLoginPhone` — `UserDisplayName::isShopProfileComplete` |
| 2026-10-23 | رفع 500 آنالیز کد تخفیف: `CouponAnalyticsPage::mount($record)` بدون پارامتر DI دوم (Livewire + route named arg) |
| 2026-10-23 | ادمین: لاگ پرداخت/سیستم زیر منوی بک‌آپ؛ دانلود با کلیک روی فایل؛ `LOG_STACK` پیش‌فرض `daily` روی `/data`؛ checkout: پیام کد تخفیف فقط مودال؛ درگاه کد تخفیف = Select چندتایی (مثل استان منطقه) |
| 2026-10-26 | `config/logs.php` (الگوی `backup.php`): مسیر `/data/logs`، نگه‌داری ۳۰+ روز لاگ پرداخت، zip آرشیو، پنل `/admin/payment-logs` |
| 2026-10-28 | پنل `/admin/payment-logs`: تایم‌لاین گرافیکی با جستجوی کد رهگیری یا `payment_id`؛ دکمه «پاسخ درگاه» برای نمایش خام `context.gateway_raw`؛ ثبت خام callback، verify و پاسخ initiate/response درگاه‌ها در `PaymentAuditLogger` |
| 2026-10-28 | migration پشتیبان ستون‌های `proforma_*` روی `orders`؛ `OrderProformaSplitColumns`؛ پیام خطای واضح اگر migrate اجرا نشده؛ اصلاح select درگاه نقدی ویزارد نماینده |
| 2026-10-28 | ویزارد نماینده — کارت محصول: `primary_image_path` (نه اولین position)؛ ارتفاع مرحله محصول برای نمایش کامل کارت |
| 2026-10-28 | پیش‌فاکتور PDF: بلوک «پرداخت‌های موفق» (جمع نقد/اعتبار) بالای مهر امضا؛ پرداخت نقدی با مانده پیش‌فرض؛ ledger فشرده |
| 2026-10-28 | ویزارد نماینده — جمع‌بندی: مبلغ اعتبار/نقد به **ریال** با جداکننده (`RialAmountInput`)، دکمه «ثبت + پرداخت نقدی»، ledger زیر فاکتور (سبز/قرمز)، درگاه‌ها compact flex، گرید محصول کوتاه‌تر |
| 2026-10-29 | اسلاگ محصول از نام: `SlugFromTitle` — فقط space/NBSP/tab/خط‌جدید → `-` (نه `\h`؛ حروف فارسی/لاتین حفظ) |
| 2026-10-29 | ادمین محصول: URL ویرایش با **id** (`getRecordRouteKeyName`، `getUrl`، لیست/ریدایrect/create) — فروشگاه همچنان slug |
| 2026-10-29 | فرم محصول: `_slug_name_source` — اسلاگ دستی حفظ می‌شود؛ همگام با نام فقط وقتی اسلاگ از نام ساخته شده |
| 2026-10-31 | درگاه **سداد** (`SadadGateway`): IPG JSON بانک ملی — `PaymentRequest` / `Verify`، SignData DES-EDE3؛ admin `/admin/sadad`؛ callback `/payment/callback/sadad`؛ مستندات `docs/PHP-master.zip` |
| 2026-10-31 | MediaPicker: اکشن‌های `openMediaCenter` / `clearImage` ثابت روی هر فیلد (Filament با `mountFormComponentAction(statePath, name)` کامپوننت را پیدا می‌کند؛ نام هش‌شده با state path باعث unmount خالی می‌شد) |
| 2026-10-31 | اسلایدر hero hub دسکتاپ: نمایش و آپلود **940×406** (`aspect-ratio` + preset `sliders`)؛ موبایل 750×400 (نمایش ≈989×388) |
| 2026-10-31 | hero hub: چیدمان mockup (`home-hero-hub__shell`، اسلایدر چپ / سایدبار راست با `direction:ltr`، `home-hero-hub__cards`) |
| 2026-10-29 | hero: موبایل order اسلایدر→سایدبار؛ دسکتاپ RTL ستون سایدبار راست؛ رفع ناپدید شدن تصویر اسلایدر (flex روی slide، picture block) |
| 2026-10-29 | hero layout fix: پس‌زمینه `#EDF0F2` تمام‌عرض (`home-hero-hub__inner` برای max-width)؛ رفع override `height:auto` روی Swiper؛ `SHOP_CSS_VERSION` / `shop.assets.css_version` |
| 2026-10-29 | hero: پس‌زمینه کل `section.home-hero-hub` = `#EDF0F2`؛ `home-hero-hub__grid` سفید؛ در دسکتاپ ارتفاع اسلایدر = ارتفاع grid (تصویر `object-fit: cover`)؛ لیست سایدبار با خط جداکننده |
| 2026-10-29 | کش صفحه اصلی: `ShopCacheService::HOME_PAYLOAD_KEY` (`shop:home:payload:v2`) — با تغییر ساختار hero نسخه را بالا ببرید تا بعد از deploy چیدمان قدیمی از کش نیاید |
| 2026-10-31 | **طرح‌ها** — ویجت صفحه اصلی: ۴ طرح + «مشاهده همه»؛ ردیف کارت‌ها **تمام‌عرض** زیر `home-hero-hub__shell` (padding `36px 22px 0`، گرید ۵ ستونه)؛ موبایل `homePlansSwiper` (۲ کارت) + `homePartnersSwiper`؛ `/plans` + کش `shop:plans:index:v1` |
| 2026-10-29 | hero سایدبار: لیست بالا فقط زیرمجموعه «سرویس چینی»؛ خانواده‌های `show_on_homepage_sidebar` بدون repeater → ردیف آیکون پایین (flex دایره‌ای) |
| 2026-10-29 | hero سایدبار (قبلی): چند خانوade + `rebuildAggregatedHeroSidebarLinks`؛ MediaPicker repeater |
| 2026-10-29 | hero: قاب سفید + پنل `#EDF0F2`؛ کارت درگاه RTL (لوگو راست در DOM)؛ کش CSS با `?v=filemtime`؛ سه خانوade سریع با fallback نام فارسی |
| 2026-10-28 | hero سایدبار: لینkها در JSON `homepage` (`hero_sidebar_links`) — تنظیم در `/admin/homepage-settings`؛ همگام از خانوade هنگام ذخیره؛ fallback DB خانوade؛ `shop:deploy-recover` اگر JSON خالی باشد از خانوade پر می‌کند |
| 2026-10-28 | hero سایدبار: اگر `hero_sidebar_family_id` لینک نداشت، خانواده «سرویس چینی»/chinese-service که لینک دارد انتخاب می‌شود؛ `forgetHome` همیشه `shop:home:payload` را پاک می‌کند |
| 2026-10-28 | لینک‌های سایدبار hero: **فروشگاه → خانواده محصول → ویرایش** بخش «لینک‌های کنار اسلایدر»؛ میانبر در `/admin/homepage-settings`؛ بدون نیاز به تیک پاپ‌آپ قدیمی |
| 2026-10-28 | صفحه اصلی: باکس hero (اسلایدر جمع‌وجور + لیست پاپ‌آپ خانواده + ۳ خانواده) + کارت طرح درگاه‌ها بالای «جدیدترین»؛ تنظیمات در `/admin/homepage-settings` و هر درگاه → «نمایش در صفحه اصلی» |
| 2026-10-28 | حذف راهنمای Intro.js پنل نماینده (PR #294–#296 برگشت) — بدون `public/rep-panel` و بدون تداخل `/representative` |
| 2026-10-28 | ویزارد نماینده — جمع‌بندی: کارت پرداخت ترکیبی، درگاه‌های نقدی با آیکون (flex)، CTA برجسته ثبت پیش‌فاکتور |
| 2026-10-28 | ویزارد review ارتفاع تا پایین viewport؛ کارت پرداخت ترکیبی گرافیکی؛ CMS `/pages/*`: عنوان بدون چاپینو؛ نوار ادمین «ویرایش این صفحه» |
| 2026-10-26 | رفع لاگ روی `/data/logs`: `config/logging.php` قبل از `shop.php` لود می‌شد — مسیر کانال‌ها با `config/logs.php` + `ShopStoragePaths` و هشدار `shop:verify-storage` |
| 2026-10-26 | درگاه **پارسیان (PEC)**: `ParsianGateway` + `PecIpgClient`؛ `CallBackUrl` بدون query (تطابق پنل پارسیان)، شناسایی پرداخت با `OrderId`؛ تنظیمات `/admin/parsian` |
| 2026-10-27 | پیش‌فاکتور نماینده: طرح اعتبار+نقد (`RepresentativeProformaSplitPayment`)، پرداخت نقد چندمرحله‌ای، ledger پرداخت‌ها |
| 2026-10-27 | ویزارد نماینده — انتخاب محصول: کارت با تصویر اصلی (`rep_product_card`)، گرید؛ رفع گالری (بارگذاری همه تصاویر، بدون lazy+p hidden) |
| 2026-10-27 | ویزارد نماینده: تب‌های کارخانه/قالب فقط وقتی برای خانواده/برند انتخاب‌شده در کاتالوگ وجود دارد (`RepresentativeCatalogLookup` + `OrderWizard`) |
| 2026-10-27 | بازگشت پارسیان: مسیر alias **`/payment/callback/pec`** (همان handler که `/payment/callback/parsian`)؛ پس از verify ریدایرکت به صفحه امضاشده **`payment.result`**؛ رفع TypeError ویجت `OrdersTrendChart` (Carbon) |
| 2026-10-25 | صفحه استاتیک `/plans/digipay-leasing/` — معرفی خرید اقساطی دیجی‌پی در چینی بازار (`DigipayLeasingController`، `plans-leasing.css`، سقف اعتبار ۲۰۰M) |
| 2026-10-23 | checkout کد تخفیف: پیام دقیق (کف سبد، درگاه، …) + مودال؛ ادمین: آنالیز `/admin/coupons/{id}/analytics` (`CouponAnalyticsPage`)، درگاه‌های مجاز (Select چندتایی)، npm `follow-redirects` ≥1.16.1؛ پس از deploy: `migrate --force` + `optimize:clear` |

> اگر بخش جدیدی به پروژه اضافه شد، همین فایل را در `docs/DEVELOPER_GUIDE.md` به‌روز کنید و در صورت نیاز `docs/README.md` و `docs/dev/*`.
