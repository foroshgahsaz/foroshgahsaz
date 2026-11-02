<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\Pages\EditRecord as BaseEditRecord;
use App\Filament\Resources\ProductResource;
use App\Filament\Support\ProductDeleteActions;
use App\Filament\Support\RecordsProductLastEditor;

class EditProduct extends BaseEditRecord
{
    use RecordsProductLastEditor;

    protected static string $resource = ProductResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $product = $this->getRecord();

        if (filled($product->slug)) {
            $this->syncAdminTopBarUtilityLink([
                'url' => route('products.show', $product),
                'label' => 'نمایش محصول',
            ]);
        }
    }

    public function hasCombinedRelationManagerTabsWithContentForm(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            ProductDeleteActions::pageDeleteAction(),
        ];
    }
}
