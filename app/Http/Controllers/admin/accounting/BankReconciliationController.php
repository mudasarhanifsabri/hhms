<?php

namespace App\Http\Controllers\admin\accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\BankStatementImport;
use App\Models\BankStatementTransaction;
use App\Support\BankStatementCsv;
use App\Support\BankStatementExcel;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BankReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $accounts = BankAccount::where('type', 'bank')->orderBy('name')->get();
        $imports = BankStatementImport::with('account')->withCount(['transactions', 'transactions as confirmed_count' => fn ($q) => $q->where('status', 'confirmed')])
            ->latest()->paginate(15);
        return view('admin.accounting.bank-reconciliation.index', compact('accounts', 'imports'));
    }

    public function systemTransactions(Request $request)
    {
        $accounts = BankAccount::where('type', 'bank')->orderBy('name')->get();
        $entries = $this->systemTransactionsQuery($request)
            ->with(['paidFromAccount', 'bankTransfer', 'property.building', 'booking.property.building', 'bankStatementTransaction.import'])
            ->withSum('bookingInvoicePayments', 'amount')
            ->orderByDesc('entry_date')->orderByDesc('created_at')->paginate(100)->withQueryString();

        return view('admin.accounting.bank-reconciliation.system-transactions', compact('accounts', 'entries'));
    }

    public function exportSystemTransactions(Request $request)
    {
        $entries = $this->systemTransactionsQuery($request)
            ->with(['paidFromAccount', 'bankTransfer', 'property.building', 'booking.property.building', 'bankStatementTransaction.import'])
            ->withSum('bookingInvoicePayments', 'amount')
            ->orderBy('entry_date')->orderBy('created_at')->get();

        return response()->streamDownload(function () use ($entries) {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Date', 'Entry No', 'Reference No', 'Purpose', 'Category', 'Description', 'Booking', 'Guest', 'Unit', 'Bank Account', 'Debit AED', 'Credit AED', 'Bank Match']);
            foreach ($entries as $entry) {
                $safe = fn ($value) => preg_match('/^[=+\-@]/', (string) $value) ? "'".$value : $value;
                fputcsv($handle, [
                    $entry->entry_date?->format('Y-m-d'), $safe($entry->entry_no), $safe($entry->transaction_reference ?: $entry->bankTransfer?->reference),
                    AccountingEntry::TYPES[$entry->type] ?? ucfirst((string) $entry->type), $safe($entry->category), $safe($entry->description),
                    $safe($entry->booking?->booking_reference), $safe($entry->booking?->guest_name), $safe($entry->property?->name ?? $entry->booking?->property?->name),
                    $safe($entry->paidFromAccount?->name), number_format($entry->bank_debit, 2, '.', ''), number_format($entry->bank_credit, 2, '.', ''),
                    $entry->bankStatementTransaction ? 'Matched' : 'Not matched',
                ]);
            }
            fclose($handle);
        }, 'system-bank-transactions-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function upload(Request $request)
    {
        $data = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'bank' => 'required|in:wio,adcb',
            'statement' => 'required|file|mimes:csv,txt,xls,xlsx|max:15360',
        ]);
        $account = BankAccount::findOrFail($data['bank_account_id']);
        if ($account->type !== 'bank') throw ValidationException::withMessages(['bank_account_id' => 'Select a bank account.']);
        $bankName = strtolower((string) $account->bank_name.' '.$account->name);
        if (! str_contains($bankName, $data['bank'])) {
            throw ValidationException::withMessages(['bank_account_id' => 'Selected account does not appear to belong to this bank.']);
        }
        $file = $request->file('statement');
        $hash = hash_file('sha256', $file->getRealPath());
        if (BankStatementImport::where('bank_account_id', $account->id)->where('file_hash', $hash)->exists()) {
            throw ValidationException::withMessages(['statement' => 'This file has already been imported for this account.']);
        }
        $extension = strtolower($file->getClientOriginalExtension());
        $rows = in_array($extension, ['xls', 'xlsx'], true)
            ? BankStatementExcel::parse($file->getRealPath())
            : BankStatementCsv::parse($file->getRealPath());
        $import = DB::transaction(function () use ($account, $data, $file, $hash, $rows) {
            $import = BankStatementImport::create(['bank_account_id' => $account->id, 'bank' => $data['bank'],
                'filename' => $file->getClientOriginalName(), 'file_hash' => $hash, 'uploaded_by' => auth()->id()]);
            foreach ($rows as $row) $import->transactions()->create([...$row, 'bank_account_id' => $account->id]);
            return $import;
        });
        return redirect()->route('admin.accounting.bank-reconciliation.show', $import)->with('success', count($rows).' bank transactions imported for review.');
    }

    public function show(BankStatementImport $import)
    {
        $import->load('account');
        $transactions = $import->transactions()->with('entry')->orderBy('row_number')->paginate(100);
        $suggestions = [];
        foreach ($transactions as $transaction) {
            if ($transaction->status === 'confirmed') continue;
            $suggestions[$transaction->id] = $this->candidates($transaction)->take(10);
        }
        return view('admin.accounting.bank-reconciliation.show', compact('import', 'transactions', 'suggestions'));
    }

    public function confirm(Request $request, BankStatementTransaction $transaction)
    {
        $data = $request->validate(['accounting_entry_id' => 'required|exists:accounting_entries,id']);
        DB::transaction(function () use ($transaction, $data) {
            $transaction = BankStatementTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if ($transaction->status === 'confirmed') throw ValidationException::withMessages(['accounting_entry_id' => 'This bank row is already confirmed.']);
            $entry = AccountingEntry::whereKey($data['accounting_entry_id'])->lockForUpdate()->firstOrFail();
            if (! $this->validMatch($transaction, $entry)) {
                throw ValidationException::withMessages(['accounting_entry_id' => 'Account, reference, direction, or amount does not match the bank transaction.']);
            }
            if (BankStatementTransaction::where('accounting_entry_id', $entry->id)->exists()) {
                throw ValidationException::withMessages(['accounting_entry_id' => 'This accounting entry was already confirmed against another bank row.']);
            }
            $transaction->update(['status' => 'confirmed', 'accounting_entry_id' => $entry->id,
                'confirmed_by' => auth()->id(), 'confirmed_at' => now()]);
        });
        return back()->with('success', 'Transaction confirmed.');
    }

    public function confirmAll(BankStatementImport $import)
    {
        $confirmed = 0;
        DB::transaction(function () use ($import, &$confirmed) {
            foreach ($import->transactions()->where('status', '!=', 'confirmed')->orderBy('row_number')->lockForUpdate()->get() as $transaction) {
                $matches = $this->candidates($transaction);
                if ($matches->count() !== 1) continue;
                $entry = AccountingEntry::whereKey($matches->first()->id)->lockForUpdate()->first();
                if (! $entry || ! $this->validMatch($transaction, $entry)) continue;
                if (BankStatementTransaction::where('accounting_entry_id', $entry->id)->exists()) continue;
                $transaction->update(['status' => 'confirmed', 'accounting_entry_id' => $entry->id,
                    'confirmed_by' => auth()->id(), 'confirmed_at' => now()]);
                $confirmed++;
            }
        });

        return back()->with('success', $confirmed.' unique reference and amount matches confirmed. Remaining rows require review.');
    }

    private function candidates(BankStatementTransaction $transaction)
    {
        if (! $transaction->reference) return collect();
        $query = AccountingEntry::where('paid_from_account_id', $transaction->bank_account_id)
            ->whereIn('approval_status', ['posted', 'approved', 'paid'])
            ->whereNotIn('id', BankStatementTransaction::whereNotNull('accounting_entry_id')->select('accounting_entry_id'));
        return $query->with('bankTransfer')->withSum('bookingInvoicePayments', 'amount')->where(fn ($q) => $q->whereNotNull('transaction_reference')
            ->orWhereHas('bankTransfer', fn ($transfer) => $transfer->whereNotNull('reference')))
            ->get()->filter(fn ($entry) => $this->validMatch($transaction, $entry))->values();
    }

    private function systemTransactionsQuery(Request $request): Builder
    {
        $query = AccountingEntry::query()
            ->whereNotNull('paid_from_account_id')
            ->whereIn('approval_status', ['posted', 'approved', 'paid'])
            ->whereNot(fn (Builder $q) => $q->where('category', 'security_deposit')->where('description', 'like', 'Deposit allocation from %'))
            ->where(fn (Builder $q) => $q->where('debit', '>', 0)->orWhere('credit', '>', 0)->orWhereHas('bookingInvoicePayments'));

        if ($request->filled('bank_account_id')) $query->where('paid_from_account_id', $request->string('bank_account_id'));
        if ($request->filled('from')) $query->whereDate('entry_date', '>=', $request->date('from'));
        if ($request->filled('to')) $query->whereDate('entry_date', '<=', $request->date('to'));
        if ($request->input('match_status') === 'matched') {
            $query->whereIn('id', BankStatementTransaction::whereNotNull('accounting_entry_id')->select('accounting_entry_id'));
        } elseif ($request->input('match_status') === 'unmatched') {
            $query->whereNotIn('id', BankStatementTransaction::whereNotNull('accounting_entry_id')->select('accounting_entry_id'));
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $q) use ($search) {
                $q->where('transaction_reference', 'like', "%{$search}%")
                    ->orWhere('entry_no', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhereHas('booking', fn (Builder $booking) => $booking->where('booking_reference', 'like', "%{$search}%")->orWhere('guest_name', 'like', "%{$search}%"))
                    ->orWhereHas('bankTransfer', fn (Builder $transfer) => $transfer->where('reference', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    private function validMatch(BankStatementTransaction $transaction, AccountingEntry $entry): bool
    {
        $normalize = fn ($value) => strtoupper(preg_replace('/\s+/', '', trim((string) $value)));
        return $entry->paid_from_account_id === $transaction->bank_account_id
            && in_array($entry->approval_status, ['posted', 'approved', 'paid'], true)
            && $transaction->reference
            && ($normalize($transaction->reference) === $normalize($entry->transaction_reference)
                || $normalize($transaction->reference) === $normalize($entry->bankTransfer?->reference))
            && round($entry->bank_debit, 2) === round((float) $transaction->debit, 2)
            && round($entry->bank_credit, 2) === round((float) $transaction->credit, 2);
    }
}
