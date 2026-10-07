<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class BankStatementExcel
{
    public static function parse(string $path): array
    {
        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $data = $sheet->toArray(null, true, true, false);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['statement' => 'Could not read this Excel statement. Export it again from ADCB and retry.']);
        }

        $headerIndex = null;
        $headers = [];
        foreach (array_slice($data, 0, 75, true) as $index => $row) {
            $keys = array_map(fn ($value) => self::key((string) $value), $row);
            if (in_array('date', $keys, true) && (in_array('debitamount', $keys, true) || in_array('creditamount', $keys, true))) {
                $headerIndex = $index;
                $headers = $keys;
                break;
            }
        }
        if ($headerIndex === null) {
            throw ValidationException::withMessages(['statement' => 'Excel statement needs Date, Bank Reference No, Debit Amount, and Credit Amount columns.']);
        }

        $date = self::column($headers, ['date', 'transactiondate', 'valuedate', 'postingdate']);
        $reference = self::column($headers, ['bankreferenceno', 'bankreference', 'transactionreference', 'referenceno', 'reference']);
        $description = self::column($headers, ['description', 'narration', 'transactiondetails', 'paymentdetails']);
        $debit = self::column($headers, ['debitamount', 'debit', 'withdrawal']);
        $credit = self::column($headers, ['creditamount', 'credit', 'deposit']);

        $rows = [];
        foreach (array_slice($data, $headerIndex + 1, null, true) as $index => $cells) {
            if (collect($cells)->every(fn ($value) => trim((string) $value) === '')) continue;
            $line = $index + 1;
            $rawDate = $cells[$date] ?? null;
            $parsedDate = self::date($rawDate);
            if (! $parsedDate) continue;
            $out = self::money($cells[$debit] ?? null, $line);
            $in = self::money($cells[$credit] ?? null, $line);
            if (($out > 0 && $in > 0) || ($out <= 0 && $in <= 0)) {
                throw ValidationException::withMessages(['statement' => "Excel row {$line} must have exactly one nonzero debit or credit amount."]);
            }
            $ref = trim((string) ($cells[$reference] ?? ''));
            if (strlen($ref) > 255) throw ValidationException::withMessages(['statement' => "Reference is too long on Excel row {$line}."]);
            $rows[] = [
                'row_number' => $line,
                'transaction_date' => $parsedDate,
                'reference' => $ref !== '' && $ref !== '-' ? $ref : null,
                'description' => trim((string) ($cells[$description] ?? '')) ?: null,
                'debit' => $out,
                'credit' => $in,
            ];
            if (count($rows) > 10000) throw ValidationException::withMessages(['statement' => 'Maximum 10,000 transactions per upload.']);
        }
        if (! $rows) throw ValidationException::withMessages(['statement' => 'No transactions found in the Excel statement.']);

        return $rows;
    }

    private static function key(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($value));
    }

    private static function column(array $headers, array $names): ?int
    {
        foreach ($names as $name) {
            $index = array_search($name, $headers, true);
            if ($index !== false) return $index;
        }
        return null;
    }

    private static function date(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) return $value->format('Y-m-d');
        if (is_numeric($value) && (float) $value > 0) return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        return BankStatementCsv::parseDate(trim((string) $value));
    }

    private static function money(mixed $value, int $line): float
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-') return 0;
        $clean = str_replace([',', ' ', 'AED'], '', strtoupper($value));
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $clean)) {
            throw ValidationException::withMessages(['statement' => "Invalid amount on Excel row {$line}."]);
        }
        return round((float) $clean, 2);
    }
}
