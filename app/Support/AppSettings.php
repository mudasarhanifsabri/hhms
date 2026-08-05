<?php

namespace App\Support;

use App\Models\ApplicationSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AppSettings
{
    public const CACHE_KEY = 'application_settings_map';

    public const RESERVED_MEDIA_FOLDERS = [
        'branding',
        'documents',
        'floor_plans',
        'id_documents',
        'property_photos',
        'task_attachments',
        'task_costs',
        'task_photos',
        'videos',
    ];

    public const ENCRYPTED_KEYS = [
        'mail_password',
        'whatsapp_token',
        'whatsapp_verify_token',
        'sms_api_secret',
        'aws_access_key_id',
        'aws_secret_access_key',
        'aws_textract_access_key_id',
        'aws_textract_secret_access_key',
    ];

    public static function all(): array
    {
        if (! self::tableReady()) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, function () {
            return ApplicationSetting::query()
                ->get()
                ->mapWithKeys(fn (ApplicationSetting $setting) => [
                    $setting->key => self::readValue($setting),
                ])
                ->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $encrypted = in_array($key, self::ENCRYPTED_KEYS, true);

            ApplicationSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => self::groupFor($key),
                    'value' => $encrypted && filled($value) ? Crypt::encryptString($value) : $value,
                    'is_encrypted' => $encrypted,
                ]
            );
        }

        Cache::forget(self::CACHE_KEY);
        self::apply();
    }

    public static function apply(): void
    {
        $settings = self::all();

        if (empty($settings)) {
            return;
        }

        $mediaDisk = $settings['media_disk'] ?? config('hhms.media_disk', 'public');

        config([
            'app.name' => $settings['company_name'] ?? config('app.name'),
            'hhms.media_disk' => $mediaDisk,
            'mail.default' => $settings['mail_mailer'] ?? config('mail.default'),
            'mail.from.address' => $settings['mail_from_address'] ?? config('mail.from.address'),
            'mail.from.name' => $settings['mail_from_name'] ?? config('mail.from.name'),
        ]);

        if (($settings['mail_mailer'] ?? null) === 'smtp') {
            config([
                'mail.mailers.smtp.host' => $settings['mail_host'] ?? config('mail.mailers.smtp.host'),
                'mail.mailers.smtp.port' => $settings['mail_port'] ?? config('mail.mailers.smtp.port'),
                'mail.mailers.smtp.username' => $settings['mail_username'] ?? config('mail.mailers.smtp.username'),
                'mail.mailers.smtp.password' => $settings['mail_password'] ?? config('mail.mailers.smtp.password'),
                'mail.mailers.smtp.scheme' => $settings['mail_encryption'] ?? config('mail.mailers.smtp.scheme'),
            ]);
        }

        $savedS3Bucket = $settings['aws_bucket'] ?? null;
        $s3Bucket = self::validS3Bucket($savedS3Bucket)
            ? $savedS3Bucket
            : config('filesystems.disks.s3.bucket');

        config([
            'filesystems.disks.s3.key' => $settings['aws_access_key_id'] ?? config('filesystems.disks.s3.key'),
            'filesystems.disks.s3.secret' => $settings['aws_secret_access_key'] ?? config('filesystems.disks.s3.secret'),
            'filesystems.disks.s3.region' => $settings['aws_default_region'] ?? config('filesystems.disks.s3.region'),
            'filesystems.disks.s3.bucket' => $s3Bucket,
            'filesystems.disks.s3.url' => $settings['aws_url'] ?? config('filesystems.disks.s3.url'),
            'filesystems.disks.s3.endpoint' => $settings['aws_endpoint'] ?? config('filesystems.disks.s3.endpoint'),
            'services.textract.key' => $settings['aws_textract_access_key_id'] ?? $settings['aws_access_key_id'] ?? config('services.textract.key'),
            'services.textract.secret' => $settings['aws_textract_secret_access_key'] ?? $settings['aws_secret_access_key'] ?? config('services.textract.secret'),
            'services.textract.region' => $settings['aws_textract_region'] ?? $settings['aws_default_region'] ?? config('services.textract.region'),
            'services.whatsapp.provider' => $settings['whatsapp_provider'] ?? null,
            'services.whatsapp.phone_number_id' => $settings['whatsapp_phone_number_id'] ?? null,
            'services.whatsapp.token' => $settings['whatsapp_token'] ?? null,
            'services.whatsapp.verify_token' => $settings['whatsapp_verify_token'] ?? null,
            'services.sms.provider' => $settings['sms_provider'] ?? null,
            'services.sms.sender_id' => $settings['sms_sender_id'] ?? null,
            'services.sms.api_key' => $settings['sms_api_key'] ?? null,
            'services.sms.api_secret' => $settings['sms_api_secret'] ?? null,
            'hhms.logo_path' => $settings['logo_path'] ?? null,
            'hhms.favicon_path' => $settings['favicon_path'] ?? null,
        ]);
    }

    private static function readValue(ApplicationSetting $setting): mixed
    {
        if (! $setting->is_encrypted || blank($setting->value)) {
            return $setting->value;
        }

        try {
            return Crypt::decryptString($setting->value);
        } catch (Throwable) {
            return null;
        }
    }

    public static function validS3Bucket(?string $bucket): bool
    {
        if (blank($bucket) || str_contains($bucket, '/') || str_contains($bucket, '\\')) {
            return false;
        }

        $bucket = trim($bucket);

        if (in_array($bucket, self::RESERVED_MEDIA_FOLDERS, true)) {
            return false;
        }

        return (bool) preg_match('/^[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]$/', $bucket);
    }

    private static function groupFor(string $key): string
    {
        return match (true) {
            str_starts_with($key, 'mail_') => 'email',
            str_starts_with($key, 'whatsapp_') => 'whatsapp',
            str_starts_with($key, 'sms_') => 'sms',
            str_starts_with($key, 'aws_textract_') => 'ocr',
            str_starts_with($key, 'aws_'), $key === 'media_disk' => 'storage',
            str_contains($key, 'logo'), str_contains($key, 'favicon'), str_starts_with($key, 'company_') => 'branding',
            default => 'general',
        };
    }

    private static function tableReady(): bool
    {
        try {
            return Schema::hasTable('application_settings');
        } catch (Throwable) {
            return false;
        }
    }
}
