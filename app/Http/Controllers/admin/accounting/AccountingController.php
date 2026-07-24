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
use App\Support\PdfRenderer;
use Illuminate\Http\Request;
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
        $expenses = Expense::with(['property', 'landlord', 'booking', 'vendor', 'paidFromAccount'])
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->input('category')))
            ->when($request->filled('property_id'), fn ($query) => $query->where('property_id', $request->input('property_id')))
            ->latest('expense_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.accounting.expenses', $this->sharedData() + compact('expenses'));
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
            'payment_method' => 'nullable|string|max:100',
            'transaction_reference' => 'nullable|string|max:255',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'invoice' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'approval_status' => 'nullable|in:pending,approved,paid,rejected',
            'description' => 'nullable|string|max:1000',
        ]);

        $property = ! empty($data['property_id']) ? Property::find($data['property_id']) : null;
        $vatRate = (float) ($data['vat_rate'] ?? 5);
        $net = (float) $data['net_amount'];
        $vat = round($net * ($vatRate / 100), 2);
        $gross = $net + $vat;
        $receipt = $this->upload($request, 'receipt', 'expense_receipts');
        $invoice = $this->upload($request, 'invoice', 'expense_invoices');

        $expense = Expense::create([
            ...$data,
            'expense_no' => $this->nextNumber('EXP', Expense::class, 'expense_no'),
            'landlord_id' => $property?->landlord_id,
            'owner_billable' => $request->boolean('owner_billable'),
            'vat_rate' => $vatRate,
            'vat_amount' => $vat,
            'gross_amount' => $gross,
            'receipt_path' => $receipt,
            'invoice_path' => $invoice,
            'approval_status' => $data['approval_status'] ?? 'approved',
            'created_by' => auth()->id(),
        ]);

        $entry = $this->postExpenseEntry($expense);
        $expense->update(['accounting_entry_id' => $entry->id]);
        $this->postOwnerDebitIfNeeded($expense);

        return back()->with('success', 'Expense recorded and posted to accounting.');
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
            $this->postOwnerDebitIfNeeded($expense);
        }

        return back()->with('success', 'Utility payment saved.');
    }

    public function reports(Request $request)
    {
        $month = $this->month($request);
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();
        $utilityByType = UtilityBill::selectRaw('utility_accounts.utility_type, sum(utility_bills.total_amount) as total')
            ->join('utility_accounts', 'utility_accounts.id', '=', 'utility_bills.utility_account_id')
            ->whereBetween('bill_month', [$from, $to])
            ->groupBy('utility_accounts.utility_type')
            ->pluck('total', 'utility_type');
        $expensesByCategory = Expense::whereBetween('expense_date', [$from, $to])
            ->selectRaw('category, sum(gross_amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');
        $outstandingBills = UtilityBill::with(['account', 'property'])
            ->whereIn('status', ['outstanding', 'overdue'])
            ->orderBy('due_date')
            ->get();

        return view('admin.accounting.reports', compact('month', 'utilityByType', 'expensesByCategory', 'outstandingBills'));
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

    private function postOwnerDebitIfNeeded(Expense $expense): void
    {
        if (! $expense->owner_billable || ! $expense->landlord_id) {
            return;
        }

        LandlordAccountEntry::create([
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
            'reference' => $expense->expense_no,
            'description' => $expense->description ?: ucfirst($expense->category) . ' expense',
        ]);
        LandlordAccountEntry::recalculateBalancesFor($expense->landlord_id);
    }

    private function upload(Request $request, string $field, string $folder): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        return $request->file($field)->store($folder, 'public');
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
