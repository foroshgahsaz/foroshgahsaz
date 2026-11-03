<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Product;
use App\Services\Product\ProductPriceStockUpdateService;
use App\Support\AdminAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

class ProductPriceStockUpdate extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'بروزرسانی قیمت';

    protected static ?string $slug = 'product-price-stock-update';

    protected static ?string $title = 'بروزرسانی قیمت و موجودی';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.product-price-stock-update';

    protected string $paginationTheme = 'tailwind';

    public ?int $filterCategoryId = null;

    /** @var ''|'all'|'in_stock'|'out_of_stock' */
    public string $filterStock = 'all';

    /** @var array<int, int|string> */
    public array $rowPrices = [];

    /** @var array<int, int|string> */
    public array $rowStocks = [];

    public string $bulkPricePercent = '';

    public string $bulkStockDelta = '';

    public static function canAccess(): bool
    {
        return AdminAccess::canManageProductsInAdmin();
    }

    public function mount(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCategoryId(): void
    {
        $this->resetPage();
        $this->resetRowInputs();
    }

    public function updatedFilterStock(): void
    {
        $this->resetPage();
        $this->resetRowInputs();
    }

    /** @return LengthAwarePaginator<int, Product> */
    public function getProductsProperty(): LengthAwarePaginator
    {
        return $this->filteredQuery()
            ->paginate(25);
    }

    /** @return array<int, string> */
    public function getCategoryOptionsProperty(): array
    {
        return Category::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function saveProduct(int $productId, ProductPriceStockUpdateService $service): void
    {
        $this->validate([
            "rowPrices.{$productId}" => ['required', 'integer', 'min:0'],
            "rowStocks.{$productId}" => ['required', 'integer', 'min:0', 'max:999999'],
        ], [], [
            "rowPrices.{$productId}" => 'قیمت',
            "rowStocks.{$productId}" => 'موجودی',
        ]);

        $product = Product::query()->whereKey($productId)->firstOrFail();

        $service->updateRow(
            $product,
            (int) $this->rowPrices[$productId],
            (int) $this->rowStocks[$productId],
            auth()->id(),
        );

        Notification::make()
            ->title('ذخیره شد')
            ->body('قیمت و موجودی «'.$product->name.'» به‌روزرسانی شد.')
            ->success()
            ->send();
    }

    public function toggleProductActive(int $productId, ProductPriceStockUpdateService $service): void
    {
        $product = Product::query()->whereKey($productId)->firstOrFail();

        $updated = $service->toggleActive($product, auth()->id());

        Notification::make()
            ->title($updated->is_active ? 'محصول فعال شد' : 'محصول غیرفعال شد')
            ->body('«'.$updated->name.'»')
            ->success()
            ->send();
    }

    public function applyBulkPricePercent(ProductPriceStockUpdateService $service): void
    {
        if (trim($this->bulkPricePercent) === '') {
            Notification::make()
                ->title('درصد قیمت خالی است')
                ->body('درصد تغییر قیمت پایه را وارد کنید.')
                ->warning()
                ->send();

            return;
        }

        $this->validate([
            'bulkPricePercent' => ['required', 'numeric', 'not_in:0', 'between:-99,1000'],
        ], [], [
            'bulkPricePercent' => 'درصد تغییر قیمت',
        ]);

        $count = (clone $this->filteredQuery())->count();

        if ($count === 0) {
            Notification::make()->title('محصولی یافت نشد')->warning()->send();

            return;
        }

        $updated = $service->bulkAdjustPriceOnQuery(
            $this->filteredQuery(),
            (float) $this->bulkPricePercent,
            auth()->id(),
        );

        $this->bulkPricePercent = '';
        $this->resetRowInputs();
        $this->resetPage();

        Notification::make()
            ->title('درصد قیمت اعمال شد')
            ->body(sprintf('قیمت %d محصول از %d محصول فیلترشده به‌روزرسانی شد.', $updated, $count))
            ->success()
            ->send();
    }

    public function applyBulkStockDelta(ProductPriceStockUpdateService $service): void
    {
        if (trim($this->bulkStockDelta) === '') {
            Notification::make()
                ->title('تغییر موجودی خالی است')
                ->body('مقدار تغییر موجودی را وارد کنید.')
                ->warning()
                ->send();

            return;
        }

        $this->validate([
            'bulkStockDelta' => ['required', 'integer', 'not_in:0', 'between:-999999,999999'],
        ], [], [
            'bulkStockDelta' => 'تغییر موجودی',
        ]);

        $count = (clone $this->filteredQuery())->count();

        if ($count === 0) {
            Notification::make()->title('محصولی یافت نشد')->warning()->send();

            return;
        }

        $updated = $service->bulkAdjustStockOnQuery(
            $this->filteredQuery(),
            (int) $this->bulkStockDelta,
            auth()->id(),
        );

        $this->bulkStockDelta = '';
        $this->resetRowInputs();
        $this->resetPage();

        Notification::make()
            ->title('تغییر موجودی اعمال شد')
            ->body(sprintf('موجودی %d محصول از %d محصول فیلترشده به‌روزرسانی شد.', $updated, $count))
            ->success()
            ->send();
    }

    protected function filteredQuery(): Builder
    {
        $query = Product::query()
            ->with(['category:id,name', 'lastEditedBy:id,name'])
            ->orderByDesc('updated_at');

        if ($this->filterCategoryId) {
            $query->where('category_id', $this->filterCategoryId);
        }

        match ($this->filterStock) {
            'in_stock' => $query->where('stock', '>', 0),
            'out_of_stock' => $query->where('stock', '<=', 0),
            default => null,
        };

        return $query;
    }

    protected function resetRowInputs(): void
    {
        $this->rowPrices = [];
        $this->rowStocks = [];
    }

    public function rendering(): void
    {
        foreach ($this->products as $product) {
            if (! array_key_exists($product->id, $this->rowPrices)) {
                $this->rowPrices[$product->id] = (int) $product->price;
            }
            if (! array_key_exists($product->id, $this->rowStocks)) {
                $this->rowStocks[$product->id] = (int) $product->stock;
            }
        }
    }
}
