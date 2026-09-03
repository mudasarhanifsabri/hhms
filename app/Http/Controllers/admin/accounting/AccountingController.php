<?php

namespace App\Http\Controllers\admin\accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingAccount;
use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\BankTransfer;
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
use Illuminate\Validation\ValidationException;

class AccountingController extends Controller
{
    public function dashboard(Request $request)
    {
        $month = $this->month($request);
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $income = AccountingEntry::where('type', '!=', 'deposit')->whereBetween('entry_date', [$from, $to])->sum('credit');
        $expenses = AccountingEntry::where('type', '!=', 'deposit')->whereBetween('entry_date', [$from, $to])->sum('debit');
        $todayIncome = AccountingEntry::where('type', '!=', 'deposit')->whereDate('entry_date', today())->sum('credit');
        $todayExpenses = AccountingEntry::where('type', '!=', 'deposit')->whereDate('entry_date', today())->sum('debit');
        $cashBalance = $this->bankBalanceTotal('cash');
        $bankBalance = $this->bankBalanceTotal('bank');
        $ownerBalances = $this->ownerAccountBalances();
        $accountsReceivable = $this->outstandingBookingInvoiceTotal()
            + $ownerBalances->filter(fn ($balance) => $balance < 0)->sum(fn ($balance) => abs($balance));
        $accountsPayable = Expense::whereIn('approval_status', ['pending', 'approved'])->sum('gross_amount');
        $ownerPayables = $ownerBalances->filter(fn ($balance) => $balance > 0)->sum();
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
        $expenses = $this->filteredExpenses($request)
            ->latest('expense_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.accounting.expenses', $this->sharedData() + compact('expenses'));
    }

    public function expenseReportPdf(Request $request)
    {
        $this->validateExpenseReportFilters($request);
        $expenses = $this->filteredExpenses($request)->orderBy('expense_date')->orderBy('expense_no')->get();
        $filters = $this->expenseReportFilters($request);

        return PdfRenderer::downloadView(
            'admin.accounting.pdf.expense-report',
            compact('expenses', 'filters'),
            'expense-report-' . now()->format('Y-m-d') . '.pdf',
            ['format' => 'A4-L']
        );
    }

    public function expenseReportCsv(Request $request)
    {
        $this->validateExpenseReportFilters($request);
        $expenses = $this->filteredExpenses($request)->orderBy('expense_date')->orderBy('expense_no')->get();

        return response()->streamDownload(function () use ($expenses) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Date', 'Expense No.', 'Category', 'Unit', 'Building', 'Vendor', 'Charged To', 'Paid From', 'Status', 'Net (AED)', 'VAT (AED)', 'Total (AED)', 'Description', 'View Document']);

            foreach ($expenses as $expense) {
                $documentUrl = ($expense->invoice_path || $expense->receipt_path || $expense->import_source_file)
                    ? route('admin.accounting.expenses.document', $expense)
                    : null;
                fputcsv($output, [
                    $expense->expense_date?->format('Y-m-d'),
                    $expense->expense_no,
                    Expense::CATEGORIES[$expense->category] ?? ucfirst($expense->category),
                    $expense->property?->name,
                    $expense->property?->building?->building_name ?? $expense->property?->building?->name,
                    $expense->vendor?->name ?? $expense->supplier,
                    ucfirst(str_replace('_', ' ', $expense->responsibility)),
                    $expense->paidFromAccount?->name,
                    ucfirst($expense->approval_status),
                    number_format((float) $expense->net_amount, 2, '.', ''),
                    number_format((float) $expense->vat_amount, 2, '.', ''),
                    number_format((float) $expense->gross_amount, 2, '.', ''),
                    $expense->description,
                    $documentUrl ? '=HYPERLINK("' . str_replace('"', '""', $documentUrl) . '","View Document")' : '',
                ]);
            }

            fclose($output);
        }, 'expense-report-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function expenseDocument(Expense $expense)
    {
        $path = $expense->invoice_path ?: $expense->receipt_path ?: $expense->import_source_file;
        abort_if(blank($path), 404, 'No invoice or receipt is attached to this expense.');

        return redirect()->away(MediaStorage::url($path));
    }

    private function filteredExpenses(Request $request)
    {
        return Expense::with(['property.building', 'landlord', 'booking', 'vendor', 'paidFromAccount'])
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->input('category')))
            ->when($request->filled('property_id'), fn ($query) => $query->where('property_id', $request->input('property_id')))
            ->when($request->filled('approval_status'), fn ($query) => $query->where('approval_status', $request->input('approval_status')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('expense_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('expense_date', '<=', $request->input('date_to')));
    }

    private function validateExpenseReportFilters(Request $request): void
    {
        $request->validate([
            'category' => 'nullable|in:' . implode(',', array_keys(Expense::CATEGORIES)),
            'property_id' => 'nullable|uuid|exists:properties,id',
            'approval_status' => 'nullable|in:draft,pending,reviewed,approved,paid,rejected',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);
    }

    private function expenseReportFilters(Request $request): array
    {
        return [
            'category' => $request->filled('category') ? (Expense::CATEGORIES[$request->input('category')] ?? $request->input('category')) : 'All categories',
            'unit' => $request->filled('property_id') ? Property::with('building')->find($request->input('property_id')) : null,
            'status' => $request->filled('approval_status') ? ucfirst($request->input('approval_status')) : 'All statuses',
            'date_from' => $request->filled('date_from') ? Carbon::parse($request->input('date_from')) : null,
            'date_to' => $request->filled('date_to') ? Carbon::parse($request->input('date_to')) : null,
        ];
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
            'approval_status' => $data['approval_status'] ?? 'pending',
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

        if ($expense->accounting_entry_id) {
            $expense->accountingEntry()->update([
                'entry_date' => $expense->expense_date,
                'type' => 'expense',
                'category' => $expense->category,
                'accounting_account_id' => $this->expenseAccountId($expense->category),
                'description' => $expense->description,
                'property_id' => $expense->property_id,
                'landlord_id' => $expense->landlord_id,
                'booking_id' => $expense->booking_id,
                'paid_from_account_id' => $expense->paid_from_account_id,
                'vendor_id' => $expense->vendor_id,
                'debit' => $expense->gross_amount,
                'credit' => 0,
                'vat_rate' => $expense->vat_rate,
                'vat_amount' => $expense->vat_amount,
                'net_amount' => $expense->net_amount,
                'gross_amount' => $expense->gross_amount,
                'payment_method' => $expense->payment_method,
                'transaction_reference' => $expense->transaction_reference,
                'approval_status' => $expense->approval_status === 'paid' ? 'posted' : $expense->approval_status,
            ]);
        }

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

        $postedStatuses = ['posted', 'approved', 'paid'];
        $profitAndLoss = AccountingEntry::query()
            ->join('accounting_accounts', 'accounting_accounts.id', '=', 'accounting_entries.accounting_account_id')
            ->whereBetween('accounting_entries.entry_date', [$from, $to])
            ->whereIn('accounting_entries.approval_status', $postedStatuses)
            ->whereIn('accounting_accounts.type', ['income', 'expense'])
            ->selectRaw('accounting_accounts.code, accounting_accounts.name, accounting_accounts.type, SUM(accounting_entries.debit) as debit_total, SUM(accounting_entries.credit) as credit_total')
            ->groupBy('accounting_accounts.code', 'accounting_accounts.name', 'accounting_accounts.type')
            ->orderBy('accounting_accounts.code')
            ->get();
        $incomeAccounts = $profitAndLoss->where('type', 'income')->map(fn ($row) => [
            'code' => $row->code,
            'name' => $row->name,
            'amount' => (float) $row->credit_total - (float) $row->debit_total,
        ]);
        $expenseAccounts = $profitAndLoss->where('type', 'expense')->map(fn ($row) => [
            'code' => $row->code,
            'name' => $row->name,
            'amount' => (float) $row->debit_total - (float) $row->credit_total,
        ]);
        $profitLossTotals = [
            'income' => $incomeAccounts->sum('amount'),
            'expense' => $expenseAccounts->sum('amount'),
        ];
        $profitLossTotals['net_profit'] = $profitLossTotals['income'] - $profitLossTotals['expense'];

        $balanceSheetRows = AccountingEntry::query()
            ->join('accounting_accounts', 'accounting_accounts.id', '=', 'accounting_entries.accounting_account_id')
            ->whereDate('accounting_entries.entry_date', '<=', $to)
            ->whereIn('accounting_entries.approval_status', $postedStatuses)
            ->whereIn('accounting_accounts.type', ['asset', 'liability', 'equity'])
            ->selectRaw('accounting_accounts.code, accounting_accounts.name, accounting_accounts.type, SUM(accounting_entries.debit) as debit_total, SUM(accounting_entries.credit) as credit_total')
            ->groupBy('accounting_accounts.code', 'accounting_accounts.name', 'accounting_accounts.type')
            ->orderBy('accounting_accounts.code')
            ->get()
            ->groupBy('type');

        $cashFlow = AccountingEntry::query()
            ->whereBetween('entry_date', [$from, $to])
            ->whereIn('approval_status', $postedStatuses)
            ->whereNotNull('paid_from_account_id')
            ->selectRaw('SUM(credit) as inflow, SUM(debit) as outflow')
            ->first();
        $cashFlowSummary = [
            'opening' => $this->bankBalanceAsOf($from->copy()->subDay()),
            'inflow' => (float) ($cashFlow->inflow ?? 0),
            'outflow' => (float) ($cashFlow->outflow ?? 0),
        ];
        $cashFlowSummary['closing'] = $cashFlowSummary['opening'] + $cashFlowSummary['inflow'] - $cashFlowSummary['outflow'];

        $ownerReceivableRows = $this->ownerReceivableRows();
        $receivableAgeingRecords = BookingInvoice::withSum('payments', 'amount')->whereIn('status', ['unpaid', 'partial'])->get(['id', 'issue_date', 'total_amount'])
            ->map(function (BookingInvoice $invoice) { $invoice->total_amount = $invoice->balance_due; return $invoice; })
            ->concat($ownerReceivableRows->map(fn ($row) => (object) [
                'issue_date' => $row->oldest_debit_date ?? today(),
                'total_amount' => abs((float) $row->balance),
            ]));
        $receivableAgeing = $this->ageingBuckets(
            $receivableAgeingRecords,
            'issue_date',
            'total_amount'
        );
        $receivableRows = BookingInvoice::with('booking.property')->withSum('payments', 'amount')
            ->whereIn('status', ['unpaid', 'partial'])
            ->orderBy('issue_date')
            ->get();
        $payableAgeing = $this->ageingBuckets(
            Expense::whereIn('approval_status', ['pending', 'approved'])->get(['expense_date', 'gross_amount']),
            'expense_date',
            'gross_amount'
        );
        $expenseCategories = Expense::CATEGORIES;

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
            , 'incomeAccounts'
            , 'expenseAccounts'
            , 'profitLossTotals'
            , 'balanceSheetRows'
            , 'cashFlowSummary'
            , 'receivableAgeing'
            , 'receivableRows'
            , 'ownerReceivableRows'
            , 'payableAgeing'
            , 'expenseCategories'
        ));
    }

    public function chartOfAccounts(Request $request)
    {
        $accountRows = AccountingAccount::query()
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))
            ->orderBy('code')
            ->get();
        $movements = AccountingEntry::whereIn('approval_status', ['posted', 'approved', 'paid'])
            ->whereNotNull('accounting_account_id')
            ->selectRaw('accounting_account_id, SUM(debit) AS debit_total, SUM(credit) AS credit_total')
            ->groupBy('accounting_account_id')->get()->keyBy('accounting_account_id');
        $dynamicReceivable = $this->totalAccountsReceivable();
        $accountRows->each(function (AccountingAccount $account) use ($movements, $dynamicReceivable) {
            $movement = $movements->get($account->id);
            $balance = in_array($account->type, ['asset', 'expense'], true)
                ? (float) ($movement?->debit_total ?? 0) - (float) ($movement?->credit_total ?? 0)
                : (float) ($movement?->credit_total ?? 0) - (float) ($movement?->debit_total ?? 0);
            $account->setAttribute('live_balance', $account->code === '1060' ? $dynamicReceivable : $balance);
        });
        $accounts = $accountRows->groupBy('type');

        return view('admin.accounting.chart-of-accounts', compact('accounts') + $this->sharedData());
    }

    public function chartAccountStatement(Request $request, AccountingAccount $account)
    {
        $from = $request->filled('date_from') ? Carbon::parse($request->input('date_from'))->startOfDay() : null;
        $to = $request->filled('date_to') ? Carbon::parse($request->input('date_to'))->endOfDay() : today()->endOfDay();

        if ($account->code === '1060') {
            $allRows = $this->accountsReceivableStatementRows();
        } else {
            $allRows = AccountingEntry::where('accounting_account_id', $account->id)
                ->whereIn('approval_status', ['posted', 'approved', 'paid'])->get()
                ->map(fn (AccountingEntry $entry) => (object) [
                    'date' => $entry->entry_date, 'source_type' => ucfirst($entry->type), 'party' => $entry->description ?: '-',
                    'reference' => $entry->entry_no, 'description' => $entry->description,
                    'debit' => (float) $entry->debit, 'credit' => (float) $entry->credit, 'url' => null,
                ]);
        }

        $allRows = $allRows->sortBy(fn ($row) => Carbon::parse($row->date)->timestamp)->values();
        $openingBalance = $from ? $allRows->filter(fn ($row) => Carbon::parse($row->date)->lt($from))->sum(fn ($row) => $row->debit - $row->credit) : 0;
        $rows = $allRows->filter(fn ($row) => (! $from || Carbon::parse($row->date)->gte($from)) && Carbon::parse($row->date)->lte($to))->values();
        $running = $openingBalance;
        $rows->each(function ($row) use (&$running) { $running += $row->debit - $row->credit; $row->balance = $running; });

        return view('admin.accounting.chart-account-statement', compact('account', 'rows', 'from', 'to', 'openingBalance', 'running'));
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
        $movements = AccountingEntry::whereIn('approval_status', ['posted', 'approved', 'paid'])
            ->whereNotNull('paid_from_account_id')
            ->selectRaw('paid_from_account_id, SUM(credit - debit) as movement')
            ->groupBy('paid_from_account_id')
            ->pluck('movement', 'paid_from_account_id');
        $bankAccounts->each(fn (BankAccount $account) => $account->setAttribute(
            'current_balance',
            (float) $account->opening_balance + (float) ($movements[$account->id] ?? 0)
        ));

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

    public function updateBankAccount(Request $request, BankAccount $bankAccount)
    {
        $data = $request->validate([
            'accounting_account_id' => 'nullable|exists:accounting_accounts,id', 'name' => 'required|string|max:255',
            'type' => 'required|in:bank,cash,credit_card,wallet', 'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255', 'account_number' => 'nullable|string|max:255',
            'currency' => 'required|string|max:10', 'opening_balance' => 'required|numeric',
            'is_active' => 'nullable|boolean', 'notes' => 'nullable|string|max:1000',
        ]);
        $bankAccount->update([...$data, 'is_active' => $request->boolean('is_active')]);
        $this->refreshBankAccountBalance($bankAccount);
        return back()->with('success', 'Bank or cash account updated.');
    }

    public function transferBetweenAccounts(Request $request)
    {
        $data = $request->validate([
            'transfer_date' => 'required|date', 'from_account_id' => 'required|exists:bank_accounts,id|different:to_account_id',
            'to_account_id' => 'required|exists:bank_accounts,id', 'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255', 'description' => 'nullable|string|max:1000',
        ]);
        $transfer = DB::transaction(function () use ($data) {
            $accounts = BankAccount::whereIn('id', [$data['from_account_id'], $data['to_account_id']])->lockForUpdate()->get()->keyBy(fn ($a) => (string) $a->id);
            $from = $accounts[(string) $data['from_account_id']]; $to = $accounts[(string) $data['to_account_id']]; $amount = (float) $data['amount'];
            if (! $from->is_active || ! $to->is_active) throw ValidationException::withMessages(['from_account_id' => 'Both transfer accounts must be active.']);
            if (strtoupper($from->currency) !== strtoupper($to->currency)) throw ValidationException::withMessages(['to_account_id' => 'Transfers require accounts with the same currency.']);
            if ($this->bankAccountBalance($from) < $amount) throw ValidationException::withMessages(['amount' => 'The source account has insufficient available balance.']);
            $transfer = BankTransfer::create([...$data, 'transfer_no' => $this->nextNumber('TRF', BankTransfer::class, 'transfer_no'), 'currency' => strtoupper($from->currency), 'created_by' => auth()->id()]);
            $common = ['entry_date' => $data['transfer_date'], 'type' => 'transfer', 'category' => 'account_transfer', 'bank_transfer_id' => $transfer->id,
                'vat_rate' => 0, 'vat_amount' => 0, 'net_amount' => $amount, 'gross_amount' => $amount, 'payment_method' => 'internal_transfer',
                'transaction_reference' => $transfer->transfer_no, 'status' => 'posted', 'approval_status' => 'posted', 'created_by' => auth()->id()];
            AccountingEntry::create([...$common, 'entry_no' => $this->nextNumber('JE', AccountingEntry::class, 'entry_no'), 'description' => "Transfer to {$to->name}",
                'accounting_account_id' => $from->accounting_account_id, 'paid_from_account_id' => $from->id, 'debit' => $amount, 'credit' => 0]);
            AccountingEntry::create([...$common, 'entry_no' => $this->nextNumber('JE', AccountingEntry::class, 'entry_no'), 'description' => "Transfer from {$from->name}",
                'accounting_account_id' => $to->accounting_account_id, 'paid_from_account_id' => $to->id, 'debit' => 0, 'credit' => $amount]);
            $this->refreshBankAccountBalance($from); $this->refreshBankAccountBalance($to);
            return $transfer;
        });
        return redirect()->route('admin.accounting.bank-account.statement', $transfer->from_account_id)->with('success', "Transfer {$transfer->transfer_no} posted with balanced ledger entries.");
    }

    public function bankStatements(Request $request)
    {
        return $this->renderBankStatement($request, $request->filled('account_id') ? BankAccount::findOrFail($request->input('account_id')) : null);
    }

    public function bankAccountStatement(Request $request, BankAccount $bankAccount)
    {
        return $this->renderBankStatement($request, $bankAccount);
    }

    private function renderBankStatement(Request $request, ?BankAccount $account)
    {
        $entries = AccountingEntry::with(['paidFromAccount', 'bankTransfer.fromAccount', 'bankTransfer.toAccount', 'creator'])
            ->whereIn('approval_status', ['posted', 'approved', 'paid'])->whereNotNull('paid_from_account_id')
            ->when($account, fn ($q) => $q->where('paid_from_account_id', $account->id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('entry_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('entry_date', '<=', $request->input('date_to')))
            ->orderBy('entry_date')->orderBy('created_at')->get();
        $balances = BankAccount::all()->mapWithKeys(fn ($item) => [(string) $item->id => (float) $item->opening_balance])->all();
        if ($request->filled('date_from')) {
            $priorMovements = AccountingEntry::whereIn('approval_status', ['posted', 'approved', 'paid'])
                ->whereNotNull('paid_from_account_id')->whereDate('entry_date', '<', $request->input('date_from'))
                ->when($account, fn ($q) => $q->where('paid_from_account_id', $account->id))
                ->selectRaw('paid_from_account_id, SUM(credit - debit) as movement')->groupBy('paid_from_account_id')->pluck('movement', 'paid_from_account_id');
            foreach ($priorMovements as $accountId => $movement) $balances[(string) $accountId] = ($balances[(string) $accountId] ?? 0) + (float) $movement;
        }
        $runningBalances = [];
        foreach ($entries as $entry) { $id = (string) $entry->paid_from_account_id; $balances[$id] = ($balances[$id] ?? 0) + (float) $entry->credit - (float) $entry->debit; $runningBalances[$entry->id] = $balances[$id]; }
        return view('admin.accounting.bank-statements', ['selectedAccount' => $account, 'entries' => $entries, 'runningBalances' => $runningBalances,
            'bankAccounts' => BankAccount::orderBy('name')->get()] + $this->sharedData());
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
                ->statementOrder()
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
            ->statementOrder()
            ->get()
            ->groupBy('property_id');

        return PdfRenderer::downloadView('admin.accounting.pdf.owner-statement', compact('owner', 'from', 'to', 'entries'), 'owner-statement-' . Str::slug($owner->name) . '.pdf');
    }

    public function destroyOwnerStatementEntry(LandlordAccountEntry $entry)
    {
        $landlordId = $entry->landlord_id;

        DB::transaction(function () use ($entry, $landlordId) {
            if (filled($entry->reference)) {
                Expense::where('expense_no', $entry->reference)
                    ->where('landlord_id', $landlordId)
                    ->update(['owner_billable' => false]);
            }

            $entry->delete();
            LandlordAccountEntry::recalculateBalancesFor($landlordId);
        });

        return back()->with('success', 'Owner statement entry deleted and balances recalculated.');
    }

    public function bookingInvoices(Request $request)
    {
        $invoices = BookingInvoice::with('booking.property')->withSum('payments', 'amount')
            ->latest('issue_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.accounting.booking-invoices', compact('invoices'));
    }

    public function bookingInvoicePdf(BookingInvoice $invoice)
    {
        $invoice->load('booking.property.building', 'booking.agent', 'payments');

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

    private function bankBalanceTotal(?string $type = null, ?Carbon $asOf = null): float
    {
        $accounts = BankAccount::query()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->get(['id', 'opening_balance']);
        $movements = AccountingEntry::query()
            ->whereIn('approval_status', ['posted', 'approved', 'paid'])
            ->whereIn('paid_from_account_id', $accounts->pluck('id'))
            ->when($asOf, fn ($query) => $query->whereDate('entry_date', '<=', $asOf))
            ->selectRaw('paid_from_account_id, SUM(credit - debit) as movement')
            ->groupBy('paid_from_account_id')
            ->pluck('movement', 'paid_from_account_id');

        return (float) $accounts->sum(
            fn (BankAccount $account) => (float) $account->opening_balance + (float) ($movements[$account->id] ?? 0)
        );
    }

    private function bankBalanceAsOf(Carbon $date): float
    {
        return $this->bankBalanceTotal(null, $date);
    }

    private function bankAccountBalance(BankAccount $account): float
    {
        $movement = AccountingEntry::where('paid_from_account_id', $account->id)->whereIn('approval_status', ['posted', 'approved', 'paid'])
            ->selectRaw('COALESCE(SUM(credit - debit),0) as movement')->value('movement');
        return (float) $account->opening_balance + (float) $movement;
    }

    private function refreshBankAccountBalance(BankAccount $account): void
    {
        $account->forceFill(['current_balance' => $this->bankAccountBalance($account)])->save();
    }

    private function ageingBuckets($records, string $dateColumn, string $amountColumn): array
    {
        $buckets = ['current' => 0.0, '31_60' => 0.0, '61_90' => 0.0, 'over_90' => 0.0];

        foreach ($records as $record) {
            $date = Carbon::parse($record->{$dateColumn});
            $days = max(0, $date->diffInDays(today(), false));
            $bucket = match (true) {
                $days <= 30 => 'current',
                $days <= 60 => '31_60',
                $days <= 90 => '61_90',
                default => 'over_90',
            };
            $buckets[$bucket] += (float) $record->{$amountColumn};
        }

        $buckets['total'] = array_sum($buckets);

        return $buckets;
    }

    private function ownerAccountBalances()
    {
        return LandlordAccountEntry::query()
            ->selectRaw("landlord_id, SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END) AS balance")
            ->groupBy('landlord_id')
            ->pluck('balance', 'landlord_id')
            ->map(fn ($balance) => (float) $balance);
    }

    private function totalAccountsReceivable(): float
    {
        $ownerReceivable = $this->ownerAccountBalances()->filter(fn ($balance) => $balance < 0)->sum(fn ($balance) => abs($balance));

        return $this->outstandingBookingInvoiceTotal() + $ownerReceivable;
    }

    private function accountsReceivableStatementRows()
    {
        $bookingRows = BookingInvoice::with(['booking.property', 'payments'])->get()->flatMap(function (BookingInvoice $invoice) {
            $charge = (object) [
                'date' => $invoice->issue_date, 'source_type' => 'Guest Invoice',
                'party' => $invoice->booking?->guest_name ?? 'Booking customer', 'reference' => $invoice->invoice_number,
                'description' => trim(($invoice->booking?->booking_reference ?? '') . ' ' . ($invoice->booking?->property?->name ?? '')),
                'debit' => (float) $invoice->total_amount, 'credit' => 0.0,
                'url' => $invoice->booking ? route('admin.booking.show', $invoice->booking) : null,
            ];
            return collect([$charge])->concat($invoice->payments->map(function ($invoicePayment) use ($charge) {
                $payment = clone $charge; $payment->date = $invoicePayment->payment_date; $payment->source_type = 'Guest Payment';
                $payment->description = trim('Invoice payment ' . ($invoicePayment->payment_method ?: '') . ' ' . ($invoicePayment->reference ?: ''));
                $payment->debit = 0.0; $payment->credit = (float) $invoicePayment->amount;
                return $payment;
            }));
        });
        $ownerIds = $this->ownerAccountBalances()->filter(fn ($balance) => $balance < 0)->keys();
        $ownerRows = LandlordAccountEntry::with(['landlord', 'property'])->whereIn('landlord_id', $ownerIds)->get()
            ->map(fn (LandlordAccountEntry $entry) => (object) [
                'date' => $entry->entry_date, 'source_type' => 'Owner Ledger',
                'party' => $entry->landlord?->name ?? 'Owner', 'reference' => $entry->reference,
                'description' => trim($entry->type_label . ' - ' . ($entry->description ?: $entry->property?->name)),
                'debit' => $entry->direction === 'debit' ? (float) $entry->amount : 0.0,
                'credit' => $entry->direction === 'credit' ? (float) $entry->amount : 0.0,
                'url' => route('admin.landlord.account-statement', $entry->landlord_id),
            ]);

        return $bookingRows->concat($ownerRows);
    }

    private function outstandingBookingInvoiceTotal(): float
    {
        return BookingInvoice::withSum('payments', 'amount')
            ->whereIn('status', ['unpaid', 'partial'])
            ->get()
            ->sum(fn (BookingInvoice $invoice) => $invoice->balance_due);
    }

    private function ownerReceivableRows()
    {
        return LandlordAccountEntry::with('landlord')
            ->selectRaw("landlord_id, SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END) AS balance, MIN(CASE WHEN direction = 'debit' THEN entry_date END) AS oldest_debit_date")
            ->groupBy('landlord_id')
            ->havingRaw("SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END) < 0")
            ->get();
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
