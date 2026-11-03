@php
    use App\Filament\Pages\ManageFooterSettings;
    use App\Filament\Pages\ManageSiteHeaderSettings;
    use App\Filament\Pages\ManageSearchPopupSettings;
    use App\Filament\Pages\ManageGeneralSettings;
    use App\Filament\Pages\ManageTrustBadges;
    use App\Filament\Pages\ManageIntegrations;
    use App\Filament\Pages\ManageSmsIr;
    use App\Filament\Pages\ManageTransactionalSms;
    use App\Filament\Pages\ManageHomepageImages;
    use App\Filament\Pages\ManageHomepageSettings;
    use App\Filament\Pages\ManageMediaPresets;
    use App\Filament\Pages\ManageBajetPay;
    use App\Filament\Pages\ManageDigiPay;
    use App\Filament\Pages\ManageTara;
    use App\Filament\Pages\ManageParsian;
    use App\Filament\Pages\ManageSadad;
    use App\Filament\Pages\ManageCodPayment;
    use App\Filament\Pages\ManageZarinpal;
    use App\Filament\Resources\AttributeResource;
    use App\Filament\Resources\BrandResource;
    use App\Filament\Resources\CreditPlanResource;
    use App\Filament\Resources\PartnerResource;
    use App\Filament\Resources\CategoryResource;
    use App\Filament\Resources\CouponResource;
    use App\Filament\Resources\HomeSliderResource;
    use App\Filament\Resources\MediaFileResource;
    use App\Filament\Resources\MenuItemResource;
    use App\Filament\Resources\OrderResource;
    use App\Filament\Resources\PageResource;
    use App\Filament\Pages\ViewDatabaseBackups;
    use App\Filament\Pages\ViewPaymentLogs;
    use App\Filament\Pages\ViewSiteErrorLogs;
    use App\Filament\Resources\PaymentResource;
    use App\Filament\Resources\PostResource;
    use App\Filament\Resources\ProductFamilyResource;
    use App\Filament\Resources\ProductPlantResource;
    use App\Filament\Pages\ProductListImport;
    use App\Filament\Pages\ProductPriceStockUpdate;
    use App\Filament\Resources\ProductResource;
    use App\Filament\Resources\ProductTemplateResource;
    use App\Filament\Resources\ProductQuestionResource;
    use App\Filament\Resources\ProductReviewResource;
    use App\Filament\Resources\ReservationRequestResource;
    use App\Filament\Resources\SalesRegionResource;
    use App\Filament\Resources\FreightCarrierResource;
    use App\Filament\Resources\ShippingMethodResource;
    use App\Filament\Resources\UserResource;

    use App\Support\AdminAccess;

    $user = filament()->auth()->user();
    $salesManagerOnly = AdminAccess::isSalesManagerOnly($user);
    $brand = filament()->getBrandName();
    $isDashboard = request()->routeIs('filament.admin.pages.dashboard');
    $profileUrl = UserResource::getUrl('edit', ['record' => $user->id], panel: 'admin');
    $dashboardUrl = filament()->getUrl();
    $pendingRequestsCount = $user
        ? app(\App\Services\Representative\ProformaReservationRequestService::class)->pendingCountFor($user)
        : 0;

    $panels = [
        'users' => [
            'label' => 'کاربران',
            'icon' => 'fa-users',
            'routes' => ['filament.admin.resources.users.*'],
            'menus' => [
                ['label' => 'کاربران', 'icon' => 'fa-users', 'items' => [
                    ['label' => 'مشتریان', 'url' => UserResource::getUrl('index'), 'icon' => 'fa-user'],
                    ['label' => 'غیر مشتری', 'url' => UserResource::getUrl('index', ['activeTab' => 'staff']), 'icon' => 'fa-user-tie'],
                    ['label' => 'افزودن کاربر', 'url' => UserResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
            ],
        ],
        'sales_regions' => [
            'label' => 'مناطق فروش',
            'icon' => 'fa-map-location-dot',
            'routes' => ['filament.admin.resources.sales-regions.*'],
            'menus' => [
                ['label' => 'مناطق فروش', 'icon' => 'fa-map-location-dot', 'items' => [
                    ['label' => 'لیست مناطق', 'url' => SalesRegionResource::getUrl('index'), 'icon' => 'fa-map'],
                    ['label' => 'افزودن منطقه', 'url' => SalesRegionResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
            ],
        ],
        'products' => [
            'label' => 'محصولات',
            'icon' => 'fa-box-open',
            'routes' => [
                'filament.admin.resources.products.*',
                'filament.admin.pages.product-price-stock-update',
                'filament.admin.pages.product-list-import',
                'filament.admin.product-import.template',
                'filament.admin.resources.categories.*',
                'filament.admin.resources.attributes.*',
                'filament.admin.resources.product-families.*',
                'filament.admin.resources.product-plants.*',
                'filament.admin.resources.product-templates.*',
            ],
            'menus' => [
                ['label' => 'محصولات', 'icon' => 'fa-box-open', 'items' => [
                    ['label' => 'لیست همه', 'url' => ProductResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'بروزرسانی قیمت', 'url' => ProductPriceStockUpdate::getUrl(), 'icon' => 'fa-tags'],
                    ['label' => 'ورود لیست محصول', 'url' => ProductListImport::getUrl(), 'icon' => 'fa-file-excel'],
                    ['label' => 'افزودن محصول', 'url' => ProductResource::getUrl('create'), 'icon' => 'fa-plus'],
                    ['label' => 'دسته‌بندی‌ها', 'url' => CategoryResource::getUrl('index'), 'icon' => 'fa-tags'],
                    ['label' => 'ویژگی‌ها', 'url' => AttributeResource::getUrl('index'), 'icon' => 'fa-list-ul'],
                    ['label' => 'خانواده محصول', 'url' => ProductFamilyResource::getUrl('index'), 'icon' => 'fa-layer-group'],
                    ['label' => 'کارخانه', 'url' => ProductPlantResource::getUrl('index'), 'icon' => 'fa-industry'],
                    ['label' => 'قالب محصول', 'url' => ProductTemplateResource::getUrl('index'), 'icon' => 'fa-shapes'],
                ]],
            ],
        ],
        'payments' => [
            'label' => 'پرداخت‌ها',
            'icon' => 'fa-credit-card',
            'routes' => ['filament.admin.resources.payments.*'],
            'menus' => [
                ['label' => 'پرداخت‌ها', 'icon' => 'fa-credit-card', 'items' => [
                    ['label' => 'لیست همه', 'url' => PaymentResource::getUrl('index'), 'icon' => 'fa-list'],
                ]],
            ],
        ],
        'brands' => [
            'label' => 'برندها',
            'icon' => 'fa-certificate',
            'routes' => ['filament.admin.resources.brands.*'],
            'menus' => [
                ['label' => 'برندها', 'icon' => 'fa-certificate', 'items' => [
                    ['label' => 'لیست همه', 'url' => BrandResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'ایجاد برند', 'url' => BrandResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
            ],
        ],
        'credit_plans' => [
            'label' => 'طرح‌ها',
            'icon' => 'fa-hand-holding-usd',
            'routes' => ['filament.admin.resources.credit-plans.*'],
            'menus' => [
                ['label' => 'طرح‌ها', 'icon' => 'fa-hand-holding-usd', 'items' => [
                    ['label' => 'لیست همه', 'url' => CreditPlanResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'ایجاد طرح', 'url' => CreditPlanResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
            ],
        ],
        'partners' => [
            'label' => 'همکاران ما',
            'icon' => 'fa-handshake',
            'routes' => ['filament.admin.resources.partners.*'],
            'menus' => [
                ['label' => 'همکاران ما', 'icon' => 'fa-handshake', 'items' => [
                    ['label' => 'لیست همه', 'url' => PartnerResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'افزودن همکار', 'url' => PartnerResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
            ],
        ],
        'discounts' => [
            'label' => 'تخفیف‌ها',
            'icon' => 'fa-percent',
            'routes' => ['filament.admin.resources.coupons.*'],
            'menus' => [
                ['label' => 'تخفیف‌ها', 'icon' => 'fa-percent', 'items' => [
                    ['label' => 'لیست همه', 'url' => CouponResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'ایجاد تخفیف', 'url' => CouponResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
            ],
        ],
        'orders' => [
            'label' => 'سفارش‌ها',
            'icon' => 'fa-shopping-bag',
            'routes' => ['filament.admin.resources.orders.*'],
            'menus' => [
                ['label' => 'سفارش‌ها', 'icon' => 'fa-shopping-bag', 'items' => [
                    ['label' => 'لیست همه', 'url' => OrderResource::getUrl('index'), 'icon' => 'fa-list'],
                ]],
            ],
        ],
        'requests' => [
            'label' => 'درخواست‌ها',
            'icon' => 'fa-inbox',
            'routes' => ['filament.admin.resources.reservation-requests.*'],
            'menus' => [
                ['label' => 'درخواست‌ها', 'icon' => 'fa-inbox', 'items' => [
                    ['label' => 'در انتظار بررسی', 'url' => ReservationRequestResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'pending']]]), 'icon' => 'fa-hourglass-half', 'badge' => $pendingRequestsCount],
                    ['label' => 'همه درخواست‌ها', 'url' => ReservationRequestResource::getUrl('index'), 'icon' => 'fa-list'],
                ]],
            ],
        ],
        'content' => [
            'label' => 'محتوا',
            'icon' => 'fa-newspaper',
            'routes' => [
                'filament.admin.resources.posts.*',
                'filament.admin.resources.pages.*',
                'filament.admin.resources.product-reviews.*',
                'filament.admin.resources.product-questions.*',
                'filament.admin.resources.home-sliders.*',
                'filament.admin.resources.menu-items.*',
            ],
            'menus' => [
                ['label' => 'بلاگ', 'icon' => 'fa-file-alt', 'items' => [
                    ['label' => 'لیست همه', 'url' => PostResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'ایجاد مطلب', 'url' => PostResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
                ['label' => 'صفحات', 'icon' => 'fa-file-lines', 'items' => [
                    ['label' => 'لیست همه', 'url' => PageResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'صفحه جدید', 'url' => PageResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
                ['label' => 'نظرات', 'icon' => 'fa-comments', 'items' => [
                    ['label' => 'لیست همه', 'url' => ProductReviewResource::getUrl('index'), 'icon' => 'fa-list'],
                ]],
                ['label' => 'پرسش و پاسخ', 'icon' => 'fa-circle-question', 'items' => [
                    ['label' => 'لیست همه', 'url' => ProductQuestionResource::getUrl('index'), 'icon' => 'fa-list'],
                ]],
                ['label' => 'اسلایدر', 'icon' => 'fa-images', 'items' => [
                    ['label' => 'لیست همه', 'url' => HomeSliderResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'افزودن اسلاید', 'url' => HomeSliderResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
                ['label' => 'منوی سایت', 'icon' => 'fa-bars', 'items' => [
                    ['label' => 'آیتم‌های منو', 'url' => MenuItemResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'افزودن آیتم', 'url' => MenuItemResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
            ],
        ],
        'shipping' => [
            'label' => 'ارسال',
            'icon' => 'fa-truck',
            'routes' => [
                'filament.admin.resources.shipping-methods.*',
                'filament.admin.resources.freight-carriers.*',
            ],
            'menus' => [
                ['label' => 'روش‌های ارسال', 'icon' => 'fa-truck', 'items' => [
                    ['label' => 'لیست همه', 'url' => ShippingMethodResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'افزودن روش', 'url' => ShippingMethodResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
                ['label' => 'باربری نمایندگی', 'icon' => 'fa-dolly', 'items' => [
                    ['label' => 'لیست باربری‌ها', 'url' => FreightCarrierResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'افزودن باربری', 'url' => FreightCarrierResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
            ],
        ],
        'gateways' => [
            'label' => 'درگاه‌ها',
            'icon' => 'fa-plug',
            'routes' => [
                'filament.admin.pages.zarinpal',
                'filament.admin.pages.tara',
                'filament.admin.pages.bajet',
                'filament.admin.pages.digipay',
                'filament.admin.pages.parsian',
                'filament.admin.pages.sadad',
                'filament.admin.pages.cod',
            ],
            'menus' => [
                ['label' => 'درگاه‌های پرداخت', 'icon' => 'fa-plug', 'items' => [
                    ['label' => 'پرداخت در محل', 'url' => ManageCodPayment::getUrl(), 'icon' => 'fa-truck'],
                    ['label' => 'زرین‌پال', 'url' => ManageZarinpal::getUrl(), 'icon' => 'fa-university'],
                    ['label' => 'پارسیان', 'url' => ManageParsian::getUrl(), 'icon' => 'fa-building-columns'],
                    ['label' => 'سداد', 'url' => ManageSadad::getUrl(), 'icon' => 'fa-building-columns'],
                    ['label' => 'تارا', 'url' => ManageTara::getUrl(), 'icon' => 'fa-wallet'],
                    ['label' => 'باجت‌پی', 'url' => ManageBajetPay::getUrl(), 'icon' => 'fa-credit-card'],
                    ['label' => 'دیجی‌پی', 'url' => ManageDigiPay::getUrl(), 'icon' => 'fa-credit-card'],
                ]],
            ],
        ],
        'media' => [
            'label' => 'مدیریت فایل',
            'icon' => 'fa-folder-open',
            'routes' => [
                'filament.admin.resources.media-files.*',
                'filament.admin.pages.media-presets',
                'filament.admin.pages.homepage-images',
            ],
            'menus' => [
                ['label' => 'کتابخانه رسانه', 'icon' => 'fa-photo-film', 'items' => [
                    ['label' => 'همه فایل‌ها', 'url' => MediaFileResource::getUrl('index'), 'icon' => 'fa-images'],
                ]],
                ['label' => 'تنظیمات تصویر', 'icon' => 'fa-crop', 'items' => [
                    ['label' => 'سایز آپلود', 'url' => ManageMediaPresets::getUrl(), 'icon' => 'fa-sliders'],
                    ['label' => 'تامبنیل بخش‌های فروشگاه', 'url' => ManageHomepageImages::getUrl(), 'icon' => 'fa-image'],
                ]],
            ],
        ],
        'backup' => [
            'label' => 'بک‌آپ',
            'icon' => 'fa-database',
            'routes' => [
                'filament.admin.pages.database-backups',
                'filament.admin.pages.payment-logs',
                'filament.admin.pages.site-error-logs',
            ],
            'menus' => [
                ['label' => 'بک‌آپ', 'icon' => 'fa-database', 'items' => [
                    ['label' => 'دیتابیس و فایل', 'url' => ViewDatabaseBackups::getUrl(), 'icon' => 'fa-download'],
                ]],
                ['label' => 'لاگ', 'icon' => 'fa-file-lines', 'items' => [
                    ['label' => 'لاگ پرداخت', 'url' => ViewPaymentLogs::getUrl(), 'icon' => 'fa-credit-card'],
                    ['label' => 'لاگ سیستم', 'url' => ViewSiteErrorLogs::getUrl(), 'icon' => 'fa-bug'],
                ]],
            ],
        ],
        'settings' => [
            'label' => 'تنظیمات',
            'icon' => 'fa-cog',
            'routes' => [
                'filament.admin.pages.manage-general-settings',
                'filament.admin.pages.trust-badges',
                'filament.admin.pages.footer-settings',
                'filament.admin.pages.site-header-settings',
                'filament.admin.pages.search-popup-settings',
                'filament.admin.pages.homepage-settings',
                'filament.admin.pages.kavenegar',
                'filament.admin.pages.sms-ir',
                'filament.admin.pages.transactional-sms',
            ],
            'menus' => [
                ['label' => 'تنظیمات عمومی', 'icon' => 'fa-globe', 'items' => [
                    ['label' => 'مدیریت سایت', 'url' => ManageGeneralSettings::getUrl(), 'icon' => 'fa-cog'],
                    ['label' => 'فوتر سایت', 'url' => ManageFooterSettings::getUrl(), 'icon' => 'fa-window-maximize'],
                    ['label' => 'هدر سایت', 'url' => ManageSiteHeaderSettings::getUrl(), 'icon' => 'fa-code'],
                    ['label' => 'پاپ‌آپ جستجو', 'url' => ManageSearchPopupSettings::getUrl(), 'icon' => 'fa-magnifying-glass'],
                    ['label' => 'صفحه اصلی فروشگاه', 'url' => ManageHomepageSettings::getUrl(), 'icon' => 'fa-house'],
                    ['label' => 'مجوزها', 'url' => ManageTrustBadges::getUrl(), 'icon' => 'fa-certificate'],
                ]],
                ['label' => 'پیامک', 'icon' => 'fa-sms', 'items' => [
                    ['label' => 'sms.ir', 'url' => ManageSmsIr::getUrl(), 'icon' => 'fa-comment-dots'],
                    ['label' => 'متن پیامک تراکنشی', 'url' => ManageTransactionalSms::getUrl(), 'icon' => 'fa-sms'],
                    ['label' => 'کاوه‌نگار', 'url' => ManageIntegrations::getUrl(), 'icon' => 'fa-comment-sms'],
                ]],
            ],
        ],
    ];

    if ($salesManagerOnly) {
        $panels = array_intersect_key($panels, array_flip(['products', 'brands', 'orders', 'requests', 'payments', 'users', 'media']));
        $panels['users']['menus'] = [
            ['label' => 'کاربران', 'icon' => 'fa-users', 'items' => [
                ['label' => 'نمایندگان من', 'url' => UserResource::getUrl('index', ['activeTab' => 'representatives']), 'icon' => 'fa-user-tie'],
                ['label' => 'افزودن نماینده', 'url' => UserResource::getUrl('create'), 'icon' => 'fa-user-plus'],
                ['label' => 'مشتریان نمایندگان من', 'url' => UserResource::getUrl('index', ['activeTab' => 'rep_customers']), 'icon' => 'fa-user'],
            ]],
        ];
        $panels['media']['menus'] = [
            ['label' => 'کتابخانه رسانه', 'icon' => 'fa-photo-film', 'items' => [
                ['label' => 'همه فایل‌ها', 'url' => MediaFileResource::getUrl('index'), 'icon' => 'fa-images'],
            ]],
        ];
    }

    $activePanel = 'users';
    $sidebarPanel = 'users';
    if ($isDashboard) {
        $activePanel = 'dashboard';
        $sidebarPanel = 'users';
    } else {
        foreach ($panels as $panelId => $panel) {
            if (collect($panel['routes'])->contains(fn ($p) => request()->routeIs($p))) {
                $activePanel = $panelId;
                $sidebarPanel = $panelId;
                break;
            }
        }
    }

    $activePanelLabel = $isDashboard ? 'داشبورد' : ($panels[$sidebarPanel]['label'] ?? 'منو');

    $navIcons = [
        ['id' => 'dashboard', 'icon' => 'fa-chart-pie', 'tooltip' => 'داشبورد', 'href' => $dashboardUrl, 'isLink' => true],
        ['id' => 'users', 'icon' => 'fa-users', 'tooltip' => 'کاربران', 'panel' => 'users'],
        ['id' => 'sales_regions', 'icon' => 'fa-map-location-dot', 'tooltip' => 'مناطق فروش', 'panel' => 'sales_regions'],
        ['id' => 'products', 'icon' => 'fa-box-open', 'tooltip' => 'محصولات', 'panel' => 'products'],
        ['id' => 'payments', 'icon' => 'fa-credit-card', 'tooltip' => 'پرداخت‌ها', 'panel' => 'payments'],
        ['id' => 'brands', 'icon' => 'fa-certificate', 'tooltip' => 'برندها', 'panel' => 'brands'],
        ['id' => 'credit_plans', 'icon' => 'fa-hand-holding-usd', 'tooltip' => 'طرح‌ها', 'panel' => 'credit_plans'],
        ['id' => 'partners', 'icon' => 'fa-handshake', 'tooltip' => 'همکاران ما', 'panel' => 'partners'],
        ['id' => 'discounts', 'icon' => 'fa-percent', 'tooltip' => 'تخفیف‌ها', 'panel' => 'discounts'],
        ['id' => 'orders', 'icon' => 'fa-shopping-bag', 'tooltip' => 'سفارش‌ها', 'panel' => 'orders'],
        ['id' => 'requests', 'icon' => 'fa-inbox', 'tooltip' => 'درخواست‌ها', 'panel' => 'requests', 'badge' => $pendingRequestsCount],
        ['id' => 'content', 'icon' => 'fa-newspaper', 'tooltip' => 'محتوا', 'panel' => 'content'],
        ['id' => 'shipping', 'icon' => 'fa-truck', 'tooltip' => 'ارسال', 'panel' => 'shipping'],
        ['id' => 'gateways', 'icon' => 'fa-plug', 'tooltip' => 'درگاه‌ها', 'panel' => 'gateways'],
        ['id' => 'media', 'icon' => 'fa-folder-open', 'tooltip' => 'مدیریت فایل', 'panel' => 'media'],
        ['id' => 'backup', 'icon' => 'fa-database', 'tooltip' => 'بک‌آپ', 'panel' => 'backup'],
        ['id' => 'settings', 'icon' => 'fa-cog', 'tooltip' => 'تنظیمات', 'panel' => 'settings'],
    ];

    if ($salesManagerOnly) {
        $navIcons = array_values(array_filter(
            $navIcons,
            fn (array $icon): bool => ($icon['isLink'] ?? false)
                || in_array($icon['panel'] ?? '', ['products', 'brands', 'orders', 'requests', 'payments', 'users', 'media'], true)
        ));
    }

    $isMenuItemActive = function (string $url): bool {
        $current = url()->current();
        $target = rtrim($url, '/');

        return $current === $target || str_starts_with($current, $target.'/');
    };

    $initial = mb_substr($user?->name ?? 'ا', 0, 1);
@endphp

<div class="sidebar-secondary">
    @foreach ($navIcons as $navIcon)
        @if ($navIcon['isLink'] ?? false)
            <a href="{{ $navIcon['href'] }}"
               class="sidebar-icon-item sidebar-icon-link {{ $activePanel === 'dashboard' ? 'active' : '' }}"
               data-tooltip="{{ $navIcon['tooltip'] }}"
               aria-label="{{ $navIcon['tooltip'] }}">
                <i class="fas {{ $navIcon['icon'] }}"></i>
                <span class="sidebar-icon-label">{{ $navIcon['tooltip'] }}</span>
            </a>
        @else
            <div class="sidebar-icon-item {{ $activePanel === $navIcon['id'] ? 'active' : '' }}"
                 data-panel="{{ $navIcon['panel'] }}"
                 data-tooltip="{{ $navIcon['tooltip'] }}"
                 role="button"
                 tabindex="0"
                 aria-label="{{ $navIcon['tooltip'] }}">
                <i class="fas {{ $navIcon['icon'] }}"></i>
                <span class="sidebar-icon-label">{{ $navIcon['tooltip'] }}</span>
                @if (($navIcon['badge'] ?? 0) > 0)
                    <span class="sidebar-icon-badge">{{ $navIcon['badge'] }}</span>
                @endif
            </div>
        @endif
        @if ($loop->first)
            <div class="sidebar-divider"></div>
        @endif
    @endforeach

    <a href="{{ $profileUrl }}"
       class="sidebar-user-icon d-flex align-items-center justify-content-center text-white fw-bold text-decoration-none"
       style="background: linear-gradient(135deg, #7239ea 0%, #9d5cff 100%); font-size: 14px;"
       data-tooltip="پروفایل: {{ $user?->name ?? 'کاربر' }}"
       aria-label="پروفایل کاربر">{{ $initial }}</a>
</div>

<div class="sidebar-primary" id="sidebarPrimary">
    <div class="sidebar-header">
        <p class="sidebar-title">منوی اصلی</p>
        <p class="mb-0 fw-bold text-dark" id="sidebarPanelTitle">{{ $activePanelLabel }}</p>
    </div>

    <div class="sidebar-menu" id="sidebarMenu">
        @foreach ($panels as $panelId => $panel)
            <div class="menu-content {{ $sidebarPanel === $panelId ? 'active' : '' }}" id="{{ $panelId }}Content">
                @foreach ($panel['menus'] as $menuIndex => $menu)
                    @php
                        $submenuId = $panelId.'Submenu'.$menuIndex;
                        $hasActiveChild = collect($menu['items'])->contains(fn ($item) => $isMenuItemActive($item['url']));
                    @endphp
                    <button type="button"
                            class="menu-item {{ $hasActiveChild ? 'active' : '' }}"
                            data-submenu="{{ $submenuId }}"
                            aria-expanded="{{ $hasActiveChild ? 'true' : 'false' }}">
                        <div class="d-flex align-items-center gap-3">
                            <span class="menu-icon"><i class="fas {{ $menu['icon'] }}"></i></span>
                            <span>{{ $menu['label'] }}</span>
                        </div>
                        <i class="fas fa-chevron-down menu-arrow" style="transform: rotate({{ $hasActiveChild ? '180' : '0' }}deg);"></i>
                    </button>
                    <div class="submenu {{ $hasActiveChild ? 'expanded' : '' }}" id="{{ $submenuId }}">
                        @foreach ($menu['items'] as $item)
                            <a href="{{ $item['url'] }}"
                               class="submenu-item {{ $isMenuItemActive($item['url']) ? 'active' : '' }}">
                                <i class="fas {{ $item['icon'] }} ms-2 submenu-item-icon"></i>
                                {{ $item['label'] }}
                                @if (($item['badge'] ?? 0) > 0)
                                    <span class="submenu-item-badge">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>

<div class="toggle-sidebar-btn" onclick="toggleSidebar()">
    <i class="fas fa-chevron-left" id="toggleIcon"></i>
</div>

<script>
    window.__adminPanelTitles = @json(collect($panels)->mapWithKeys(fn ($p, $id) => [$id => $p['label']]));
</script>
