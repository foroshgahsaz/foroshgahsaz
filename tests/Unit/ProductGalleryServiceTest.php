<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Product\ProductGalleryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductGalleryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_attaches_multiple_paths_with_positions(): void
    {
        $product = Product::factory()->create();

        ProductImage::query()->create([
            'product_id' => $product->id,
            'image' => 'products/existing.webp',
            'is_primary' => true,
            'position' => 3,
        ]);

        $service = app(ProductGalleryService::class);

        $count = $service->attachPaths($product, [
            'products/a.webp',
            'products/b.webp',
        ]);

        $this->assertSame(2, $count);

        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'image' => 'products/a.webp',
            'is_primary' => false,
            'position' => 4,
        ]);

        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'image' => 'products/b.webp',
            'position' => 5,
        ]);
    }

    public function test_first_image_becomes_primary_when_none_exists(): void
    {
        $product = Product::factory()->create();
        $service = app(ProductGalleryService::class);

        $service->attachPaths($product, ['products/first.webp', 'products/second.webp']);

        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'image' => 'products/first.webp',
            'is_primary' => true,
            'position' => 0,
        ]);

        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'image' => 'products/second.webp',
            'is_primary' => false,
            'position' => 1,
        ]);
    }

    public function test_paths_from_upload_state_flattens_filament_array(): void
    {
        $service = app(ProductGalleryService::class);

        $paths = $service->pathsFromUploadState([
            'uuid-1' => 'products/one.webp',
            'uuid-2' => 'products/two.webp',
        ]);

        $this->assertSame(['products/one.webp', 'products/two.webp'], $paths);
    }
}
