<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DocumentOcrController extends Controller
{
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document' => 'required|file|mimes:jpg,jpeg,png,webp|max:10240',
            'document_type' => 'nullable|in:emirates_id,passport',
        ]);

        $apiKey = config('services.openai.key');

        if (blank($apiKey)) {
            return response()->json([
                'ok' => false,
                'message' => 'OCR is not configured. Add OPENAI_API_KEY in .env, then clear config cache.',
            ], 422);
        }

        $file = $validated['document'];
        $mime = $file->getMimeType() ?: 'image/jpeg';
        $imageDataUrl = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
        $documentType = $validated['document_type'] ?? 'emirates_id';

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.ocr_model'),
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Extract identity document fields from UAE Emirates IDs and global passports. Return strict JSON only. If a field is not visible, use null. Use ISO date format YYYY-MM-DD. Gender must be Male, Female, or Other. Include confidence from 0 to 100 and an array of warnings.',
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Document type selected by user: ' . $documentType . '. Extract: document_type, full_name_en, full_name_ar, document_number, nationality, gender, date_of_birth, issue_date, expiry_date, confidence, warnings.',
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => $imageDataUrl,
                                        'detail' => 'high',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('Document OCR failed', [
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 1000),
                ]);

                return response()->json([
                    'ok' => false,
                    'message' => 'OCR service failed. Please check API key/model and try again.',
                ], 422);
            }

            $content = $response->json('choices.0.message.content');
            $data = json_decode($content, true);

            if (! is_array($data)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'OCR returned unreadable data. Please enter details manually.',
                ], 422);
            }

            return response()->json([
                'ok' => true,
                'data' => [
                    'document_type' => $data['document_type'] ?? $documentType,
                    'name' => $data['full_name_en'] ?? null,
                    'name_ar' => $data['full_name_ar'] ?? null,
                    'eid_passport_no' => $data['document_number'] ?? null,
                    'nationality' => $data['nationality'] ?? null,
                    'gender' => $this->normalizeGender($data['gender'] ?? null),
                    'dob' => $this->normalizeDate($data['date_of_birth'] ?? null),
                    'id_issue_date' => $this->normalizeDate($data['issue_date'] ?? null),
                    'id_expiry_date' => $this->normalizeDate($data['expiry_date'] ?? null),
                    'confidence' => max(0, min(100, (int) ($data['confidence'] ?? 0))),
                    'warnings' => array_values(array_filter((array) ($data['warnings'] ?? []))),
                ],
            ]);
        } catch (\Throwable $exception) {
            Log::error('Document OCR exception: ' . $exception->getMessage());

            return response()->json([
                'ok' => false,
                'message' => 'OCR could not process this file. Please enter details manually.',
            ], 422);
        }
    }

    private function normalizeDate(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeGender(?string $value): ?string
    {
        $value = Str::lower((string) $value);

        return match (true) {
            str_starts_with($value, 'm') => 'Male',
            str_starts_with($value, 'f') => 'Female',
            default => filled($value) ? 'Other' : null,
        };
    }
}
