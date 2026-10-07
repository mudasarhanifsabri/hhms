<?php

namespace App\Support;

use DateTimeImmutable;
use Illuminate\Validation\ValidationException;

class BankStatementCsv
{
    public static function parse(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) throw ValidationException::withMessages(['statement' => 'Could not read the statement.']);
        try {
            $sample = fgets($handle) ?: '';
            rewind($handle);
            $delimiter = substr_count($sample, ';') > substr_count($sample, ',') ? ';' : ',';
            $headers = fgetcsv($handle, 0, $delimiter);
            if (! $headers) throw ValidationException::withMessages(['statement' => 'The CSV is empty.']);
            $headers = array_map(fn ($value) => self::key(preg_replace('/^\xEF\xBB\xBF/', '', (string) $value)), $headers);
            $date = self::column($headers, ['date', 'transactiondate', 'valuedate', 'postingdate', 'bookdate']);
            $reference = self::column($headers, ['reference', 'transactionreference', 'transactionref', 'refno', 'referenceno', 'transactionid', 'bankreference', 'paymentreference']);
            $description = self::column($headers, ['description', 'details', 'narration', 'transactiondetails', 'particulars', 'memo']);
            $debit = self::column($headers, ['debit', 'debitamount', 'withdrawal', 'withdrawals', 'moneyout', 'paidout']);
            $credit = self::column($headers, ['credit', 'creditamount', 'deposit', 'deposits', 'moneyin', 'paidin']);
            $amount = self::column($headers, ['amount', 'transactionamount']);
            $direction = self::column($headers, ['type', 'transactiontype', 'debitcredit', 'drcr']);
            if ($date === null || ($debit === null && $credit === null && $amount === null)) {
                throw ValidationException::withMessages(['statement' => 'CSV needs a date and either debit/credit columns or a signed amount column.']);
            }
            $rows = [];
            $line = 1;
            while (($cells = fgetcsv($handle, 0, $delimiter)) !== false) {
                $line++;
                if (count($cells) === 1 && trim((string) $cells[0]) === '') continue;
                $value = fn (?int $index) => $index === null ? '' : trim((string) ($cells[$index] ?? ''));
                $rawDate = $value($date);
                $parsedDate = self::parseDate($rawDate);
                if (! $parsedDate) throw ValidationException::withMessages(['statement' => "Invalid date on CSV row {$line}: {$rawDate}"]);
                $out = self::money($value($debit), $line);
                $in = self::money($value($credit), $line);
                if ($debit === null && $credit === null) {
                    $signed = self::money($value($amount), $line, true);
                    $kind = strtolower($value($direction));
                    if (in_array($kind, ['debit', 'dr', 'withdrawal', 'out'], true)) $signed = -abs($signed);
                    if (in_array($kind, ['credit', 'cr', 'deposit', 'in'], true)) $signed = abs($signed);
                    $out = max(0, -$signed);
                    $in = max(0, $signed);
                }
                if (($out > 0 && $in > 0) || ($out <= 0 && $in <= 0)) {
                    throw ValidationException::withMessages(['statement' => "CSV row {$line} must have exactly one nonzero debit or credit amount."]);
                }
                if (strlen($value($reference)) > 255) throw ValidationException::withMessages(['statement' => "Reference is too long on CSV row {$line}."]);
                $rows[] = ['row_number' => $line, 'transaction_date' => $parsedDate, 'reference' => $value($reference) ?: null,
                    'description' => $value($description) ?: null, 'debit' => $out, 'credit' => $in];
                if (count($rows) > 10000) throw ValidationException::withMessages(['statement' => 'Maximum 10,000 transactions per upload.']);
            }
            if (! $rows) throw ValidationException::withMessages(['statement' => 'No transactions found in the CSV.']);
            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private static function key(string $value): string { return preg_replace('/[^a-z0-9]/', '', strtolower($value)); }
    private static function column(array $headers, array $names): ?int
    {
        foreach ($names as $name) { $index = array_search($name, $headers, true); if ($index !== false) return $index; }
        return null;
    }
    public static function parseDate(string $value): ?string
    {
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd-M-Y', 'd M Y', 'm/d/Y', 'Y/m/d', 'Y-m-d H:i:s', 'd/m/Y H:i:s'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            if ($date && $date->format($format) === $value) return $date->format('Y-m-d');
        }
        return null;
    }
    private static function money(string $value, int $line, bool $signed = false): float
    {
        if ($value === '') return 0;
        $clean = str_replace([',', ' ', 'AED'], '', strtoupper($value));
        if (preg_match('/^\((\d+(?:\.\d{1,2})?)\)$/', $clean, $m)) $clean = '-'.$m[1];
        if (! preg_match('/^-?\d+(?:\.\d{1,2})?$/', $clean) || (! $signed && (float) $clean < 0)) {
            throw ValidationException::withMessages(['statement' => "Invalid amount on CSV row {$line}."]);
        }
        return round((float) $clean, 2);
    }
}
