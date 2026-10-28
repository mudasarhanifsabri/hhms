<?php

namespace App\Jobs;

use App\Models\BookingInspection;
use App\Support\InspectionReport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GenerateInspectionReport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;
    public int $tries = 1;

    public function __construct(public string $inspectionId) {}

    public function handle(): void
    {
        $inspection = BookingInspection::findOrFail($this->inspectionId);
        $this->update(3, 'Starting report generation');
        InspectionReport::generate($inspection, fn (int $percent, string $message) => $this->update($percent, $message));
        $this->update(100, 'Report ready', 'ready');
    }

    public function failed(?Throwable $exception): void
    {
        Cache::put($this->cacheKey(), [
            'status' => 'failed',
            'percent' => 100,
            'message' => 'Report generation failed. Please try again or contact support.',
        ], now()->addDay());
    }

    private function update(int $percent, string $message, string $status = 'processing'): void
    {
        Cache::put($this->cacheKey(), compact('status', 'percent', 'message'), now()->addDay());
    }

    private function cacheKey(): string
    {
        return 'inspection-report:'.$this->inspectionId;
    }
}
