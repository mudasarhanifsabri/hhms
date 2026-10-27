<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\AccountingEntry;
use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\BookingInvoicePayment;
use App\Models\CeoReviewIssue;
use App\Models\Expense;
use App\Models\Property;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CeoPortalController extends Controller
{
    public function index(Request $request)
    {
        $weekEnding = $this->weekEnding($request->input('week_ending'));
        $weekStart = $weekEnding->subDays(6)->startOfDay();
        $weekEnd = $weekEnding->endOfDay();
        $monthStart = $weekEnding->startOfMonth()->startOfDay();

        $rangeTotals = fn (CarbonImmutable $from) => [
            'income' => (float) AccountingEntry::whereBetween('entry_date', [$from, $weekEnd])->where('status', 'posted')->sum('credit'),
            'expenses' => (float) Expense::whereBetween('expense_date', [$from, $weekEnd])->where('approval_status', 'approved')->whereNull('reversed_at')->sum('gross_amount'),
            'cash' => (float) BookingInvoicePayment::whereBetween('payment_date', [$from, $weekEnd])->whereNull('reversed_at')->sum('amount'),
        ];

        $week = $rangeTotals($weekStart);
        $month = $rangeTotals($monthStart);
        $week['result'] = $week['income'] - $week['expenses'];
        $month['result'] = $month['income'] - $month['expenses'];

        $overdue = BookingInvoice::withSum('payments', 'amount')
            ->whereDate('due_date', '<=', $weekEnding)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->get()->sum(fn ($invoice) => max(0, (float) $invoice->total_amount - (float) ($invoice->payments_sum_amount ?? 0)));

        $totalUnits = Property::count();
        $occupied = Property::whereHas('bookings', fn ($q) => $q
            ->whereDate('check_in', '<=', $weekEnding)
            ->whereDate('check_out', '>', $weekEnding)
            ->where('status', '!=', 'checked_out'))->count();
        $vacantReady = Property::whereIn('status', ['available', 'vacant'])->count();
        $endingSoon = Booking::whereBetween('check_out', [$weekEnding->addDay(), $weekEnding->addDays(30)])->where('status', '!=', 'checked_out')->count();
        $signed = Booking::whereBetween('reservation_date', [$weekStart, $weekEnd])->count();
        $newUnits = Property::whereBetween('created_at', [$weekStart, $weekEnd])->count();
        $lostUnits = Property::onlyTrashed()->whereBetween('deleted_at', [$weekStart, $weekEnd])->count();

        $issues = CeoReviewIssue::with(['creator', 'decider'])->whereDate('week_ending', $weekEnding)->orderByRaw("status = 'open' desc")->latest()->get();
        $weeks = CeoReviewIssue::query()->select('week_ending')->distinct()->orderByDesc('week_ending')->limit(12)
            ->pluck('week_ending')->map(fn ($date) => CarbonImmutable::parse($date, 'Asia/Dubai'));

        return view('admin.ceo.index', compact(
            'weekEnding', 'weekStart', 'week', 'month', 'overdue', 'totalUnits', 'occupied',
            'vacantReady', 'endingSoon', 'signed', 'newUnits', 'lostUnits', 'issues', 'weeks'
        ));
    }

    public function storeIssue(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'week_ending' => 'required|date', 'issue' => 'required|string|max:255', 'impact' => 'nullable|string|max:2000',
            'recommendation' => 'nullable|string|max:2000', 'owner_name' => 'nullable|string|max:255', 'due_date' => 'nullable|date',
        ]);
        CeoReviewIssue::create($data + ['created_by' => $request->user()->id]);
        return back()->with('success', 'CEO review issue added.');
    }

    public function decide(Request $request, CeoReviewIssue $issue): RedirectResponse
    {
        $data = $request->validate(['decision' => 'required|string|max:3000', 'status' => 'required|in:open,decided,closed']);
        $issue->update($data + ['decided_by' => $request->user()->id, 'decided_at' => now('Asia/Dubai')]);
        return back()->with('success', 'CEO decision saved.');
    }

    private function weekEnding(?string $date): CarbonImmutable
    {
        $day = $date ? CarbonImmutable::parse($date, 'Asia/Dubai') : CarbonImmutable::now('Asia/Dubai');
        $daysUntilSaturday = (CarbonImmutable::SATURDAY - $day->dayOfWeek + 7) % 7;

        return $day->addDays($daysUntilSaturday)->startOfDay();
    }
}
