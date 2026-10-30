<?php

namespace App\Console\Commands;

use App\Models\Expense;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateRecurringExpenses extends Command
{
    protected $signature = 'expenses:generate-recurring {--date=}';
    protected $description = 'Create due monthly recurring expenses for accounting approval';

    public function handle(): int
    {
        $today = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : today();
        $created = 0;
        Expense::where('recurring_enabled', true)->whereDate('next_recurrence_date', '<=', $today)
            ->where(fn ($q) => $q->whereNull('recurring_until')->orWhereDate('recurring_until', '>=', $today))
            ->orderBy('next_recurrence_date')->chunkById(100, function ($templates) use ($today, &$created) {
                foreach ($templates as $template) {
                    DB::transaction(function () use ($template, $today, &$created) {
                        $template = Expense::lockForUpdate()->findOrFail($template->id);
                        while ($template->recurring_enabled && $template->next_recurrence_date && $template->next_recurrence_date->lte($today)) {
                            $occurrence = $template->next_recurrence_date->copy();
                            if ($template->recurring_until && $occurrence->gt($template->recurring_until)) {
                                $template->update(['recurring_enabled' => false]); break;
                            }
                            $parent = $template->recurring_parent_id ?: $template->id;
                            if (! Expense::where('recurring_parent_id', $parent)->whereDate('recurring_for_month', $occurrence->copy()->startOfMonth())->exists()) {
                                $copy = $template->replicate(['expense_no','accounting_entry_id','receipt_path','invoice_path','quotation_path','approval_status','created_at','updated_at']);
                                $copy->fill(['expense_no'=>$this->number(),'expense_date'=>$occurrence,'approval_status'=>'pending','needs_review'=>false,
                                    'accounting_entry_id'=>null,'receipt_path'=>null,'invoice_path'=>null,'quotation_path'=>null,'recurring_enabled'=>false,
                                    'next_recurrence_date'=>null,'recurring_parent_id'=>$parent,'recurring_for_month'=>$occurrence->copy()->startOfMonth(),
                                    'description'=>trim(($template->description ?: ucfirst($template->category)).' - recurring charge '.$occurrence->format('M Y'))]);
                                $copy->save(); $created++;
                            }
                            $next = $occurrence->addMonthNoOverflow()->day(min($template->recurrence_day ?: $occurrence->day, $occurrence->daysInMonth));
                            $template->update(['next_recurrence_date'=>$next]);
                        }
                    });
                }
            });
        $this->info("Created {$created} recurring expense(s).");
        return self::SUCCESS;
    }

    private function number(): string
    {
        do $number = 'EXP-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)); while (Expense::where('expense_no', $number)->exists());
        return $number;
    }
}
