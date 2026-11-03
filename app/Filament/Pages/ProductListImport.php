<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Services\Product\ProductExcelImportParser;
use App\Services\Product\ProductListImportService;
use App\Support\AdminAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ProductListImport extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'ورود لیست محصول';

    protected static ?string $slug = 'product-list-import';

    protected static ?string $title = 'ورود لیست محصول';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.product-list-import';

    public ?int $importCategoryId = null;

    public $importFile = null;

    public bool $updateExistingBySku = true;

    public static function canAccess(): bool
    {
        return AdminAccess::canManageProductsInAdmin();
    }

    /** @return array<int, string> */
    public function getCategoryOptionsProperty(): array
    {
        return Category::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function runImport(
        ProductExcelImportParser $parser,
        ProductListImportService $importer,
    ): void {
        $this->validate([
            'importCategoryId' => ['required', 'integer', 'exists:categories,id'],
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ], [], [
            'importCategoryId' => 'دسته‌بندی',
            'importFile' => 'فایل اکسل',
        ]);

        /** @var TemporaryUploadedFile $upload */
        $upload = $this->importFile;
        $path = $upload->getRealPath();

        if (! is_string($path) || $path === '') {
            Notification::make()->title('فایل نامعتبر')->danger()->send();

            return;
        }

        $parsed = $parser->parseFile($path);
        $rows = $parsed['rows'];
        $parseErrors = $parsed['errors'];

        if ($rows === [] && $parseErrors !== []) {
            Notification::make()
                ->title('خواندن فایل ناموفق')
                ->body(implode("\n", array_slice($parseErrors, 0, 5)))
                ->danger()
                ->send();

            return;
        }

        if ($rows === []) {
            Notification::make()
                ->title('ردیفی برای import یافت نشد')
                ->warning()
                ->send();

            return;
        }

        $result = $importer->importRows(
            $rows,
            (int) $this->importCategoryId,
            auth()->id(),
            $this->updateExistingBySku,
        );

        $allErrors = array_merge($parseErrors, $result['errors']);
        $body = sprintf(
            '%d محصول جدید، %d به‌روزرسانی.',
            $result['created'],
            $result['updated'],
        );

        if ($allErrors !== []) {
            $body .= "\n".implode("\n", array_slice($allErrors, 0, 8));
            if (count($allErrors) > 8) {
                $body .= "\n…";
            }
        }

        Notification::make()
            ->title('ایمپورت انجام شد')
            ->body($body)
            ->success()
            ->send();

        $this->reset('importFile');
    }
}
