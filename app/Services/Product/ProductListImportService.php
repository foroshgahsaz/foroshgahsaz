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

        foreach ($rows as $index => $row) {
            $result = $this->importRow($row, $index + 2, $editorUserId, $updateExisting);

            if ($result['status'] === 'created') {
                $created++;
            } elseif ($result['status'] === 'updated') {
                $updated++;
            } elseif ($result['status'] === 'error' && isset($result['message'])) {
                $errors[] = $result['message'];
            }
        }

        return compact('created', 'updated', 'errors');
    }

    /**
     * @param  array{sku: string, name: string, stock: int, price: int}  $row
     * @return array{status: 'created'|'updated'|'skipped'|'error', name?: string, message?: string}
     */
    public function importRow(array $row, int $lineNumber, ?int $editorUserId, bool $updateExisting): array
    {
        try {
            return DB::transaction(function () use ($row, $lineNumber, $editorUserId, $updateExisting): array {
                $existing = Product::query()->where('sku', $row['sku'])->first();

                if ($existing !== null) {
                    if (! $updateExisting) {
                        return [
                            'status' => 'error',
                            'message' => "سطر {$lineNumber}: SKU «{$row['sku']}» از قبل وجود دارد.",
                        ];
                    }

                    $existing->update([
                        'name' => $row['name'],
                        'price' => $row['price'],
                        'stock' => $row['stock'],
                        'last_edited_by_user_id' => $editorUserId,
                    ]);

                    return ['status' => 'updated', 'name' => $row['name']];
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

                return ['status' => 'created', 'name' => $row['name']];
            });
        } catch (\Throwable $e) {
            report($e);

            return [
                'status' => 'error',
                'message' => "سطر {$lineNumber}: «{$row['name']}» — خطا در ذخیره.",
            ];
        }
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
