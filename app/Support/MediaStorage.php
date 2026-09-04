<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class MediaStorage
{
    public static function disk(): string
    {
        return config('hhms.media_disk', 'public') ?: 'public';
    }

    public static function store(UploadedFile $file, string $folder): string
    {
        $folder = self::datedFolder($folder);

        $stored = Storage::disk(self::disk())->putFileAs(
            self::path($folder),
            $file,
            $filename = self::trackedFilename($file)
        );
        if ($stored === false) throw new \RuntimeException('Media upload failed. Please retry; the file was not saved.');

        return trim($folder . '/' . $filename, '/');
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
        Storage::disk(self::disk())->put(self::path($path), $contents);
    }

    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $disk = self::disk();

        if ($disk === 'public' && file_exists(public_path($path))) {
            return asset($path);
        }

        if ($disk === 's3' && blank(config('filesystems.disks.s3.bucket'))) {
            Log::warning('S3 media URL requested without AWS bucket configured.', ['path' => $path]);

            return null;
        }

        if ($disk === 's3') {
            return self::temporaryUrl($path);
        }

        return Storage::disk($disk)->url(self::path($path));
    }

    private static function temporaryUrl(string $path): ?string
    {
        try {
            return Storage::disk('s3')->temporaryUrl(
                self::path($path),
                now()->addMinutes((int) config('hhms.s3_temporary_url_minutes', 60))
            );
        } catch (Throwable $exception) {
            Log::warning('Unable to generate S3 temporary media URL.', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public static function path(string $path): string
    {
        $path = trim($path, '/');

        if (self::disk() !== 's3') {
            return $path;
        }

        $prefix = trim((string) config('hhms.s3_prefix', 'HHMS'), '/');

        if ($prefix === '' || str_starts_with($path, $prefix . '/')) {
            return $path;
        }

        return $prefix . '/' . $path;
    }
}
