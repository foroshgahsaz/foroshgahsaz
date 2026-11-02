<?php

namespace App\Providers;

use App\Contracts\SmsSender;
use App\Filament\Support\CrudErrorNotification;
use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\FileUploadSanitizer;
use App\Filament\Support\MissingUploadPathCleaner;
use App\Http\Controllers\LivewireFileUploadController;
use App\Http\Responses\Filament\AdminLoginResponse;
use App\Models\Brand;
use App\Models\CreditPlan;
use App\Models\Partner;
use App\Models\Category;
use App\Models\HomeSlider;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\ProductFamilyHomepagePopupItem;
use App\Models\ProductImage;
use App\Models\ProductPlant;
use App\Models\ProductTemplate;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Models\UserAddress;
use App\Observers\BrandObserver;
use App\Observers\CreditPlanObserver;
use App\Observers\PartnerObserver;
use App\Observers\CategoryObserver;
use App\Observers\HomeSliderObserver;
use App\Observers\MediaUsageObserver;
use App\Observers\MenuItemObserver;
use App\Observers\PostObserver;
use App\Observers\ProductImageObserver;
use App\Observers\ProductObserver;
use App\Observers\RepresentativeCatalogObserver;
use App\Observers\ShippingMethodObserver;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\UserAddressPolicy;
use App\Services\Cache\ShopCacheService;
use App\Services\Media\ImageOptimizer;
use App\Services\Media\MediaRegistry;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Representative\SalesRegionService;
use App\Services\Settings\SearchPopupSettingsService;
use App\Services\Settings\SettingsService;
use App\Services\Sms\SmsSenderFactory;
use App\Support\MediaPath;
use App\Support\ShopMedia;
use App\Support\ShopStoragePaths;
use App\Support\StoragePermissionFixer;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\FileUploadController;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AppServiceProvider extends ServiceProvider
{
    private static bool $storageDirectoriesEnsured = false;

    public function register(): void
    {
        $this->app->bind(FileUploadController::class, LivewireFileUploadController::class);

        $this->app->bind(SmsSender::class, fn () => app(SmsSenderFactory::class)->make());

        $this->app->singleton(PaymentGatewayCatalog::class);
        $this->app->singleton(SettingsService::class);
        $this->app->scoped(SalesRegionService::class);

        $this->app->singleton(LoginResponse::class, AdminLoginResponse::class);
    }

    public function boot(): void
    {
        View::share('adminTopBarCreate', null);
        View::share('adminTopBarUtilityLink', null);

        $this->ensureRuntimeCacheAndSessionAreReachable();

        foreach (ShopStoragePaths::persistentDirectories() as $directory) {
            ShopStoragePaths::ensureDirectory($directory);
        }

        if (StoragePermissionFixer::runningAsRoot()) {
            StoragePermissionFixer::fix();
        }

        $forceHttps = $this->shouldForceHttps();

        if ($forceHttps) {
            URL::forceScheme('https');
            config(['session.secure' => true]);
        }

        if (! $this->app->runningInConsole() && request()->hasHeader('Host')) {
            $scheme = $forceHttps || request()->isSecure()
                ? 'https'
                : request()->getScheme();

            URL::forceRootUrl($scheme.'://'.request()->getHttpHost());
        }

        if (! self::$storageDirectoriesEnsured) {
            $productsPath = Storage::disk('public')->path('products');

            if (! is_dir($productsPath)) {
                Storage::disk('public')->makeDirectory('products');
            }

            $this->ensureWritableDirectory($productsPath);

            $tempDisk = config('livewire.temporary_file_upload.disk', 'livewire-tmp');
            $tempDirectory = config('livewire.temporary_file_upload.directory', 'livewire-tmp');
            $tempDirectoryPath = Storage::disk($tempDisk)->path($tempDirectory);

            if (! is_dir($tempDirectoryPath)) {
                Storage::disk($tempDisk)->makeDirectory($tempDirectory);
            }

            $this->ensureWritableDirectory($tempDirectoryPath);

            self::$storageDirectoriesEnsured = true;
        }

        Product::observe(ProductObserver::class);
        $representativeCatalogObserver = app(RepresentativeCatalogObserver::class);
        ProductFamily::observe($representativeCatalogObserver);
        ProductFamilyHomepagePopupItem::observe($representativeCatalogObserver);
        ProductPlant::observe($representativeCatalogObserver);
        ProductTemplate::observe($representativeCatalogObserver);
        ProductImage::observe(ProductImageObserver::class);
        Category::observe(CategoryObserver::class);
        Brand::observe(BrandObserver::class);
        CreditPlan::observe(CreditPlanObserver::class);
        Partner::observe(PartnerObserver::class);
        MenuItem::observe(MenuItemObserver::class);
        Post::observe(PostObserver::class);
        HomeSlider::observe(HomeSliderObserver::class);
        ShippingMethod::observe(ShippingMethodObserver::class);

        $mediaObserver = app(MediaUsageObserver::class);

        foreach (array_keys(config('media-library.models', [])) as $modelClass) {
            if (is_string($modelClass) && class_exists($modelClass)) {
                $modelClass::observe($mediaObserver);
            }
        }

        View::composer([
            'shop.partials.header',
            'shop.partials.overlays',
            'shop.partials.mobile-menu',
        ], function ($view) {
            $view->with(array_merge(
                app(ShopCacheService::class)->headerPayload(),
                ['searchPopup' => app(SearchPopupSettingsService::class)->all()],
            ));
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
                ->successNotification(CrudSuccessNotification::created())
                ->failureNotification(CrudErrorNotification::failed())
                ->beforeFormValidated(function ($action): void {
                    self::sanitizeMountedActionUploads($action);
                });
        });
        \Filament\Tables\Actions\EditAction::configureUsing(function ($action): void {
            $action
                ->label('ویرایش')
                ->successNotification(CrudSuccessNotification::saved())
                ->failureNotification(CrudErrorNotification::failed())
                ->mutateRecordDataUsing(function (array $data, \Filament\Tables\Actions\EditAction $action): array {
                    $record = $action->getRecord();

                    return $record instanceof Model
                        ? MissingUploadPathCleaner::clearFromFormData($data, $record)
                        : $data;
                })
                ->beforeFormValidated(function ($action): void {
                    self::sanitizeMountedActionUploads($action, $action->getRecord());
                });
        });
        \Filament\Tables\Actions\DeleteAction::configureUsing(function ($action): void {
            $action
                ->label('حذف')
                ->successNotification(CrudSuccessNotification::deleted())
                ->failureNotification(CrudErrorNotification::failed());
        });
        ViewAction::configureUsing(fn ($action) => $action->label('مشاهده'));
        DeleteBulkAction::configureUsing(function ($action): void {
            $action
                ->label('حذف انتخاب‌شده‌ها')
                ->successNotification(CrudSuccessNotification::deleted())
                ->failureNotification(CrudErrorNotification::failed());
        });
        CreateAction::configureUsing(function ($action): void {
            $action
                ->label('افزودن')
                ->successNotification(CrudSuccessNotification::created())
                ->failureNotification(CrudErrorNotification::failed())
                ->beforeFormValidated(function ($action): void {
                    self::sanitizeMountedActionUploads($action);
                });
        });
        EditAction::configureUsing(function ($action): void {
            $action
                ->label('ویرایش')
                ->successNotification(CrudSuccessNotification::saved())
                ->failureNotification(CrudErrorNotification::failed())
                ->mutateRecordDataUsing(function (array $data, EditAction $action): array {
                    $record = $action->getRecord();

                    return $record instanceof Model
                        ? MissingUploadPathCleaner::clearFromFormData($data, $record)
                        : $data;
                })
                ->beforeFormValidated(function ($action): void {
                    self::sanitizeMountedActionUploads($action, $action->getRecord());
                });
        });
        DeleteAction::configureUsing(function ($action): void {
            $action
                ->label('حذف')
                ->successNotification(CrudSuccessNotification::deleted())
                ->failureNotification(CrudErrorNotification::failed());
        });

        FileUpload::configureUsing(function (FileUpload $component): void {
            $component
                ->fetchFileInformation(false)
                ->maxSize(51200)
                ->imagePreviewHeight('150')
                ->helperText('تا پایان آپلود (نوار پیشرفت) صبر کنید، بعد ذخیره کنید. حداکثر ۵۰ مگابایت.')
                ->validationMessages([
                    'uploaded' => 'فایل آپلود نشد. دوباره انتخاب کنید و تا پایان آپلود صبر کنید.',
                ])
                ->getUploadedFileUsing(function (FileUpload $component, mixed $file, string|array|null $storedFileNames): ?array {
                    if ($file instanceof TemporaryUploadedFile) {
                        return null;
                    }

                    if (! is_string($file) || $file === '') {
                        return null;
                    }

                    $file = MediaPath::normalize($file) ?? ltrim(str_replace('\\', '/', $file), '/');
                    $storage = $component->getDisk();
                    $storedName = $component->isMultiple()
                        ? ($storedFileNames[$file] ?? null)
                        : $storedFileNames;
                    $name = is_string($storedName) ? $storedName : basename($file);

                    try {
                        $exists = $storage->exists($file);
                    } catch (UnableToCheckExistence|UnableToCheckFileExistence) {
                        $exists = false;
                    }

                    $url = $component->getVisibility() === 'private'
                        ? rescue(fn () => $storage->temporaryUrl($file, now()->addMinutes(5)), report: false)
                        : null;

                    $url ??= rescue(fn () => $storage->url($file), report: false) ?? ShopMedia::url($file);

                    if (! $exists) {
                        return null;
                    }

                    if (! $component->shouldFetchFileInformation()) {
                        return [
                            'name' => $name,
                            'size' => 0,
                            'type' => null,
                            'url' => $url,
                        ];
                    }

                    try {
                        return [
                            'name' => $name,
                            'size' => (int) $storage->size($file),
                            'type' => $storage->mimeType($file),
                            'url' => $url,
                        ];
                    } catch (\Throwable) {
                        return [
                            'name' => $name,
                            'size' => 0,
                            'type' => null,
                            'url' => $url,
                        ];
                    }
                })
                ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, FileUpload $component): ?string {
                    $disk = $component->getDiskName();
                    $directory = trim((string) $component->getDirectory(), '/');
                    $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin'));
                    $extension = preg_replace('/[^a-z0-9]+/', '', $extension) ?: 'bin';
                    $filename = Str::ulid().'.'.$extension;
                    $relativeDirectory = $directory !== '' ? $directory : 'uploads';

                    if (! $file->isValid()) {
                        throw ValidationException::withMessages([
                            $component->getName() => 'فایل موقت آپلود منقضی شده. دوباره انتخاب کنید و تا پایان آپلود صبر کنید.',
                        ]);
                    }

                    $path = $file->storeAs($relativeDirectory, $filename, ['disk' => $disk]);

                    if (! Storage::disk($disk)->exists($path)) {
                        throw ValidationException::withMessages([
                            $component->getName() => 'ذخیره فایل روی سرور انجام نشد. لطفاً دوباره آپلود کنید.',
                        ]);
                    }

                    $path = app(ImageOptimizer::class)->optimize($disk, $path, $relativeDirectory);

                    app(MediaRegistry::class)->registerFromPath(
                        $disk,
                        $path,
                        $file->getClientOriginalName(),
                    );

                    return $path;
                })
                ->deleteUploadedFileUsing(function (): void {
                    // Physical files are kept until shop:prune-unused-media removes orphans.
                });
        }, isImportant: true);

        FileUpload::configureUsing(function (FileUpload $component): void {
            $component->afterStateHydrated(function (FileUpload $component, $state): void {
                if (is_string($state) && filled($state)) {
                    $path = MediaPath::normalize($state) ?? $state;
                    $component->state($path !== '' ? [(string) Str::uuid() => $path] : []);

                    return;
                }

                if (blank($state)) {
                    $component->state([]);
                }
            });
        }, isImportant: true);
    }

    protected static function sanitizeMountedActionUploads(object $action, ?Model $record = null): void
    {
        $livewire = $action->getLivewire();

        if (! method_exists($livewire, 'getMountedTableActionForm')) {
            return;
        }

        $form = $livewire->getMountedTableActionForm(mountedAction: $action);

        if ($form === null) {
            return;
        }

        FileUploadSanitizer::sanitize($livewire, $form, $record);
    }

    protected function ensureRuntimeCacheAndSessionAreReachable(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $cacheStore = (string) config('cache.default');
        $sessionDriver = (string) config('session.driver');

        if ($cacheStore !== 'redis' && $sessionDriver !== 'redis') {
            return;
        }

        try {
            if ($cacheStore === 'redis') {
                Redis::connection(config('cache.stores.redis.connection', 'cache'))->ping();
            } elseif ($sessionDriver === 'redis') {
                Redis::connection(config('session.connection') ?: 'default')->ping();
            }
        } catch (\Throwable $e) {
            config([
                'cache.default' => 'database',
                'session.driver' => 'database',
            ]);

            // A per-request switch splits sessions between stores and surfaces as Livewire 419s.
            try {
                if (Cache::store('database')->add('runtime:redis-fallback-logged', 1, 300)) {
                    Log::warning('Redis unreachable; falling back to database cache/session.', [
                        'error' => $e->getMessage(),
                        'path' => request()->path(),
                    ]);
                }
            } catch (\Throwable) {
            }
        }
    }

    protected function shouldForceHttps(): bool
    {
        if ($this->app->environment('production')) {
            return true;
        }

        return str_starts_with((string) config('app.url'), 'https://');
    }

    protected function ensureWritableDirectory(string $path): void
    {
        if (! is_dir($path) || is_writable($path)) {
            return;
        }

        @chmod($path, 0775);

        if (! is_writable($path)) {
            @chmod($path, 0777);
        }
    }
}
