<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Aws\Exception\AwsException;
use Aws\Textract\TextractClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DocumentOcrController extends Controller
{
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'document_type' => 'nullable|in:emirates_id,passport',
        ]);

        if (blank(config('services.textract.key')) || blank(config('services.textract.secret'))) {
            return response()->json([
                'ok' => false,
                'message' => 'AWS OCR is not configured. Add AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY and AWS_TEXTRACT_REGION in .env, then clear config cache.',
            ], 422);
        }

        $fileBytes = file_get_contents($validated['document']->getRealPath());
        $documentType = $validated['document_type'] ?? 'emirates_id';
        $warnings = [];

        try {
            $client = $this->textractClient();
            $fields = $this->analyzeIdentityDocument($client, $fileBytes);
            $text = $fields['_full_text'] ?? '';

            if (blank($text)) {
                $text = $this->detectDocumentText($client, $fileBytes);
            }

            $data = $this->mapExtractedData($fields, $text, $documentType);

            if (($data['confidence'] ?? 0) < 45) {
                $warnings[] = 'Low OCR confidence. Please review and correct the extracted data.';
            }

            if (blank($data['name']) || blank($data['eid_passport_no'])) {
                $warnings[] = 'Some important fields could not be detected from this document.';
            }

            $data['warnings'] = array_values(array_unique(array_merge($data['warnings'] ?? [], $warnings)));

            return response()->json([
                'ok' => true,
                'data' => $data,
            ]);
        } catch (AwsException $exception) {
            Log::warning('AWS Textract OCR failed', [
                'aws_error' => $exception->getAwsErrorCode(),
                'message' => $exception->getAwsErrorMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'AWS OCR failed: ' . ($exception->getAwsErrorMessage() ?: $exception->getMessage()),
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('AWS OCR exception: ' . $exception->getMessage());

            return response()->json([
                'ok' => false,
                'message' => 'AWS OCR could not process this file. Please enter details manually.',
            ], 422);
        }
    }

    private function textractClient(): TextractClient
    {
        return new TextractClient([
            'version' => '2018-06-27',
            'region' => config('services.textract.region'),
            'credentials' => [
                'key' => config('services.textract.key'),
                'secret' => config('services.textract.secret'),
            ],
        ]);
    }

    private function analyzeIdentityDocument(TextractClient $client, string $fileBytes): array
    {
        $result = $client->analyzeID([
            'DocumentPages' => [
                ['Bytes' => $fileBytes],
            ],
        ]);

        $fields = [];
        $textLines = [];
        $confidenceValues = [];

        foreach (($result['IdentityDocuments'] ?? []) as $document) {
            foreach (($document['IdentityDocumentFields'] ?? []) as $field) {
                $key = $this->normalizeKey($field['Type']['Text'] ?? '');
                $value = trim((string) ($field['ValueDetection']['Text'] ?? ''));
                $confidence = (float) ($field['ValueDetection']['Confidence'] ?? 0);

                if ($key && $value) {
                    $fields[$key] = $this->preferValue($fields[$key] ?? null, $value);
                    $confidenceValues[] = $confidence;
                    $textLines[] = $value;
                }
            }

            foreach (($document['Blocks'] ?? []) as $block) {
                if (($block['BlockType'] ?? null) === 'LINE' && filled($block['Text'] ?? null)) {
                    $textLines[] = $block['Text'];
                    $confidenceValues[] = (float) ($block['Confidence'] ?? 0);
                }
            }
        }

        $fields['_full_text'] = implode("\n", array_unique(array_filter($textLines)));
        $fields['_confidence'] = $confidenceValues ? (int) round(array_sum($confidenceValues) / count($confidenceValues)) : 0;

        return $fields;
    }

    private function detectDocumentText(TextractClient $client, string $fileBytes): string
    {
        $result = $client->detectDocumentText([
            'Document' => ['Bytes' => $fileBytes],
        ]);

        $lines = [];

        foreach (($result['Blocks'] ?? []) as $block) {
            if (($block['BlockType'] ?? null) === 'LINE' && filled($block['Text'] ?? null)) {
                $lines[] = $block['Text'];
            }
        }

        return implode("\n", $lines);
    }

    private function mapExtractedData(array $fields, string $text, string $documentType): array
    {
        $normalizedText = $this->normalizeText($text);
        $mrz = $this->parseMrz($normalizedText);
        $uaeId = $this->matchFirst('/\b784[-\s]?\d{4}[-\s]?\d{7}[-\s]?\d\b/', $normalizedText);
        $passportNumber = $this->firstFilled(
            $fields['document_number'] ?? null,
            $fields['id_number'] ?? null,
            $fields['passport_number'] ?? null,
            $mrz['document_number'] ?? null
        );

        $name = $this->firstFilled(
            $fields['name'] ?? null,
            $fields['full_name'] ?? null,
            trim(($fields['first_name'] ?? '') . ' ' . ($fields['middle_name'] ?? '') . ' ' . ($fields['last_name'] ?? '')),
            $mrz['name'] ?? null,
            $this->extractAfterLabel($normalizedText, ['Name', 'Given Name', 'Surname'])
        );

        $nationality = $this->firstFilled(
            $fields['nationality'] ?? null,
            $mrz['nationality'] ?? null,
            $this->extractAfterLabel($normalizedText, ['Nationality'])
        );

        $dates = $this->extractDates($normalizedText);

        return [
            'document_type' => $documentType,
            'name' => $this->cleanName($name),
            'name_ar' => null,
            'eid_passport_no' => $documentType === 'emirates_id' ? ($uaeId ?: $passportNumber) : $passportNumber,
            'nationality' => $this->normalizeNationality($nationality),
            'gender' => $this->normalizeGender($this->firstFilled($fields['sex'] ?? null, $fields['gender'] ?? null, $mrz['gender'] ?? null, $this->extractAfterLabel($normalizedText, ['Sex']))),
            'dob' => $this->normalizeDate($this->firstFilled($fields['date_of_birth'] ?? null, $fields['birth_date'] ?? null, $mrz['dob'] ?? null, $dates['birth'] ?? null)),
            'id_issue_date' => $this->normalizeDate($this->firstFilled($fields['date_of_issue'] ?? null, $fields['issue_date'] ?? null, $dates['issue'] ?? null)),
            'id_expiry_date' => $this->normalizeDate($this->firstFilled($fields['expiration_date'] ?? null, $fields['expiry_date'] ?? null, $mrz['expiry'] ?? null, $dates['expiry'] ?? null)),
            'confidence' => max(0, min(100, (int) ($fields['_confidence'] ?? 55))),
            'warnings' => [],
        ];
    }

    private function parseMrz(string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", strtoupper($text))), fn ($line) => str_contains($line, '<')));
        $mrz = [];

        foreach ($lines as $index => $line) {
            if (preg_match('/^P<([A-Z]{3})([A-Z<]+)$/', $line, $matches)) {
                $names = array_values(array_filter(explode('<<', $matches[2])));
                $surname = str_replace('<', ' ', $names[0] ?? '');
                $given = str_replace('<', ' ', $names[1] ?? '');
                $mrz['name'] = trim($given . ' ' . $surname);
                $mrz['nationality'] = $this->countryCodeToNationality($matches[1]);

                $detailLine = $lines[$index + 1] ?? '';
                if (preg_match('/^([A-Z0-9<]{6,12}).*?([A-Z]{3})(\d{6})([MF<])(\d{6})/', $detailLine, $details)) {
                    $mrz['document_number'] = trim(str_replace('<', '', $details[1]));
                    $mrz['nationality'] = $this->countryCodeToNationality($details[2]) ?: ($mrz['nationality'] ?? null);
                    $mrz['dob'] = $this->mrzDate($details[3], true);
                    $mrz['gender'] = $details[4] === 'M' ? 'Male' : ($details[4] === 'F' ? 'Female' : null);
                    $mrz['expiry'] = $this->mrzDate($details[5], false);
                }
            }
        }

        return $mrz;
    }

    private function extractDates(string $text): array
    {
        $dates = [];

        foreach ([
            'birth' => '/(?:Date of Birth|Birth Date|DOB)[^\d]{0,20}(\d{1,2}[\/\-. ]\d{1,2}[\/\-. ]\d{2,4})/i',
            'issue' => '/(?:Issuing Date|Issue Date|Date of Issue)[^\d]{0,20}(\d{1,2}[\/\-. ]\d{1,2}[\/\-. ]\d{2,4})/i',
            'expiry' => '/(?:Expiry Date|Expiration Date|Date of Expiry|Date of Expiry)[^\d]{0,20}(\d{1,2}[\/\-. ]\d{1,2}[\/\-. ]\d{2,4})/i',
        ] as $key => $pattern) {
            $dates[$key] = $this->matchFirst($pattern, $text);
        }

        return $dates;
    }

    private function extractAfterLabel(string $text, array $labels): ?string
    {
        foreach ($labels as $label) {
            if (preg_match('/' . preg_quote($label, '/') . '\s*[:\/]?\s*([^\n]+)/i', $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    private function normalizeKey(string $key): string
    {
        $key = Str::of($key)->lower()->replace(['#', '/', '-'], ' ')->squish()->replace(' ', '_')->toString();

        return match ($key) {
            'document_id', 'document_id_number', 'id_no', 'id_number', 'document_number' => 'document_number',
            'date_of_birth', 'birth_date', 'dob' => 'date_of_birth',
            'date_of_issue', 'issue_date' => 'date_of_issue',
            'expiration_date', 'expiry_date', 'date_of_expiry' => 'expiration_date',
            'first_name', 'middle_name', 'last_name', 'name', 'full_name', 'nationality', 'sex', 'gender', 'passport_number' => $key,
            default => $key,
        };
    }

    private function normalizeText(string $text): string
    {
        return preg_replace("/\r\n|\r/", "\n", $text) ?: '';
    }

    private function normalizeDate(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = trim(str_replace(['.', ' '], ['/', '/'], $value));

        foreach (['d/m/Y', 'd/m/y', 'Y-m-d', 'Y/m/d', 'd-m-Y', 'd-m-y'] as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $value);

                if ($date !== false) {
                    return $date->toDateString();
                }
            } catch (\Throwable) {
                // Try the next format before falling back to Carbon parser.
            }
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function mrzDate(string $value, bool $isBirthDate): ?string
    {
        if (! preg_match('/^\d{6}$/', $value)) {
            return null;
        }

        $year = (int) substr($value, 0, 2);
        $currentYear = (int) now()->format('y');
        $century = $isBirthDate && $year > $currentYear ? 1900 : 2000;

        return sprintf('%04d-%02d-%02d', $century + $year, (int) substr($value, 2, 2), (int) substr($value, 4, 2));
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

    private function normalizeNationality(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = trim(preg_replace('/[^A-Za-z ]/', '', $value) ?: $value);

        return match (Str::upper($value)) {
            'PAK' => 'Pakistani',
            'IND' => 'Indian',
            'ARE', 'UAE', 'UNITED ARAB EMIRATES' => 'Emirati',
            'GBR', 'UNITED KINGDOM' => 'British',
            'AUS', 'AUSTRALIA' => 'Australian',
            'SDN' => 'Sudanese',
            'SYR' => 'Syrian',
            'BRA' => 'Brazilian',
            'RUS' => 'Russian',
            'PHL' => 'Filipino',
            default => Str::title(Str::lower($value)),
        };
    }

    private function countryCodeToNationality(string $code): ?string
    {
        return $this->normalizeNationality($code);
    }

    private function matchFirst(string $pattern, string $text): ?string
    {
        return preg_match($pattern, $text, $matches) ? trim($matches[1] ?? $matches[0]) : null;
    }

    private function firstFilled(...$values): ?string
    {
        foreach ($values as $value) {
            if (filled(trim((string) $value))) {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function preferValue(?string $old, string $new): string
    {
        return strlen($new) > strlen((string) $old) ? $new : (string) $old;
    }

    private function cleanName(?string $name): ?string
    {
        if (blank($name)) {
            return null;
        }

        $name = preg_replace('/\s+/', ' ', str_replace(['<', ':'], ' ', $name));

        return trim(Str::title(Str::lower($name)));
    }
}
