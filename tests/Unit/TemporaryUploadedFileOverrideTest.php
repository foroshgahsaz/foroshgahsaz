<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Tests\TestCase;

class TemporaryUploadedFileOverrideTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('tmp-for-tests');
    }

    public function test_broken_temp_reference_is_not_valid(): void
    {
        $file = TemporaryUploadedFile::createFromLivewire('livewire-tmp');

        $this->assertFalse($file->isValid());
        $this->assertSame(0, $file->getSize());
    }

    public function test_valid_temp_file_passes_validation(): void
    {
        Storage::disk('tmp-for-tests')->put('livewire-tmp/sample.jpg', 'contents');

        $file = TemporaryUploadedFile::createFromLivewire('sample.jpg');

        $this->assertTrue($file->isValid());
        $this->assertSame(8, $file->getSize());
    }
}
