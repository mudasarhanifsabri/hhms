<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Support\AppSettings;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function edit()
    {
        $settings = AppSettings::all();

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:50',
            'invoice_establishment_name' => 'nullable|string|max:255',
            'invoice_legal_name' => 'nullable|string|max:255',
            'invoice_trn' => 'nullable|digits:15',
            'invoice_address' => 'nullable|string|max:1000',
            'media_disk' => 'required|in:public,s3',

            'mail_mailer' => 'required|in:log,smtp',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|in:tls,ssl',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',

            'whatsapp_provider' => 'nullable|string|max:255',
            'whatsapp_phone_number_id' => 'nullable|string|max:255',
            'whatsapp_token' => 'nullable|string|max:1000',
            'whatsapp_verify_token' => 'nullable|string|max:255',

            'sms_provider' => 'nullable|string|max:255',
            'sms_sender_id' => 'nullable|string|max:255',
            'sms_api_key' => 'nullable|string|max:1000',
            'sms_api_secret' => 'nullable|string|max:1000',

            'aws_access_key_id' => [Rule::requiredIf($request->input('media_disk') === 's3' && blank(AppSettings::get('aws_access_key_id'))), 'nullable', 'string', 'max:255'],
            'aws_secret_access_key' => [Rule::requiredIf($request->input('media_disk') === 's3' && blank(AppSettings::get('aws_secret_access_key'))), 'nullable', 'string', 'max:1000'],
            'aws_default_region' => [Rule::requiredIf($request->input('media_disk') === 's3'), 'nullable', 'string', 'max:100'],
            'aws_bucket' => [
                Rule::requiredIf($request->input('media_disk') === 's3'),
                'nullable',
                'string',
                'min:3',
                'max:63',
                'regex:/^[a-z0-9][a-z0-9.-]*[a-z0-9]$/',
                Rule::notIn(AppSettings::RESERVED_MEDIA_FOLDERS),
            ],
            'aws_url' => 'nullable|url|max:500',
            'aws_endpoint' => 'nullable|url|max:500',
            'aws_textract_region' => 'nullable|string|max:100',
            'aws_textract_access_key_id' => 'nullable|string|max:255',
            'aws_textract_secret_access_key' => 'nullable|string|max:1000',

            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'favicon' => 'nullable|mimes:ico,png|max:512',
        ], [
            'aws_bucket.regex' => 'AWS bucket must be the real S3 bucket name only. Do not enter folders like id_documents.',
            'aws_bucket.not_in' => 'AWS bucket cannot be an upload folder. Enter the real shared S3 bucket name.',
        ]);

        unset($validated['logo'], $validated['favicon']);

        foreach (AppSettings::ENCRYPTED_KEYS as $secretKey) {
            if (blank($validated[$secretKey] ?? null)) {
                unset($validated[$secretKey]);
            }
        }

        AppSettings::setMany($validated);

        $branding = [];

        if ($request->hasFile('logo')) {
            $branding['logo_path'] = MediaStorage::store($request->file('logo'), 'branding');
        }

        if ($request->hasFile('favicon')) {
            $branding['favicon_path'] = MediaStorage::store($request->file('favicon'), 'branding');
        }

        if (! empty($branding)) {
            AppSettings::setMany($branding);
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', 'Settings updated successfully.');
    }
}
