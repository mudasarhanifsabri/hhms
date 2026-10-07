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
        if ((float) $transaction->debit > 0) $query->where('debit', $transaction->debit)->where('credit', 0);
        else $query->where('credit', $transaction->credit)->where('debit', 0);
        return $query->with('bankTransfer')->where(fn ($q) => $q->whereNotNull('transaction_reference')
            ->orWhereHas('bankTransfer', fn ($transfer) => $transfer->whereNotNull('reference')))
            ->get()->filter(fn ($entry) => $this->validMatch($transaction, $entry))->values();
    }

    private function validMatch(BankStatementTransaction $transaction, AccountingEntry $entry): bool
    {
        $normalize = fn ($value) => strtoupper(preg_replace('/\s+/', '', trim((string) $value)));
        return $entry->paid_from_account_id === $transaction->bank_account_id
            && in_array($entry->approval_status, ['posted', 'approved', 'paid'], true)
            && $transaction->reference
            && ($normalize($transaction->reference) === $normalize($entry->transaction_reference)
                || $normalize($transaction->reference) === $normalize($entry->bankTransfer?->reference))
            && round((float) $entry->debit, 2) === round((float) $transaction->debit, 2)
            && round((float) $entry->credit, 2) === round((float) $transaction->credit, 2);
    }
}
