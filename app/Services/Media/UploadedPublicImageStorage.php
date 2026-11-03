<?php

namespace App\Services\Media;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class UploadedPublicImageStorage
{
    public function __construct(
        protected ImageOptimizer $optimizer,
        protected MediaRegistry $registry,
    ) {}

    public function store(TemporaryUploadedFile $file, string $directory): string
    {
        $disk = 'public';
        $directory = trim($directory, '/') !== '' ? trim($directory, '/') : 'uploads';
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin'));
        $extension = preg_replace('/[^a-z0-9]+/', '', $extension) ?: 'bin';
        $filename = Str::ulid().'.'.$extension;

        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'upload_file' => 'فایل موقت آپلود منقضی شده. دوباره انتخاب کنید و تا پایان آپلود صبر کنید.',
            ]);
        }

        $path = $file->storeAs($directory, $filename, ['disk' => $disk]);
        $path = $this->optimizer->optimize($disk, $path, $directory);

        $this->registry->registerFromPath(
            $disk,
            $path,
            $file->getClientOriginalName(),
        );

        return $path;
    }
}
