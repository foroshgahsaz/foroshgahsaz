<?php

namespace App\Services\Product;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProductPriceStockUpdateService
{
    public function updateRow(Product $product, int $price, int $stock, ?int $editorUserId): Product
    {
        $product->update([
            'price' => max(0, $price),
            'stock' => max(0, $stock),
            'last_edited_by_user_id' => $editorUserId,
        ]);

        return $product->fresh();
    }

    public function toggleActive(Product $product, ?int $editorUserId): Product
    {
        $product->update([
            'is_active' => ! $product->is_active,
            'last_edited_by_user_id' => $editorUserId,
        ]);

        return $product->fresh();
    }

    public function bulkAdjustPriceOnQuery(Builder $query, float $pricePercent, ?int $editorUserId): int
    {
        if ($pricePercent === 0.0) {
            return 0;
        }

        $updated = 0;

        DB::transaction(function () use ($query, $pricePercent, $editorUserId, &$updated): void {
            $factor = 1 + ($pricePercent / 100);

            $query->clone()
                ->select('id', 'price')
                ->orderBy('id')
                ->chunkById(200, function ($products) use ($factor, $editorUserId, &$updated): void {
                    foreach ($products as $product) {
                        /** @var Product $product */
                        Product::query()->whereKey($product->id)->update([
                            'price' => max(0, (int) round((int) $product->price * $factor)),
                            'last_edited_by_user_id' => $editorUserId,
                        ]);
                        $updated++;
                    }
                });
        });

        return $updated;
    }

    public function bulkAdjustStockOnQuery(Builder $query, int $stockDelta, ?int $editorUserId): int
    {
        if ($stockDelta === 0) {
            return 0;
        }

        $updated = 0;

        DB::transaction(function () use ($query, $stockDelta, $editorUserId, &$updated): void {
            $query->clone()
                ->select('id', 'stock')
                ->orderBy('id')
                ->chunkById(200, function ($products) use ($stockDelta, $editorUserId, &$updated): void {
                    foreach ($products as $product) {
                        /** @var Product $product */
                        Product::query()->whereKey($product->id)->update([
                            'stock' => max(0, (int) $product->stock + $stockDelta),
                            'last_edited_by_user_id' => $editorUserId,
                        ]);
                        $updated++;
                    }
                });
        });

        return $updated;
    }
}
