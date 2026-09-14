<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class LivewireUploadFilename
{
    /** Keep temp paths well below common 255-byte filename limits. */
    public const MAX_FILENAME_LENGTH = 180;

    public static function generate(UploadedFile $file): string
    {
        $hash = Str::random(40);
        $extension = Str::lower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin'));
        $extension = Str::limit(preg_replace('/[^a-z0-9]+/', '', $extension) ?: 'bin', 10, '');

        $originalName = Str::limit((string) $file->getClientOriginalName(), 80, '');
        $meta = str('-meta'.base64_encode($originalName).'-')->replace('/', '_')->toString();

        $filename = $hash.$meta.'.'.$extension;

        if (strlen($filename) <= self::MAX_FILENAME_LENGTH) {
            return $filename;
        }

        $maxMetaLength = max(0, self::MAX_FILENAME_LENGTH - strlen($hash) - strlen($extension) - 2);
        $meta = Str::limit($meta, $maxMetaLength, '');

        $filename = $hash.$meta.'.'.$extension;

        if (strlen($filename) <= self::MAX_FILENAME_LENGTH) {
            return $filename;
        }

        return $hash.'.'.$extension;
    }
}
