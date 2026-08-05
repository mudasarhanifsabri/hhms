<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaStorage
{
    public static function disk(): string
    {
        return config('hhms.media_disk', 'public') ?: 'public';
    }

    public static function store(UploadedFile $file, string $folder): string
    {
        return $file->storeAs(self::datedFolder($folder), self::trackedFilename($file), self::disk());
    }

    public static function datedFolder(string $folder): string
    {
        return trim($folder, '/') . '/' . now()->format('Y/m/d');
    }

    public static function trackedFilename(UploadedFile $file, ?string $extension = null): string
    {
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'upload';
        $extension ??= strtolower($file->getClientOriginalExtension() ?: 'bin');

        return now()->format('His') . '-' . Str::lower(Str::random(6)) . '-' . Str::limit($name, 60, '') . '.' . $extension;
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
