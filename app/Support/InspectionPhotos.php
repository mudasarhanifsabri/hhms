<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class InspectionPhotos
{
    public static function thumbnail(string $path): ?string
    {
        // Read trusted storage keys, never fetch arbitrary URLs from report data.
        if (str_contains($path, '..') || str_contains($path, '://')) {
            return null;
        }
        try {
            $disk = Storage::disk(MediaStorage::disk());
            if (MediaStorage::disk() === 'public' && is_file(public_path($path))) {
                if (filesize(public_path($path)) > 10 * 1024 * 1024) {
                    return null;
                }
                $bytes = file_get_contents(public_path($path));
            } else {
                $key = MediaStorage::path($path);
                if (! $disk->exists($key) || $disk->size($key) > 10 * 1024 * 1024) {
                    return null;
                }
                $bytes = $disk->get($key);
            }
            $size = @getimagesizefromstring($bytes);
            if (! $size || $size[0] * $size[1] > 24000000) {
                return null;
            }
            $source = @imagecreatefromstring($bytes);
            if (! $source) {
                return null;
            }
            $scale = min(1, 600 / $size[0], 380 / $size[1]);
            $thumb = imagecreatetruecolor(max(1, (int) ($size[0] * $scale)), max(1, (int) ($size[1] * $scale)));
            imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 255));
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, imagesx($thumb), imagesy($thumb), $size[0], $size[1]);
            ob_start();
            imagejpeg($thumb, null, 82);
            $output = ob_get_clean();
            imagedestroy($source);
            imagedestroy($thumb);

            return 'data:image/jpeg;base64,'.base64_encode($output);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
