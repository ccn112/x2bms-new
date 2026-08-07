<?php

namespace App\Services\Notifications;

use App\Enums\CommunicationApprovalStatus;
use App\Enums\CommunicationWorkflowStatus as WS;
use App\Models\Notification;
use App\Models\NotificationApproval;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Tuyến duyệt chiến dịch (maker-checker) — spec 09. Route resolve theo CONFIG
 * (config/x2_communication.php), KHÔNG hardcode role. send_now KHÔNG bypass duyệt.
 */
class NotificationApprovalService
{
    public function __construct(
        private readonly CampaignStateMachine $stateMachine,
        private readonly NotificationSnapshotService $snapshots,
    ) {}

    /**
     * Chọn tuyến duyệt khớp điều kiện (route đầu tiên match; default cuối cùng).
     *
     * @return array<string,mixed>
     */
    public function resolveRoute(Notification $n): array
    {
        $routes = (array) config('x2_communication.approval_routes', []);
        $audience = (int) $n->recipient_count;
        $paidCost = (float) $n->cost_estimate;
        $priority = (string) $n->priority;

        foreach ($routes as $route) {
            if ($this->routeMatches($route['conditions'] ?? [], $priority, $audience, $paidCost)) {
                return $route;
            }
        }

        throw new DomainException('Không tìm thấy tuyến duyệt phù hợp (thiếu route mặc định trong config).');
    }

    /** Gửi duyệt: chốt snapshot + tạo approval/steps + chuyển campaign → pending_approval. */
    public function requestApproval(Notification $n, ?int $actorId = null): NotificationApproval
    {
        return DB::transaction(function () use ($n, $actorId) {
            $route = $this->resolveRoute($n);
            $snapshot = $this->snapshots->capture($n, $actorId);
            $steps = $route['steps'] ?? [];

            $approval = NotificationApproval::create([
                'notification_id' => $n->id,
                'route_key' => $route['key'] ?? 'approval-default',
                'status' => CommunicationApprovalStatus::Requested,
                'current_step' => 1,
                'total_steps' => count($steps),
                'correlation_id' => (string) Str::uuid(),
                'snapshot_hash' => $snapshot->hash,
                'requested_by_id' => $actorId,
                'requested_at' => now(),
                'due_at' => $this->stepDue($steps[0] ?? []),
            ]);

            foreach ($steps as $i => $step) {
                $approval->steps()->create([
                    'step_no' => $i + 1,
                    'role' => $step['role'] ?? 'bql_manager',
                    'status' => CommunicationApprovalStatus::Requested,
                    'sla_due_at' => $this->stepDue($step),
                ]);
            }

            $this->stateMachine->transition($n, WS::PendingApproval, ['actor_id' => $actorId]);

            return $approval->load('steps');
        });
    }

    /**
     * Duyệt/từ chối/yêu cầu sửa một bước. Maker-checker: người tạo không tự duyệt.
     * decision: approved|rejected|changes_requested.
     */
    public function act(NotificationApproval $approval, int $actorId, string $decision, ?string $reason = null): NotificationApproval
    {
        if ($decision === 'approved' && $approval->requested_by_id === $actorId) {
            throw new DomainException('Người tạo không được tự duyệt chiến dịch của mình (maker-checker).');
        }

        return DB::transaction(function () use ($approval, $actorId, $decision, $reason) {
            $n = $approval->notification;
            $step = $approval->currentStep();
            if (! $step || $step->status !== CommunicationApprovalStatus::Requested) {
                throw new DomainException('Bước duyệt hiện tại không hợp lệ.');
            }

            $status = match ($decision) {
                'approved' => CommunicationApprovalStatus::Approved,
                'rejected' => CommunicationApprovalStatus::Rejected,
                'changes_requested' => CommunicationApprovalStatus::ChangesRequested,
                default => throw new DomainException("Quyết định không hợp lệ: {$decision}."),
            };

            $step->forceFill([
                'status' => $status, 'actor_id' => $actorId, 'acted_at' => now(), 'reason' => $reason,
            ])->save();

            if ($decision === 'rejected') {
                $approval->forceFill(['status' => $status, 'resolved_at' => now()])->save();
                $this->stateMachine->transition($n, WS::Rejected, ['actor_id' => $actorId]);
            } elseif ($decision === 'changes_requested') {
                $approval->forceFill(['status' => $status, 'resolved_at' => now()])->save();
                $this->stateMachine->transition($n, WS::ChangesRequested, ['actor_id' => $actorId]);
            } else { // approved
                if ($approval->current_step >= $approval->total_steps) {
                    $approval->forceFill(['status' => $status, 'resolved_at' => now()])->save();
                    $this->stateMachine->transition($n, WS::Approved, ['actor_id' => $actorId]);
                } else {
                    $approval->increment('current_step');
                }
            }

            return $approval->fresh('steps');
        });
    }

    private function routeMatches(array $cond, string $priority, int $audience, float $paidCost): bool
    {
        if (! empty($cond['priority']) && ! in_array($priority, (array) $cond['priority'], true)) {
            return false;
        }
        if (isset($cond['min_audience']) && $audience < $cond['min_audience']) {
            return false;
        }
        if (isset($cond['max_audience']) && $audience > $cond['max_audience']) {
            return false;
        }
        if (isset($cond['paid_cost_gt']) && $paidCost <= $cond['paid_cost_gt']) {
            return false;
        }
        if (isset($cond['paid_cost_lte']) && $paidCost > $cond['paid_cost_lte']) {
            return false;
        }

        return true;
    }

    private function stepDue(array $step): ?\Illuminate\Support\Carbon
    {
        if (isset($step['sla_minutes'])) {
            return now()->addMinutes((int) $step['sla_minutes']);
        }
        if (isset($step['sla_hours'])) {
            return now()->addHours((int) $step['sla_hours']);
        }

        return null;
    }
}
