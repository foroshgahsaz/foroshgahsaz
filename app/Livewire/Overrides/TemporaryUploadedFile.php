<?php

namespace Livewire\Features\SupportFileUploads;

use App\Support\LivewireUploadFilename;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use League\Flysystem\UnableToCheckExistence;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Throwable;

class TemporaryUploadedFile extends UploadedFile
{
    protected $disk;

    protected $storage;

    protected $path;

    public function __construct($path, $disk)
    {
        $this->disk = $disk;
        $this->storage = Storage::disk($this->disk);
        $this->path = FileUploadConfiguration::path(self::normalizeTempPath($path), false);

        $tmpFile = tmpfile();

        parent::__construct(stream_get_meta_data($tmpFile)['uri'], $this->path);

        if (app()->runningUnitTests()) {
            @touch($this->path(), now()->timestamp);
        }
    }

    public function getPath(): string
    {
        return $this->storage->path(FileUploadConfiguration::directory());
    }

    public function isValid(): bool
    {
        if (self::isBrokenTempReference($this->path)) {
            return false;
        }

        try {
            return $this->exists();
        } catch (UnableToCheckExistence|Throwable) {
            return false;
        }
    }

    public function getSize(): int
    {
        if (app()->runningUnitTests() && str($this->getFilename())->contains('-size=')) {
            return (int) str($this->getFilename())->between('-size=', '.')->__toString();
        }

        if (! $this->isValid()) {
            return 0;
        }

        return (int) $this->storage->size($this->path);
    }

    public function getMimeType(): string
    {
        if (app()->runningUnitTests() && str($this->getFilename())->contains('-mimeType=')) {
            $escapedMimeType = str($this->getFilename())->between('-mimeType=', '-');

            return (string) $escapedMimeType->replace('_', '/');
        }

        if (! $this->isValid()) {
            return 'application/octet-stream';
        }

        $mimeType = $this->storage->mimeType($this->path);

        if (in_array($mimeType, ['application/octet-stream', 'inode/x-empty', 'application/x-empty'], true)) {
            $detector = new FinfoMimeTypeDetector;

            $mimeType = $detector->detectMimeTypeFromPath($this->path) ?: 'text/plain';
        }

        return $mimeType;
    }

    public function getFilename(): string
    {
        return $this->getName($this->path);
    }

    public function getRealPath(): string
    {
        return $this->storage->path($this->path);
    }

    public function getPathname(): string
    {
        return $this->getRealPath();
    }

    public function getClientOriginalName(): string
    {
        $original = $this->extractOriginalNameFromFilePath($this->path);

        return is_string($original) && $original !== '' ? $original : $this->getFilename();
    }

    public function dimensions()
    {
        if (! $this->isValid()) {
            return false;
        }

        stream_copy_to_stream($this->storage->readStream($this->path), $tmpFile = tmpfile());

        return @getimagesize(stream_get_meta_data($tmpFile)['uri']);
    }

    public function temporaryUrl()
    {
        if (! $this->isPreviewable()) {
            throw new FileNotPreviewableException($this);
        }

        if ((FileUploadConfiguration::isUsingS3() or FileUploadConfiguration::isUsingGCS()) && ! app()->runningUnitTests()) {
            return $this->storage->temporaryUrl(
                $this->path,
                now()->addDay()->endOfHour(),
                ['ResponseContentDisposition' => 'attachment; filename="'.urlencode($this->getClientOriginalName()).'"']
            );
        }

        if (method_exists($this->storage->getAdapter(), 'getTemporaryUrl')) {
            return $this->storage->temporaryUrl($this->path, now()->addDay());
        }

        return URL::temporarySignedRoute(
            'livewire.preview-file', now()->addMinutes(30)->endOfHour(), ['filename' => $this->getFilename()]
        );
    }

    public function isPreviewable()
    {
        $supportedPreviewTypes = config('livewire.temporary_file_upload.preview_mimes', [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ]);

        return in_array($this->guessExtension(), $supportedPreviewTypes, true);
    }

    public function readStream()
    {
        return $this->storage->readStream($this->path);
    }

    public function exists()
    {
        return $this->storage->exists($this->path);
    }

    public function get()
    {
        return $this->storage->get($this->path);
    }

    public function delete()
    {
        return $this->storage->delete($this->path);
    }

    public function storeAs($path, $name = null, $options = [])
    {
        $options = $this->parseOptions($options);

        $disk = Arr::pull($options, 'disk') ?: $this->disk;

        $newPath = trim($path.'/'.$name, '/');

        Storage::disk($disk)->put(
            $newPath, $this->storage->readStream($this->path), $options
        );

        return $newPath;
    }

    public static function generateHashNameWithOriginalNameEmbedded($file)
    {
        return LivewireUploadFilename::generate($file);
    }

    public function hashName($path = null)
    {
        if (app()->runningUnitTests() && str($this->getFilename())->contains('-hash=')) {
            return str($this->getFilename())->between('-hash=', '-mimeType')->value();
        }

        return parent::hashName($path);
    }

    public function extractOriginalNameFromFilePath($path)
    {
        if (! str($path)->contains('-meta')) {
            return basename((string) $path);
        }

        $decoded = base64_decode(head(explode('-', last(explode('-meta', str($path)->replace('_', '/'))))));

        return is_string($decoded) ? $decoded : basename((string) $path);
    }

    public static function createFromLivewire($filePath)
    {
        return new static(self::normalizeTempPath($filePath), FileUploadConfiguration::disk());
    }

    public static function canUnserialize($subject)
    {
        if (is_string($subject)) {
            return (string) str($subject)->startsWith(['livewire-file:', 'livewire-files:']);
        }

        if (is_array($subject)) {
            return collect($subject)->contains(function ($value) {
                return static::canUnserialize($value);
            });
        }

        return false;
    }

    public static function unserializeFromLivewireRequest($subject)
    {
        if (is_string($subject)) {
            if (str($subject)->startsWith('livewire-file:')) {
                return static::createFromLivewire(str($subject)->after('livewire-file:'));
            }

            if (str($subject)->startsWith('livewire-files:')) {
                $paths = json_decode(str($subject)->after('livewire-files:'), true);

                return collect($paths)->map(function ($path) {
                    return static::createFromLivewire($path);
                })->toArray();
            }
        }

        if (is_array($subject)) {
            foreach ($subject as $key => $value) {
                $subject[$key] = static::unserializeFromLivewireRequest($value);
            }
        }

        return $subject;
    }

    public function serializeForLivewireResponse()
    {
        return 'livewire-file:'.$this->getFilename();
    }

    public static function serializeMultipleForLivewireResponse($files)
    {
        return 'livewire-files:'.json_encode(collect($files)->map->getFilename());
    }

    protected static function normalizeTempPath(mixed $filePath): string
    {
        $path = trim((string) $filePath, '/');

        if (str_starts_with($path, 'livewire-tmp/')) {
            $path = substr($path, strlen('livewire-tmp/'));
        }

        $path = trim($path, '/');

        if ($path === '' || $path === 'livewire-tmp') {
            return '__invalid-upload__';
        }

        return $path;
    }

    protected static function isBrokenTempReference(string $path): bool
    {
        return $path === FileUploadConfiguration::path('livewire-tmp', false)
            || $path === FileUploadConfiguration::path('__invalid-upload__', false)
            || str_ends_with($path, '/livewire-tmp');
    }
}
