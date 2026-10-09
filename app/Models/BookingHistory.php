<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingHistory extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'title',
        'description',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** Human-readable audit text; database UUIDs remain unchanged internally. */
    public function getDisplayDescriptionAttribute(): string
    {
        $description = $this->humanReadableChanges((string) $this->description);
        if (! str_contains((string)$this->title, 'Payment') && ! str_contains((string)$this->title, 'Approval') && ! str_contains((string)$this->title, 'Financial Request')) return $description;
        $index = 0;
        $approvalFirst = str_contains((string)$this->title, 'Approval') || str_contains((string)$this->title, 'Financial Request');
        $date = $this->created_at?->format('Ymd') ?? now()->format('Ymd');
        return preg_replace_callback('/\b[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\b/i', function ($match) use (&$index, $approvalFirst, $date) {
            $prefix = $approvalFirst && $index++ === 0 ? 'APR' : 'TRX';
            return $prefix.'-'.$date.'-'.strtoupper(substr(str_replace('-', '', $match[0]), 0, 8));
        }, $description) ?? $description;
    }

    private function humanReadableChanges(string $description): string
    {
        if (! preg_match('/^(.*?)\s*\|\s*Before:\s*(\{.*\})\s*\|\s*After:\s*(\{.*\})$/s', $description, $matches)) {
            return $description;
        }
        $before = json_decode($matches[2], true);
        $after = json_decode($matches[3], true);
        if (! is_array($before) || ! is_array($after)) return $description;

        $before = $this->flattenValues($before);
        $after = $this->flattenValues($after);
        $changes = [];
        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $field) {
            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;
            if ((string) $old === (string) $new) continue;
            $changes[] = '• '.$this->fieldLabel($field).': '.$this->displayValue($field, $old).' → '.$this->displayValue($field, $new);
        }

        return trim($matches[1]).($changes ? "\n".implode("\n", $changes) : "\nNo financial values changed.");
    }

    private function flattenValues(array $values, string $prefix = ''): array
    {
        $flat = [];
        foreach ($values as $key => $value) {
            $field = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value)) $flat += $this->flattenValues($value, $field);
            else $flat[$field] = $value;
        }
        return $flat;
    }

    private function fieldLabel(string $field): string
    {
        $field = str_replace('fees.', '', $field);
        return str($field)->replace('_', ' ')->headline()
            ->replace(['Vat', 'Dtcm', ' Id'], ['VAT', 'DTCM', ' ID'])->toString();
    }

    private function displayValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') return 'Not set';
        if (str_ends_with($field, 'vat_included')) return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
        if (preg_match('/(amount|fee|deposit|rent)/i', $field) && is_numeric($value)) return 'AED '.number_format((float) $value, 2);
        return (string) $value;
    }
}
