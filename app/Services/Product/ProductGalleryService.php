<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Media\MediaRegistry;
use App\Support\MediaPath;
use Illuminate\Support\Facades\DB;

final class ProductGalleryService
{
    public function __construct(
        protected MediaRegistry $mediaRegistry,
    ) {}

    /** @return list<string> */
    public function pathsFromUploadState(mixed $uploadState): array
    {
        if (is_string($uploadState) && $uploadState !== '') {
            return [$this->normalizePath($uploadState)];
        }

        if (! is_array($uploadState) || $uploadState === []) {
            return [];
        }

        $paths = [];

        foreach ($uploadState as $value) {
            foreach ($this->pathsFromUploadState($value) as $path) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique(array_filter($paths)));
    }

    /**
     * @param  list<string>  $paths  مسیر نسبی روی disk public
     */
    public function attachPaths(Product $product, array $paths): int
    {
        $paths = array_values(array_unique(array_filter(
            array_map(fn (string $path): string => $this->normalizePath($path), $paths),
            fn (string $path): bool => $path !== '',
        )));

        if ($paths === []) {
            return 0;
        }

        return (int) DB::transaction(function () use ($product, $paths): int {
            $existing = $product->images()->orderBy('position')->get();
            $nextPosition = ((int) $existing->max('position')) + 1;
            $hasPrimary = $existing->contains(fn (ProductImage $image): bool => (bool) $image->is_primary);
            $created = 0;

            foreach ($paths as $path) {
                $this->mediaRegistry->registerFromPath('public', $path);

                $makePrimary = ! $hasPrimary;

                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'image' => $path,
                    'is_primary' => $makePrimary,
                    'position' => $nextPosition,
                ]);

                if ($makePrimary) {
                    $hasPrimary = true;
                }

                $nextPosition++;
                $created++;
            }

            return $created;
        });
    }

    protected function normalizePath(string $path): string
    {
        return MediaPath::normalize($path) ?? $path;
    }
}
