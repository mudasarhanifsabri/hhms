<?php

namespace App\Http\Controllers\admin\accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingAccount;
use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\Expense;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\User;
use App\Models\UtilityAccount;
use App\Models\UtilityBill;
use App\Models\Vendor;
use App\Support\MediaStorage;
use App\Support\PdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AccountingController extends Controller
{
    public function dashboard(Request $request)
    {
        $month = $this->month($request);
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $income = AccountingEntry::whereBetween('entry_date', [$from, $to])->sum('credit');
        $expenses = AccountingEntry::whereBetween('entry_date', [$from, $to])->sum('debit');
        $todayIncome = AccountingEntry::whereDate('entry_date', today())->sum('credit');
        $todayExpenses = AccountingEntry::whereDate('entry_date', today())->sum('debit');
        $cashBalance = BankAccount::where('type', 'cash')->sum('current_balance');
        $bankBalance = BankAccount::where('type', 'bank')->sum('current_balance');
        $accountsReceivable = BookingInvoice::where('status', 'unpaid')->sum('total_amount');
        $accountsPayable = Expense::whereIn('approval_status', ['pending', 'approved'])->sum('gross_amount');
        $ownerPayables = LandlordAccountEntry::selectRaw("sum(case when direction = 'credit' then amount else -amount end) as balance")->value('balance') ?? 0;
        $vatOutput = AccountingEntry::where('type', 'income')->whereBetween('entry_date', [$from, $to])->sum('vat_amount');
        $vatInput = AccountingEntry::whereIn('type', ['expense', 'utility'])->whereBetween('entry_date', [$from, $to])->sum('vat_amount');
        $monthlyProfit = $income - $expenses;
        $occupancyRevenue = Booking::whereBetween('check_in', [$from, $to])->sum('rent_amount');
        $utilityExpenses = UtilityBill::whereBetween('bill_month', [$from, $to])->sum('total_amount');
        $outstandingUtilities = UtilityBill::whereIn('status', ['outstanding', 'overdue'])->sum('total_amount');
        $recentEntries = AccountingEntry::with(['property', 'landlord', 'booking', 'accountingAccount'])->latest('entry_date')->limit(8)->get();
        $upcomingUtilityBills = UtilityBill::with(['account', 'property'])
            ->whereIn('status', ['outstanding', 'overdue'])
            ->orderBy('due_date')
            ->limit(8)
            ->get();

        return view('admin.accounting.dashboard', compact(
            'month',
            'income',
            'expenses',
            'todayIncome',
            'todayExpenses',
            'cashBalance',
            'bankBalance',
            'accountsReceivable',
            'accountsPayable',
            'ownerPayables',
            'vatOutput',
            'vatInput',
            'monthlyProfit',
            'occupancyRevenue',
            'utilityExpenses',
            'outstandingUtilities',
            'recentEntries',
            'upcomingUtilityBills'
        ));
    }

    public function ledger(Request $request)
    {
        $entries = AccountingEntry::with(['property', 'landlord', 'booking', 'creator'])
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))
            ->when($request->filled('property_id'), fn ($query) => $query->where('property_id', $request->input('property_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('entry_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('entry_date', '<=', $request->input('date_to')))
            ->latest('entry_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.accounting.ledger', $this->sharedData() + compact('entries'));
    }

    public function storeEntry(Request $request)
    {
        $data = $request->validate([
            'entry_date' => 'required|date',
            'type' => 'required|string|max:50',
            'category' => 'nullable|string|max:100',
            'accounting_account_id' => 'nullable|exists:accounting_accounts,id',
            'description' => 'nullable|string|max:1000',
            'property_id' => 'nullable|exists:properties,id',
            'landlord_id' => 'nullable|exists:users,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'paid_from_account_id' => 'nullable|exists:bank_accounts,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'payment_method' => 'nullable|string|max:100',
            'transaction_reference' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $debit = (float) ($data['debit'] ?? 0);
        $credit = (float) ($data['credit'] ?? 0);
        $gross = max($debit, $credit);
        $vatRate = (float) ($data['vat_rate'] ?? 5);
        $vat = round($gross * ($vatRate / 100), 2);
        $attachment = $this->upload($request, 'attachment', 'accounting_attachments');

        AccountingEntry::create([
            ...$data,
            'entry_no' => $this->nextNumber('JE', AccountingEntry::class, 'entry_no'),
            'debit' => $debit,
            'credit' => $credit,
            'vat_rate' => $vatRate,
            'vat_amount' => $vat,
            'net_amount' => $gross,
            'gross_amount' => $gross + $vat,
            'attachment' => $attachment,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Accounting entry posted.');
    }

    public function expenses(Request $request)
    {
        $expenses = Expense::with(['property.building', 'landlord', 'booking', 'vendor', 'paidFromAccount'])
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->input('category')))
            ->when($request->filled('property_id'), fn ($query) => $query->where('property_id', $request->input('property_id')))
            ->when($request->filled('approval_status'), fn ($query) => $query->where('approval_status', $request->input('approval_status')))
            ->latest('expense_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.accounting.expenses', $this->sharedData() + compact('expenses'));
    }

    public function importExpenses()
    {
        return view('admin.accounting.expense-import', $this->sharedData() + [
            'previewRows' => collect(),
            'sourcePath' => null,
            'sourceType' => null,
            'fileName' => null,
        ]);
    }

    public function previewExpenseImport(Request $request)
    {
        $data = $request->validate([
            'expense_file' => 'required|file|mimes:csv,txt,xlsx,xls,pdf|max:20480',
        ]);

        $file = $data['expense_file'];
        $sourceType = strtolower($file->getClientOriginalExtension());
        $sourcePath = MediaStorage::store($file, 'expense_imports');
        $rows = collect($this->parseExpenseImport($file->getRealPath(), $sourceType, $file->getClientOriginalName()))
            ->map(function (array $row, int $index) use ($sourceType, $sourcePath) {
                $row['row_key'] = $index + 1;
                $row['source_type'] = $sourceType;
                $row['source_file'] = $sourcePath;
                $row['status'] = $this->importDuplicateExists($row) ? 'duplicate' : ($row['needs_review'] ? 'needs_review' : 'new');

                return $row;
            });

        return view('admin.accounting.expense-import', $this->sharedData() + [
            'previewRows' => $rows,
            'sourcePath' => $sourcePath,
            'sourceType' => $sourceType,
            'fileName' => $file->getClientOriginalName(),
        ]);
    }

    public function confirmExpenseImport(Request $request)
    {
        $data = $request->validate([
            'rows' => 'required|string',
            'source_file' => 'nullable|string|max:1000',
            'source_type' => 'nullable|string|max:20',
            'default_property_id' => 'nullable|exists:properties,id',
            'default_category' => 'nullable|string|max:100',
            'default_paid_from_account_id' => 'nullable|exists:bank_accounts,id',
            'default_owner_billable' => 'nullable|boolean',
        ]);

        $rows = json_decode($data['rows'], true) ?: [];
        $created = 0;
        $duplicates = 0;

        foreach ($rows as $row) {
            if (($row['status'] ?? null) === 'duplicate' || $this->importDuplicateExists($row)) {
                $duplicates++;
                continue;
            }

            $property = ! empty($data['default_property_id']) ? Property::find($data['default_property_id']) : null;
            $gross = (float) ($row['gross_amount'] ?? 0);
            $vat = (float) ($row['vat_amount'] ?? 0);
            $net = max(0, (float) ($row['net_amount'] ?? ($gross - $vat)));

            Expense::create(array_merge([
                'expense_no' => $this->nextNumber('EXP', Expense::class, 'expense_no'),
                'expense_date' => $row['expense_date'] ?: now()->toDateString(),
                'category' => $data['default_category'] ?: ($row['category'] ?: 'other'),
                'supplier' => $row['supplier'] ?: null,
                'property_id' => $property?->id,
                'landlord_id' => $property?->landlord_id,
                'responsibility' => 'company',
                'owner_billable' => $request->boolean('default_owner_billable'),
                'paid_from_account_id' => $data['default_paid_from_account_id'] ?? null,
                'net_amount' => $net,
                'vat_rate' => (float) ($row['vat_rate'] ?? 5),
                'vat_amount' => $vat,
                'gross_amount' => $gross > 0 ? $gross : $net + $vat,
                'payment_method' => $row['payment_method'] ?: null,
                'transaction_reference' => $row['transaction_reference'] ?: null,
                'approval_status' => 'draft',
                'description' => $row['description'] ?: 'Imported expense draft',
                'created_by' => auth()->id(),
            ], $this->expenseImportTrackingPayload([
                'import_source_type' => $data['source_type'] ?? ($row['source_type'] ?? null),
                'import_source_file' => $data['source_file'] ?? ($row['source_file'] ?? null),
                'imported_transaction_id' => $row['imported_transaction_id'] ?: null,
                'imported_payload' => $row['raw'] ?? $row,
                'needs_review' => (bool) ($row['needs_review'] ?? false),
            ])));
            $created++;
        }

        return redirect()
            ->route('admin.accounting.expenses', ['approval_status' => 'draft'])
            ->with('success', "{$created} draft expense(s) imported. {$duplicates} duplicate row(s) skipped.");
    }

    public function storeExpense(Request $request)
    {
        $data = $request->validate([
            'expense_date' => 'required|date',
            'category' => 'required|string|max:100',
            'vendor_id' => 'nullable|exists:vendors,id',
            'supplier' => 'nullable|string|max:255',
            'property_id' => 'nullable|exists:properties,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'responsibility' => 'required|in:company,owner,tenant_guest',
            'paid_from_account_id' => 'nullable|exists:bank_accounts,id',
            'owner_billable' => 'nullable|boolean',
            'net_amount' => 'required|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'vat_included' => 'nullable|boolean',
            'payment_method' => 'nullable|string|max:100',
            'transaction_reference' => 'nullable|string|max:255',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'invoice' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'approval_status' => 'nullable|in:draft,pending,reviewed,approved,paid,rejected',
            'description' => 'nullable|string|max:1000',
        ]);

        $property = ! empty($data['property_id']) ? Property::find($data['property_id']) : null;
        $amounts = $this->expenseAmounts((float) $data['net_amount'], (float) ($data['vat_rate'] ?? 5), $request->boolean('vat_included'));
        $receipt = $this->upload($request, 'receipt', 'expense_receipts');
        $invoice = $this->upload($request, 'invoice', 'expense_invoices');

        $expense = Expense::create([
            ...$data,
            'expense_no' => $this->nextNumber('EXP', Expense::class, 'expense_no'),
            'landlord_id' => $property?->landlord_id,
            'owner_billable' => $request->boolean('owner_billable') || $data['responsibility'] === 'owner',
            'net_amount' => $amounts['net'],
            'vat_rate' => $amounts['rate'],
            'vat_amount' => $amounts['vat'],
            'gross_amount' => $amounts['gross'],
            'receipt_path' => $receipt,
            'invoice_path' => $invoice,
            'approval_status' => $data['approval_status'] ?? 'approved',
            'created_by' => auth()->id(),
        ]);

        if ($this->expenseShouldPost($expense)) {
            $entry = $this->postExpenseEntry($expense);
            $expense->update(['accounting_entry_id' => $entry->id]);
            $this->syncOwnerDebit($expense);
        }

        $message = $this->expenseShouldPost($expense)
            ? 'Expense recorded and posted to accounting.'
            : 'Expense saved as draft/review item. It is not posted yet.';

        return back()->with('success', $message);
    }

    public function updateExpense(Request $request, Expense $expense)
    {
        abort_unless($this->canModifyApprovedExpense($expense), 403, 'Only the super admin can edit an approved expense.');

        $data = $request->validate([
            'expense_date' => 'required|date',
            'category' => 'required|string|max:100',
            'vendor_id' => 'nullable|exists:vendors,id',
            'supplier' => 'nullable|string|max:255',
            'property_id' => 'nullable|exists:properties,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'responsibility' => 'required|in:company,owner,tenant_guest',
            'paid_from_account_id' => 'nullable|exists:bank_accounts,id',
            'owner_billable' => 'nullable|boolean',
            'net_amount' => 'required|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'vat_included' => 'nullable|boolean',
            'payment_method' => 'nullable|string|max:100',
            'transaction_reference' => 'nullable|string|max:255',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'invoice' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'approval_status' => 'required|in:draft,pending,reviewed,approved,paid,rejected',
            'description' => 'nullable|string|max:1000',
        ]);

        $property = ! empty($data['property_id']) ? Property::find($data['property_id']) : null;
        $amounts = $this->expenseAmounts((float) $data['net_amount'], (float) ($data['vat_rate'] ?? 5), $request->boolean('vat_included'));
        $receipt = $this->upload($request, 'receipt', 'expense_receipts');
        $invoice = $this->upload($request, 'invoice', 'expense_invoices');

        $expense->update([
            ...$data,
            'landlord_id' => $property?->landlord_id,
            'owner_billable' => $request->boolean('owner_billable') || $data['responsibility'] === 'owner',
            'net_amount' => $amounts['net'],
            'vat_rate' => $amounts['rate'],
            'vat_amount' => $amounts['vat'],
            'gross_amount' => $amounts['gross'],
            'receipt_path' => $receipt ?? $expense->receipt_path,
            'invoice_path' => $invoice ?? $expense->invoice_path,
            'needs_review' => in_array($data['approval_status'], ['draft', 'pending'], true) ? $expense->needs_review : false,
        ]);

        if ($this->expenseShouldPost($expense)) {
            if (! $expense->accounting_entry_id) {
                $entry = $this->postExpenseEntry($expense);
                $expense->update(['accounting_entry_id' => $entry->id]);
            }
        }

        $this->syncOwnerDebit($expense);

        return back()->with('success', 'Expense updated.');
    }

    public function approveExpense(Expense $expense)
    {
        if ($expense->approval_status === 'rejected') {
            return back()->with('error', 'Rejected expenses cannot be approved.');
        }

        $expense->update(array_merge(
            ['approval_status' => 'approved'],
            Schema::hasColumn('expenses', 'needs_review') ? ['needs_review' => false] : []
        ));

        if (! $expense->accounting_entry_id) {
            $entry = $this->postExpenseEntry($expense);
            $expense->update(['accounting_entry_id' => $entry->id]);
        }

        $this->syncOwnerDebit($expense);

        return back()->with('success', 'Expense approved and posted.');
    }

    public function destroyExpense(Expense $expense)
    {
        abort_unless($this->canModifyApprovedExpense($expense), 403, 'Only the super admin can delete an approved expense.');

        $landlordId = $expense->landlord_id;
        $expenseNo = $expense->expense_no;

        DB::transaction(function () use ($expense, $landlordId, $expenseNo) {
            AccountingEntry::where('expense_id', $expense->id)->delete();

            if ($landlordId && $expenseNo) {
                LandlordAccountEntry::where('landlord_id', $landlordId)
                    ->where('reference', $expenseNo)
                    ->delete();
            }

            UtilityBill::where('expense_id', $expense->id)->update([
                'expense_id' => null,
                'accounting_entry_id' => null,
                'status' => 'outstanding',
            ]);

            $expense->delete();
        });

        if ($landlordId) {
            LandlordAccountEntry::recalculateBalancesFor($landlordId);
        }

        return back()->with('success', 'Expense deleted successfully.');
    }

    public function utilities(Request $request)
    {
        $month = $this->month($request);
        $accounts = UtilityAccount::with(['property.building'])
            ->when($request->filled('property_id'), fn ($query) => $query->where('property_id', $request->input('property_id')))
            ->orderBy('utility_type')
            ->get();
        $bills = UtilityBill::with(['account', 'property', 'landlord'])
            ->whereBetween('bill_month', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get()
            ->keyBy(fn ($bill) => $bill->property_id . '|' . $bill->account?->utility_type);
        $properties = Property::with(['building', 'utilityAccounts'])->orderBy('name')->get();

        return view('admin.accounting.utilities', $this->sharedData() + compact('accounts', 'bills', 'properties', 'month'));
    }

    public function storeUtilityAccount(Request $request)
    {
        $data = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'utility_type' => 'required|string|max:50',
            'responsibility' => 'required|in:owner,company,tenant_guest',
            'supplier' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'portal_password' => 'nullable|string|max:255',
            'contract_number' => 'nullable|string|max:255',
            'connection_status' => 'required|string|max:50',
            'connection_start_date' => 'nullable|date',
            'contract_expiry_date' => 'nullable|date',
            'billing_day' => 'nullable|integer|min:1|max:31',
            'notes' => 'nullable|string|max:1000',
        ]);

        $portalPassword = $data['portal_password'] ?? null;
        unset($data['portal_password']);

        $account = UtilityAccount::updateOrCreate(
            ['property_id' => $data['property_id'], 'utility_type' => $data['utility_type']],
            $data
        );

        if (filled($portalPassword)) {
            $account->portal_password = $portalPassword;
            $account->save();
        }

        return back()->with('success', 'Utility account saved.');
    }

    public function storeUtilityBill(Request $request)
    {
        $data = $request->validate([
            'utility_account_id' => 'required|exists:utility_accounts,id',
            'bill_month' => 'required|date',
            'bill_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'bill_amount' => 'required|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'booking_id' => 'nullable|exists:bookings,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $account = UtilityAccount::with('property')->findOrFail($data['utility_account_id']);
        $net = (float) $data['bill_amount'];
        $vatRate = (float) ($data['vat_rate'] ?? 5);
        $vat = round($net * ($vatRate / 100), 2);

        UtilityBill::create([
            ...$data,
            'property_id' => $account->property_id,
            'landlord_id' => $account->property?->landlord_id,
            'bill_month' => Carbon::parse($data['bill_month'])->startOfMonth(),
            'responsibility' => $account->responsibility,
            'vat_rate' => $vatRate,
            'vat_amount' => $vat,
            'total_amount' => $net + $vat,
            'status' => 'outstanding',
        ]);

        return back()->with('success', 'Utility bill recorded.');
    }

    public function payUtilityBill(Request $request, UtilityBill $bill)
    {
        $data = $request->validate([
            'paid_at' => 'required|date',
            'payment_method' => 'nullable|string|max:100',
            'transaction_reference' => 'nullable|string|max:255',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'owner_paid' => 'nullable|boolean',
        ]);

        $receipt = $this->upload($request, 'receipt', 'utility_receipts');
        $ownerPaid = $request->boolean('owner_paid') || $bill->responsibility === 'owner';
        $bill->update([
            'paid_at' => $data['paid_at'],
            'paid_by' => auth()->id(),
            'payment_method' => $data['payment_method'] ?? null,
            'transaction_reference' => $data['transaction_reference'] ?? null,
            'receipt_path' => $receipt ?? $bill->receipt_path,
            'status' => $ownerPaid ? 'owner_paid' : 'paid',
        ]);

        if (! $ownerPaid) {
            $expense = Expense::create([
                'expense_no' => $this->nextNumber('EXP', Expense::class, 'expense_no'),
                'expense_date' => Carbon::parse($data['paid_at'])->toDateString(),
                'category' => $bill->account?->utility_type ?? 'utility',
                'supplier' => $bill->account?->supplier,
                'property_id' => $bill->property_id,
                'landlord_id' => $bill->landlord_id,
                'booking_id' => $bill->booking_id,
                'responsibility' => 'company',
                'owner_billable' => true,
                'net_amount' => $bill->bill_amount,
                'vat_rate' => $bill->vat_rate,
                'vat_amount' => $bill->vat_amount,
                'gross_amount' => $bill->total_amount,
                'payment_method' => $data['payment_method'] ?? null,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'receipt_path' => $receipt,
                'approval_status' => 'paid',
                'description' => $bill->account?->type_label . ' bill for ' . $bill->bill_month?->format('M Y'),
                'created_by' => auth()->id(),
            ]);
            $entry = $this->postExpenseEntry($expense, 'utility', $bill->id);
            $expense->update(['accounting_entry_id' => $entry->id]);
            $bill->update(['expense_id' => $expense->id, 'accounting_entry_id' => $entry->id]);
            $this->syncOwnerDebit($expense);
        }

        return back()->with('success', 'Utility payment saved.');
    }

    public function reports(Request $request)
    {
        $month = $this->month($request);
        $from = Carbon::parse($request->input('date_from', $month->copy()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('date_to', $month->copy()->endOfMonth()->toDateString()))->endOfDay();
        $utilityByType = UtilityBill::selectRaw('utility_accounts.utility_type, sum(utility_bills.total_amount) as total')
            ->join('utility_accounts', 'utility_accounts.id', '=', 'utility_bills.utility_account_id')
            ->whereBetween('bill_month', [$from, $to])
            ->groupBy('utility_accounts.utility_type')
            ->pluck('total', 'utility_type');

        $expenseBaseQuery = Expense::with(['property.building', 'vendor', 'paidFromAccount'])
            ->whereBetween('expense_date', [$from, $to])
            ->where('approval_status', '!=', 'rejected');

        $expenseRows = (clone $expenseBaseQuery)
            ->latest('expense_date')
            ->get();

        $expensesByCategory = (clone $expenseBaseQuery)
            ->selectRaw('category, sum(gross_amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');
        $expensesByDate = (clone $expenseBaseQuery)
            ->selectRaw('expense_date, sum(net_amount) as net_total, sum(vat_amount) as vat_total, sum(gross_amount) as gross_total, count(*) as count_total')
            ->groupBy('expense_date')
            ->orderBy('expense_date')
            ->get();
        $expensesByUnit = $expenseRows
            ->groupBy(fn (Expense $expense) => $expense->property?->name ?: 'General Company Expense')
            ->map(fn ($items) => [
                'count' => $items->count(),
                'net' => $items->sum('net_amount'),
                'vat' => $items->sum('vat_amount'),
                'gross' => $items->sum('gross_amount'),
            ]);
        $expenseTotals = [
            'count' => $expenseRows->count(),
            'draft' => $expenseRows->where('approval_status', 'draft')->count(),
            'review' => $expenseRows->where('needs_review', true)->count(),
            'net' => $expenseRows->sum('net_amount'),
            'vat' => $expenseRows->sum('vat_amount'),
            'gross' => $expenseRows->sum('gross_amount'),
        ];
        $outstandingBills = UtilityBill::with(['account', 'property'])
            ->whereIn('status', ['outstanding', 'overdue'])
            ->orderBy('due_date')
            ->get();

        return view('admin.accounting.reports', compact(
            'month',
            'from',
            'to',
            'utilityByType',
            'expensesByCategory',
            'expensesByDate',
            'expensesByUnit',
            'expenseRows',
            'expenseTotals',
            'outstandingBills'
        ));
    }

    public function chartOfAccounts(Request $request)
    {
        $accounts = AccountingAccount::query()
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))
            ->orderBy('code')
            ->get()
            ->groupBy('type');

        return view('admin.accounting.chart-of-accounts', compact('accounts') + $this->sharedData());
    }

    public function storeAccount(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:30|unique:accounting_accounts,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,income,expense',
            'parent_code' => 'nullable|string|max:30',
            'is_bank_cash' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        AccountingAccount::create([
            ...$data,
            'is_bank_cash' => $request->boolean('is_bank_cash'),
            'is_active' => true,
        ]);

        return back()->with('success', 'Chart of account added.');
    }

    public function bankAccounts()
    {
        $bankAccounts = BankAccount::with('accountingAccount')->orderBy('type')->orderBy('name')->get();

        return view('admin.accounting.bank-accounts', compact('bankAccounts') + $this->sharedData());
    }

    public function storeBankAccount(Request $request)
    {
        $data = $request->validate([
            'accounting_account_id' => 'nullable|exists:accounting_accounts,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:bank,cash,credit_card,wallet',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'opening_balance' => 'nullable|numeric',
            'notes' => 'nullable|string|max:1000',
        ]);

        $opening = (float) ($data['opening_balance'] ?? 0);
        BankAccount::create([
            ...$data,
            'currency' => $data['currency'] ?? 'AED',
            'opening_balance' => $opening,
            'current_balance' => $opening,
            'is_active' => true,
        ]);

        return back()->with('success', 'Bank or cash account added.');
    }

    public function vendors()
    {
        $vendors = Vendor::withCount('expenses')->orderBy('name')->get();

        return view('admin.accounting.vendors', compact('vendors') + $this->sharedData());
    }

    public function storeVendor(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'trn' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:1000',
            'opening_balance' => 'nullable|numeric',
            'notes' => 'nullable|string|max:1000',
        ]);

        Vendor::create([
            ...$data,
            'vendor_no' => $this->nextNumber('VEN', Vendor::class, 'vendor_no'),
            'opening_balance' => (float) ($data['opening_balance'] ?? 0),
            'is_active' => true,
        ]);

        return back()->with('success', 'Vendor saved.');
    }

    public function vatReport(Request $request)
    {
        $month = $this->month($request);
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();
        $entries = AccountingEntry::whereBetween('entry_date', [$from, $to])->latest('entry_date')->get();
        $outputVat = $entries->where('credit', '>', 0)->sum('vat_amount');
        $inputVat = $entries->where('debit', '>', 0)->sum('vat_amount');

        return view('admin.accounting.vat', compact('month', 'entries', 'outputVat', 'inputVat'));
    }

    public function ownerStatements(Request $request)
    {
        $owners = User::where('role', 'landlord')->orderBy('name')->get();
        $owner = $request->filled('landlord_id') ? User::find($request->input('landlord_id')) : $owners->first();
        $from = Carbon::parse($request->input('date_from', now()->startOfMonth()->toDateString()));
        $to = Carbon::parse($request->input('date_to', now()->endOfMonth()->toDateString()));
        $entries = collect();

        if ($owner) {
            $entries = LandlordAccountEntry::with('property')
                ->where('landlord_id', $owner->id)
                ->whereBetween('entry_date', [$from, $to])
                ->orderBy('entry_date')
                ->get()
                ->groupBy('property_id');
        }

        return view('admin.accounting.owner-statements', compact('owners', 'owner', 'from', 'to', 'entries'));
    }

    public function ownerStatementPdf(Request $request)
    {
        $owner = User::where('role', 'landlord')->findOrFail($request->input('landlord_id'));
        $from = Carbon::parse($request->input('date_from', now()->startOfMonth()->toDateString()));
        $to = Carbon::parse($request->input('date_to', now()->endOfMonth()->toDateString()));
        $entries = LandlordAccountEntry::with('property')
            ->where('landlord_id', $owner->id)
            ->whereBetween('entry_date', [$from, $to])
            ->orderBy('entry_date')
            ->get()
            ->groupBy('property_id');

        return PdfRenderer::downloadView('admin.accounting.pdf.owner-statement', compact('owner', 'from', 'to', 'entries'), 'owner-statement-' . Str::slug($owner->name) . '.pdf');
    }

    public function bookingInvoices(Request $request)
    {
        $invoices = BookingInvoice::with('booking.property')
            ->latest('issue_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.accounting.booking-invoices', compact('invoices'));
    }

    public function bookingInvoicePdf(BookingInvoice $invoice)
    {
        $invoice->load('booking.property.building', 'booking.agent');

        return PdfRenderer::downloadView('admin.accounting.pdf.booking-invoice', compact('invoice'), $invoice->invoice_number . '.pdf');
    }

    private function parseExpenseImport(string $path, string $sourceType, string $fileName): array
    {
        return match ($sourceType) {
            'csv', 'txt' => $this->parseCsvExpenses($path),
            'xlsx' => $this->parseXlsxExpenses($path),
            default => [$this->reviewOnlyImportRow($fileName, $sourceType)],
        };
    }

    private function parseCsvExpenses(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            return [];
        }

        $headers = fgetcsv($handle) ?: [];
        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            $raw = $this->combineImportRow($headers, $line);
            if ($this->rowIsBlank($raw)) {
                continue;
            }
            $rows[] = $this->normalizeExpenseImportRow($raw);
        }
        fclose($handle);

        return $rows;
    }

    private function parseXlsxExpenses(string $path): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return [$this->reviewOnlyImportRow(basename($path), 'xlsx')];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [$this->reviewOnlyImportRow(basename($path), 'xlsx')];
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $shared = simplexml_load_string($sharedXml);
            if ($shared) {
                foreach ($shared->si ?? [] as $item) {
                    $sharedStrings[] = trim((string) ($item->t ?? $item->r->t ?? ''));
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheetXml === false) {
            return [];
        }

        $sheet = simplexml_load_string($sheetXml);
        if (! $sheet) {
            return [];
        }
        $matrix = [];
        foreach ($sheet->sheetData->row ?? [] as $row) {
            $cells = [];
            foreach ($row->c ?? [] as $cell) {
                $ref = (string) $cell['r'];
                $column = preg_replace('/\d+/', '', $ref);
                $index = $this->excelColumnIndex($column);
                $value = (string) ($cell->v ?? '');
                if ((string) $cell['t'] === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                } elseif ((string) $cell['t'] === 'inlineStr') {
                    $value = (string) ($cell->is->t ?? '');
                }
                $cells[$index] = $value;
            }
            ksort($cells);
            $matrix[] = array_values($cells);
        }

        $headers = array_shift($matrix) ?: [];
        $rows = [];
        foreach ($matrix as $line) {
            $raw = $this->combineImportRow($headers, $line);
            if (! $this->rowIsBlank($raw)) {
                $rows[] = $this->normalizeExpenseImportRow($raw);
            }
        }

        return $rows;
    }

    private function normalizeExpenseImportRow(array $raw): array
    {
        $normalized = [];
        foreach ($raw as $key => $value) {
            $normalized[$this->normalizeImportKey($key)] = trim((string) $value);
        }

        $transactionId = $this->firstImportValue($normalized, ['transaction_id', 'transactionid', 'id', 'reference', 'transaction_reference']);
        $date = $this->normalizeImportDate($this->firstImportValue($normalized, [
            'date',
            'transaction_date',
            'transaction_clearing_time',
            'transaction_authorization_time',
            'created_at',
            'settlement_date',
        ]));
        $supplier = $this->firstImportValue($normalized, ['merchant', 'merchant_name', 'supplier', 'vendor', 'description']);
        $category = $this->guessExpenseCategory($this->firstImportValue($normalized, ['category', 'expense_category', 'merchant_category', 'spend_category']));
        $debit = $this->importMoney($this->firstImportValue($normalized, ['debit', 'amount_debited', 'amount', 'billing_amount', 'transaction_amount']));
        $credit = $this->importMoney($this->firstImportValue($normalized, ['credit', 'amount_credited']));
        $gross = max(0, $debit ?: -$credit);
        $vat = $this->importMoney($this->firstImportValue($normalized, ['vat', 'vat_amount', 'tax']));
        $trn = $this->firstImportValue($normalized, ['trn', 'trn_number', 'tax_registration_number']);
        $invoice = $this->firstImportValue($normalized, ['invoice', 'invoice_number', 'receipt_number']);
        $notes = $this->firstImportValue($normalized, ['notes', 'note', 'comments', 'memo', 'comments_from_the_employee_cardholder']);

        return [
            'expense_date' => $date,
            'category' => $category,
            'supplier' => $supplier,
            'net_amount' => max(0, $gross - $vat),
            'vat_rate' => 5,
            'vat_amount' => $vat,
            'gross_amount' => $gross,
            'payment_method' => $this->firstImportValue($normalized, ['wallet', 'card', 'payment_method']),
            'transaction_reference' => $invoice ?: $trn ?: $transactionId,
            'imported_transaction_id' => $transactionId,
            'description' => trim(implode(' | ', array_filter([$supplier, $notes, $trn ? 'TRN: ' . $trn : null, $invoice ? 'Invoice: ' . $invoice : null]))),
            'needs_review' => ! $date || $gross <= 0 || ! $supplier,
            'raw' => $raw,
        ];
    }

    private function reviewOnlyImportRow(string $fileName, string $sourceType): array
    {
        return [
            'expense_date' => now()->toDateString(),
            'category' => 'other',
            'supplier' => pathinfo($fileName, PATHINFO_FILENAME),
            'net_amount' => 0,
            'vat_rate' => 5,
            'vat_amount' => 0,
            'gross_amount' => 0,
            'payment_method' => null,
            'transaction_reference' => null,
            'imported_transaction_id' => null,
            'description' => strtoupper($sourceType) . ' imported as draft. Enter amount and details after review.',
            'needs_review' => true,
            'raw' => ['file' => $fileName, 'type' => $sourceType],
        ];
    }

    private function combineImportRow(array $headers, array $line): array
    {
        $row = [];
        foreach ($line as $index => $value) {
            $header = trim((string) ($headers[$index] ?? 'column_' . ($index + 1)));
            $row[$header] = $value;
        }

        return $row;
    }

    private function rowIsBlank(array $row): bool
    {
        return collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty();
    }

    private function importDuplicateExists(array $row): bool
    {
        if (! empty($row['imported_transaction_id']) && Schema::hasColumn('expenses', 'imported_transaction_id')) {
            return Expense::where('imported_transaction_id', $row['imported_transaction_id'])->exists();
        }

        return Expense::whereDate('expense_date', $row['expense_date'] ?: now()->toDateString())
            ->where('supplier', $row['supplier'] ?: '')
            ->where('gross_amount', (float) ($row['gross_amount'] ?? 0))
            ->exists();
    }

    private function expenseAmounts(float $enteredAmount, float $vatRate, bool $vatIncluded): array
    {
        $vatRate = max(0, $vatRate);
        $divider = 1 + ($vatRate / 100);

        if ($vatIncluded && $divider > 0) {
            $gross = round($enteredAmount, 2);
            $net = round($gross / $divider, 2);
            $vat = round($gross - $net, 2);
        } else {
            $net = round($enteredAmount, 2);
            $vat = round($net * ($vatRate / 100), 2);
            $gross = round($net + $vat, 2);
        }

        return [
            'net' => $net,
            'vat' => $vat,
            'gross' => $gross,
            'rate' => $vatRate,
        ];
    }

    private function expenseShouldPost(Expense $expense): bool
    {
        return in_array($expense->approval_status, ['approved', 'paid'], true);
    }

    private function expenseImportTrackingPayload(array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn('expenses', $column))
            ->all();
    }

    private function excelColumnIndex(string $column): int
    {
        $index = 0;
        foreach (str_split(strtoupper($column)) as $char) {
            $index = ($index * 26) + (ord($char) - 64);
        }

        return $index - 1;
    }

    private function normalizeImportKey(string $key): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($key)), '_');
    }

    private function firstImportValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (filled($row[$key] ?? null)) {
                return $row[$key];
            }
        }

        return null;
    }

    private function normalizeImportDate(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value)->toDateString();
        }

        try {
            $clean = preg_replace('/\s*\([^)]*\)\s*/', '', $value);

            return Carbon::parse(str_replace('/', '-', $clean))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function importMoney(?string $value): float
    {
        if (blank($value)) {
            return 0;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return round((float) $clean, 2);
    }

    private function guessExpenseCategory(?string $value): string
    {
        $text = strtolower((string) $value);

        return match (true) {
            str_contains($text, 'clean') => 'cleaning',
            str_contains($text, 'maint') || str_contains($text, 'repair') => 'maintenance',
            str_contains($text, 'dewa') || str_contains($text, 'electric') || str_contains($text, 'water') => 'dewa',
            str_contains($text, 'gas') => 'gas',
            str_contains($text, 'internet') || str_contains($text, 'wifi') => 'internet',
            str_contains($text, 'chiller') || str_contains($text, 'cool') => 'chiller',
            str_contains($text, 'supply') || str_contains($text, 'guest') => 'supplies',
            str_contains($text, 'commission') => 'commission',
            str_contains($text, 'license') || str_contains($text, 'permit') => 'license',
            default => 'other',
        };
    }

    private function postExpenseEntry(Expense $expense, string $type = 'expense', ?string $utilityBillId = null): AccountingEntry
    {
        return AccountingEntry::create([
            'entry_no' => $this->nextNumber('JE', AccountingEntry::class, 'entry_no'),
            'entry_date' => $expense->expense_date,
            'type' => $type,
            'category' => $expense->category,
            'accounting_account_id' => $this->expenseAccountId($expense->category),
            'description' => $expense->description,
            'property_id' => $expense->property_id,
            'landlord_id' => $expense->landlord_id,
            'booking_id' => $expense->booking_id,
            'paid_from_account_id' => $expense->paid_from_account_id,
            'vendor_id' => $expense->vendor_id,
            'expense_id' => $expense->id,
            'utility_bill_id' => $utilityBillId,
            'debit' => $expense->gross_amount,
            'credit' => 0,
            'vat_rate' => $expense->vat_rate,
            'vat_amount' => $expense->vat_amount,
            'net_amount' => $expense->net_amount,
            'gross_amount' => $expense->gross_amount,
            'payment_method' => $expense->payment_method,
            'transaction_reference' => $expense->transaction_reference,
            'attachment' => $expense->receipt_path,
            'approval_status' => $expense->approval_status === 'paid' ? 'posted' : $expense->approval_status,
            'created_by' => auth()->id(),
        ]);
    }

    private function syncOwnerDebit(Expense $expense): void
    {
        $existingEntries = LandlordAccountEntry::where('reference', $expense->expense_no)->get();
        $affectedLandlordIds = $existingEntries->pluck('landlord_id')->filter()->unique();
        $shouldPost = $this->expenseShouldPost($expense) && $expense->owner_billable && $expense->landlord_id;

        if (! $shouldPost) {
            LandlordAccountEntry::where('reference', $expense->expense_no)->delete();
            $affectedLandlordIds->each(fn (string $landlordId) => LandlordAccountEntry::recalculateBalancesFor($landlordId));

            return;
        }

        $entry = LandlordAccountEntry::updateOrCreate([
            'reference' => $expense->expense_no,
        ], [
            'landlord_id' => $expense->landlord_id,
            'property_id' => $expense->property_id,
            'entry_date' => $expense->expense_date,
            'type' => match ($expense->category) {
                'dewa' => 'dewa',
                'gas' => 'gas',
                'internet' => 'internet',
                'chiller' => 'chiller',
                'cleaning' => 'cleaning',
                'maintenance' => 'maintenance',
                default => 'other_expense',
            },
            'direction' => 'debit',
            'amount' => $expense->gross_amount,
            'description' => $expense->description ?: ucfirst($expense->category) . ' expense',
        ]);

        LandlordAccountEntry::where('reference', $expense->expense_no)
            ->where('id', '!=', $entry->id)
            ->delete();

        $affectedLandlordIds
            ->push($expense->landlord_id)
            ->unique()
            ->each(fn (string $landlordId) => LandlordAccountEntry::recalculateBalancesFor($landlordId));
    }

    private function upload(Request $request, string $field, string $folder): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        return MediaStorage::store($request->file($field), $folder);
    }

    private function nextNumber(string $prefix, string $model, string $column): string
    {
        do {
            $number = $prefix . '-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5));
        } while ($model::where($column, $number)->exists());

        return $number;
    }

    private function month(Request $request): Carbon
    {
        return Carbon::parse($request->input('month', now()->format('Y-m')) . '-01');
    }

    private function sharedData(): array
    {
        return [
            'properties' => Property::with('building')->orderBy('name')->get(),
            'owners' => User::where('role', 'landlord')->orderBy('name')->get(),
            'bookings' => Booking::with('property')->latest()->limit(100)->get(),
            'accounts' => AccountingAccount::where('is_active', true)->orderBy('code')->get(),
            'accountTypes' => AccountingAccount::TYPES,
            'bankAccounts' => BankAccount::where('is_active', true)->orderBy('name')->get(),
            'vendors' => Vendor::where('is_active', true)->orderBy('name')->get(),
            'entryTypes' => AccountingEntry::TYPES,
            'expenseCategories' => Expense::CATEGORIES,
            'utilityTypes' => UtilityAccount::TYPES,
            'responsibilities' => UtilityAccount::RESPONSIBILITIES,
        ];
    }

    private function canModifyApprovedExpense(Expense $expense): bool
    {
        if (! in_array($expense->approval_status, ['approved', 'paid'], true)) {
            return true;
        }

        // The existing access model treats the admin role as the super-admin role.
        return auth()->user()?->role === 'admin';
    }

    private function expenseAccountId(?string $category): ?string
    {
        $code = match ($category) {
            'dewa' => '5010',
            'gas' => '5020',
            'internet' => '5030',
            'chiller' => '5040',
            'cleaning' => '5050',
            'maintenance' => '5070',
            default => '5990',
        };

        return AccountingAccount::where('code', $code)->value('id');
    }
}
