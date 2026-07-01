<?php

namespace App\Http\Controllers\Platform\Integration;

use App\Filament\Concerns\WritesIntegrationAudit;
use App\Http\Controllers\Controller;
use App\Models\IntegrationRetryJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Batch 08 — Retry queue API (list/retry-now/skip/dead-letter). */
class IntegrationRetryQueueController extends Controller
{
    use WritesIntegrationAudit;

    public function index(Request $request): JsonResponse
    {
        return response()->json(IntegrationRetryJob::with('endpoint')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate((int) $request->get('per_page', 20)));
    }

    public function retryNow(IntegrationRetryJob $job): JsonResponse
    {
        $ok = $job->attempt_no + 1 < $job->max_attempts;
        $job->update([
            'status' => $ok ? 'succeeded' : 'failed', 'attempt_no' => $job->attempt_no + 1,
            'next_retry_at' => null, 'last_error' => $ok ? null : 'Retry failed',
        ]);
        $this->integrationAudit('retry_job.retried', $job, after: ['result' => $job->status]);

        return response()->json($job->fresh());
    }

    public function skip(IntegrationRetryJob $job): JsonResponse
    {
        $job->update(['status' => 'skipped', 'next_retry_at' => null]);
        $this->integrationAudit('retry_job.skipped', $job);

        return response()->json($job->fresh());
    }

    public function deadLetter(IntegrationRetryJob $job): JsonResponse
    {
        $job->update(['status' => 'dead_letter', 'next_retry_at' => null]);
        $this->integrationAudit('retry_job.dead_letter', $job);

        return response()->json($job->fresh());
    }
}
