<?php

namespace App\Providers;

use App\Contracts\SmsSender;
use App\Filament\Support\CrudSuccessNotification;
use App\Http\Controllers\LivewireFileUploadController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSlider;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Models\UserAddress;
use App\Observers\BrandObserver;
use App\Observers\CategoryObserver;
use App\Observers\HomeSliderObserver;
use App\Observers\MenuItemObserver;
use App\Observers\PostObserver;
use App\Observers\ProductImageObserver;
use App\Observers\ProductObserver;
use App\Observers\ShippingMethodObserver;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\UserAddressPolicy;
use App\Services\Cache\ShopCacheService;
use App\Services\Settings\SettingsService;
use App\Services\Sms\KavenegarSmsSender;
use App\Services\Sms\LogSmsSender;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Features\SupportFileUploads\FileUploadController;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FileUploadController::class, LivewireFileUploadController::class);

        $this->app->bind(SmsSender::class, function () {
            $kavenegar = app(SettingsService::class)->kavenegar();

            if (($kavenegar['enabled'] ?? false) && ! empty($kavenegar['api_key'])) {
                return new KavenegarSmsSender;
            }

            return match (config('sms.driver')) {
                'kavenegar' => new KavenegarSmsSender,
                default => new LogSmsSender,
            };
        });
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceHttps();
        }

        if (! $this->app->runningInConsole() && request()->hasHeader('Host')) {
            URL::forceRootUrl(rtrim(request()->root(), '/'));
        }

        Storage::disk('public')->makeDirectory('livewire-tmp');

        Product::observe(ProductObserver::class);
        ProductImage::observe(ProductImageObserver::class);
        Category::observe(CategoryObserver::class);
        Brand::observe(BrandObserver::class);
        MenuItem::observe(MenuItemObserver::class);
        Post::observe(PostObserver::class);
        HomeSlider::observe(HomeSliderObserver::class);
        ShippingMethod::observe(ShippingMethodObserver::class);

        View::composer([
            'shop.partials.header',
            'shop.partials.overlays',
            'shop.partials.mobile-menu',
        ], function ($view) {
            $view->with(app(ShopCacheService::class)->headerPayload());
        });

        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(UserAddress::class, UserAddressPolicy::class);

        Route::bind('author', function (string $value) {
            return User::query()
                ->where('slug', $value)
                ->where('is_author', true)
                ->firstOrFail();
        });

        \Filament\Tables\Actions\CreateAction::configureUsing(function ($action): void {
            $action
                ->label('افزودن')
                ->successNotification(CrudSuccessNotification::created());
        });
        \Filament\Tables\Actions\EditAction::configureUsing(function ($action): void {
            $action
                ->label('ویرایش')
                ->successNotification(CrudSuccessNotification::saved());
        });
        \Filament\Tables\Actions\DeleteAction::configureUsing(function ($action): void {
            $action
                ->label('حذف')
                ->successNotification(CrudSuccessNotification::deleted());
        });
        ViewAction::configureUsing(fn ($action) => $action->label('مشاهده'));
        DeleteBulkAction::configureUsing(function ($action): void {
            $action
                ->label('حذف انتخاب‌شده‌ها')
                ->successNotification(CrudSuccessNotification::deleted());
        });
        CreateAction::configureUsing(function ($action): void {
            $action
                ->label('افزودن')
                ->successNotification(CrudSuccessNotification::created());
        });
        EditAction::configureUsing(function ($action): void {
            $action
                ->label('ویرایش')
                ->successNotification(CrudSuccessNotification::saved());
        });
        DeleteAction::configureUsing(function ($action): void {
            $action
                ->label('حذف')
                ->successNotification(CrudSuccessNotification::deleted());
        });

        if ($this->app->runningInConsole() || request()->is('admin', 'admin/*')) {
            FileUpload::configureUsing(function (FileUpload $component): void {
                $component
                    ->fetchFileInformation(false)
                    ->maxSize(51200)
                    ->imagePreviewHeight('150')
                    ->helperText('حداکثر ۵۰ مگابایت. اگر خطا دیدید، نام فایل را کوتاه کنید و دوباره انتخاب کنید.')
                    ->rule(static function () {
                        return static function (string $attribute, mixed $value, \Closure $fail): void {
                            foreach (Arr::wrap($value) as $file) {
                                if ($file instanceof TemporaryUploadedFile && ! $file->isValid()) {
                                    $fail('فایل آپلود نشد. نام فایل را کوتاه کنید، دوباره انتخاب کنید و تا پایان آپلود صبر کنید.');

                                    return;
                                }
                            }
                        };
                    });
            });
        }
    }
}
