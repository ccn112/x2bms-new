<?php

namespace App\Http\Controllers\Platform\Support;

use App\Filament\Concerns\WritesSupportAudit;
use App\Http\Controllers\Controller;
use App\Models\SupportAuditLog;
use App\Models\SupportKbArticle;
use App\Models\SupportReport;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Batch 10 — Dashboard, KB, reports, audit API. */
class SupportCenterController extends Controller
{
    use WritesSupportAudit;

    public function dashboard(): JsonResponse
    {
        $snapshot = (array) (SupportReport::where('code', 'DASH-CURRENT')->value('metrics_json') ?? []);

        return response()->json([
            'open_tickets' => SupportTicket::whereNotIn('status', ['closed', 'resolved'])->count(),
            'by_priority' => SupportTicket::selectRaw('priority, count(*) c')->groupBy('priority')->pluck('c', 'priority'),
            'by_status' => SupportTicket::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
            'snapshot' => $snapshot,
        ]);
    }

    public function report(): JsonResponse
    {
        return response()->json(SupportReport::where('type', 'resolution')->latest()->first());
    }

    public function auditLogs(Request $request): JsonResponse
    {
        return response()->json(SupportAuditLog::with('actor')
            ->when($request->action, fn ($q, $a) => $q->where('action', $a))
            ->latest('created_at')->paginate((int) $request->get('per_page', 25)));
    }

    public function kbIndex(Request $request): JsonResponse
    {
        return response()->json(SupportKbArticle::with('category')
            ->when($request->q, fn ($query, $q) => $query->where('title', 'like', "%{$q}%"))
            ->when($request->status, fn ($query, $s) => $query->where('status', $s))
            ->orderByDesc('views')->paginate((int) $request->get('per_page', 20)));
    }

    public function kbStore(Request $request): JsonResponse
    {
        $data = $request->validate(['title' => 'required|string|max:255', 'category_id' => 'nullable|exists:support_kb_categories,id', 'body' => 'nullable|string']);
        $art = SupportKbArticle::create($data + ['code' => 'KB-SUP-'.strtoupper(Str::random(4)), 'status' => 'draft', 'author_id' => auth()->id()]);
        $this->supportAudit('kb.created', $art, after: ['code' => $art->code]);

        return response()->json($art, 201);
    }

    public function kbPublish(SupportKbArticle $article): JsonResponse
    {
        $article->update(['status' => 'published', 'published_at' => now()]);
        $this->supportAudit('kb.published', $article);

        return response()->json($article->fresh());
    }

    public function kbArchive(SupportKbArticle $article): JsonResponse
    {
        $article->update(['status' => 'archived']);
        $this->supportAudit('kb.archived', $article);

        return response()->json($article->fresh());
    }
}
