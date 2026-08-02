<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaStorage
{
    public static function disk(): string
    {
        return config('hhms.media_disk', 'public') ?: 'public';
    }

    public static function store(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, self::disk());
    }

    public static function put(string $path, string $contents): void
    {
        Storage::disk(self::disk())->put($path, $contents);
    }

    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (self::disk() === 'public' && file_exists(public_path($path))) {
            return asset($path);
        }

        return Storage::disk(self::disk())->url($path);
    }
}
