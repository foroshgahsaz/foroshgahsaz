<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Support\SlugFromTitle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ProductListImportService
{
    /**
     * @param  list<array{sku: string, name: string, stock: int, price: int}>  $rows
     * @return array{created: int, updated: int, errors: list<string>}
     */
    public function importRows(array $rows, ?int $editorUserId, bool $updateExisting = true): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $editorUserId, $updateExisting, &$created, &$updated, &$errors): void {
            foreach ($rows as $index => $row) {
                $line = $index + 2;

                $existing = Product::query()->where('sku', $row['sku'])->first();

                if ($existing !== null) {
                    if (! $updateExisting) {
                        $errors[] = "سطر {$line}: SKU «{$row['sku']}» از قبل وجود دارد.";

                        continue;
                    }

                    $existing->update([
                        'name' => $row['name'],
                        'price' => $row['price'],
                        'stock' => $row['stock'],
                        'last_edited_by_user_id' => $editorUserId,
                    ]);
                    $updated++;

                    continue;
                }

                $slug = $this->uniqueSlug(SlugFromTitle::fromProductName($row['name']), $row['sku']);

                Product::query()->create([
                    'category_id' => null,
                    'name' => $row['name'],
                    'slug' => $slug,
                    'sku' => $row['sku'],
                    'price' => $row['price'],
                    'stock' => $row['stock'],
                    'is_active' => false,
                    'is_featured' => false,
                    'last_edited_by_user_id' => $editorUserId,
                ]);
                $created++;
            }
        });

        return compact('created', 'updated', 'errors');
    }

    private function uniqueSlug(string $baseSlug, string $sku): string
    {
        $slug = $baseSlug !== '' ? $baseSlug : SlugFromTitle::fromProductName($sku);
        if ($slug === '') {
            $slug = 'product-'.Str::lower(Str::random(8));
        }

        if (! Product::query()->where('slug', $slug)->exists()) {
            return $slug;
        }

        $suffix = SlugFromTitle::fromProductName($sku);
        $candidate = $slug.'-'.($suffix !== '' ? $suffix : Str::lower(Str::random(6)));

        if (! Product::query()->where('slug', $candidate)->exists()) {
            return $candidate;
        }

        return $slug.'-'.Str::lower(Str::random(6));
    }
}
