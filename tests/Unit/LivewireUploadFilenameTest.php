<?php

namespace Tests\Unit;

use App\Support\LivewireUploadFilename;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LivewireUploadFilenameTest extends TestCase
{
    public function test_it_shortens_overly_long_generated_names(): void
    {
        $longName = str_repeat('تصویر-محصول-', 40).'photo.jpg';
        $file = UploadedFile::fake()->create($longName, 100, 'image/jpeg');

        $filename = LivewireUploadFilename::generate($file);

        $this->assertLessThanOrEqual(LivewireUploadFilename::MAX_FILENAME_LENGTH, strlen($filename));
        $this->assertStringEndsWith('.jpg', $filename);
    }

    public function test_it_keeps_short_original_names_embedded(): void
    {
        $file = UploadedFile::fake()->create('cover.jpg', 100, 'image/jpeg');

        $filename = LivewireUploadFilename::generate($file);

        $this->assertStringContainsString('-meta', $filename);
        $this->assertStringEndsWith('.jpg', $filename);
    }
}
