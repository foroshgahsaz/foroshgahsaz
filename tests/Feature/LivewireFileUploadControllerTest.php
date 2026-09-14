<?php

namespace Tests\Feature;

use App\Http\Controllers\LivewireFileUploadController;
use App\Support\LivewireUploadFilename;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Tests\TestCase;

class LivewireFileUploadControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_temp_upload_with_safe_filename(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create(str_repeat('long-name-', 30).'product.jpg', 120, 'image/jpeg');
        $controller = app(LivewireFileUploadController::class);

        $paths = $controller->validateAndStore([$file], 'public');

        $this->assertCount(1, $paths);
        $storedName = $paths->first();

        $this->assertNotSame('livewire-tmp', $storedName);
        $this->assertLessThanOrEqual(LivewireUploadFilename::MAX_FILENAME_LENGTH, strlen($storedName));
        Storage::disk('public')->assertExists(FileUploadConfiguration::path($storedName));
    }
}
