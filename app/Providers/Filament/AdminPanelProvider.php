<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\CouponAnalyticsPage;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ManageBajetPay;
use App\Filament\Pages\ManageDigiPay;
use App\Filament\Pages\ManageParsian;
use App\Filament\Pages\ManageCodPayment;
use App\Filament\Pages\ManageSadad;
use App\Filament\Pages\ManageToopi;
use App\Filament\Pages\ManageTara;
use App\Filament\Pages\ManageZarinpal;
use App\Filament\Pages\ViewDatabaseBackups;
use App\Filament\Pages\ViewPaymentLogs;
use App\Filament\Pages\ViewSiteErrorLogs;
use App\Http\Controllers\Admin\DownloadApplicationLogFileController;
use App\Http\Controllers\Admin\DownloadDatabaseBackupController;
use App\Http\Controllers\Admin\DownloadPaymentLogFileController;
use App\Http\Controllers\Admin\ProductImportTemplateController;
use App\Http\Middleware\RestrictSalesManagerAdminAccess;
use App\Http\Middleware\SetPersianLocale;
use App\Services\Settings\SettingsService;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->topbar(false)
            ->brandName(fn () => app(SettingsService::class)->site()['name'])
            ->font('YekanBakh', provider: LocalFontProvider::class)
            ->favicon(asset('shop/images/categories/code.svg'))
            ->colors([
                'primary' => Color::hex('#7239ea'),
                'danger' => Color::Rose,
                'warning' => Color::Amber,
                'success' => Color::Green,
                'info' => Color::Sky,
            ])
            ->sidebarCollapsibleOnDesktop(false)
            ->navigationGroups([
                NavigationGroup::make('فروشگاه')->icon('heroicon-o-shopping-bag'),
                NavigationGroup::make('کاربران')->icon('heroicon-o-users'),
                NavigationGroup::make('محتوا')->icon('heroicon-o-document-text'),
                NavigationGroup::make('تنظیمات')->icon('heroicon-o-cog-6-tooth'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                ManageZarinpal::class,
                ManageTara::class,
                ManageBajetPay::class,
                ManageDigiPay::class,
                ManageParsian::class,
                ManageSadad::class,
                ManageToopi::class,
                ManageCodPayment::class,
                ViewPaymentLogs::class,
                ViewSiteErrorLogs::class,
                ViewDatabaseBackups::class,
                CouponAnalyticsPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.hooks.styles'))
            ->renderHook(PanelsRenderHook::BODY_END, fn () => view('filament.hooks.scripts'))
            ->middleware([
                SetPersianLocale::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                RestrictSalesManagerAdminAccess::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->routes(function (): void {
                Route::get('/payment-logs/download', DownloadPaymentLogFileController::class)
                    ->name('payment-logs.download');
                Route::get('/site-logs/download', DownloadApplicationLogFileController::class)
                    ->name('site-logs.download');
                Route::get('/database-backups/download', DownloadDatabaseBackupController::class)
                    ->name('database-backups.download');
                Route::get('/product-import/template', ProductImportTemplateController::class)
                    ->name('product-import.template');
            });
    }
}
