<?php

namespace Tests\Feature;

use App\Models\DataCorrectionRequest;
use App\Models\SupportKbArticle;
use App\Models\SupportReport;
use App\Models\SupportSlaPolicy;
use App\Models\SupportTeam;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Batch 10 — Support Center API (10 flow theo TEST_SCENARIOS). */
class Batch10SupportApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create(['name' => 'SA', 'email' => 'sa@x2bms.vn', 'password' => bcrypt('x'), 'account_type' => 'staff', 'is_platform_admin' => true]);
        SupportSlaPolicy::create(['code' => 'SLA-HIGH', 'name' => 'High', 'priority' => 'high', 'response_minutes' => 30, 'resolution_minutes' => 480]);
    }

    private function api(): static
    {
        return $this->actingAs($this->admin);
    }

    private function ticket(array $o = []): SupportTicket
    {
        return SupportTicket::create(array_merge(['ticket_no' => 'TKT-'.uniqid(), 'subject' => 'X', 'priority' => 'high', 'status' => 'open', 'sla_state' => 'within_sla'], $o));
    }

    private function dcr(array $o = []): DataCorrectionRequest
    {
        return DataCorrectionRequest::create(array_merge(['code' => 'DCR-'.uniqid(), 'data_type' => 'Khách hàng', 'affected_records' => 10, 'risk' => 'medium', 'status' => 'pending_approval', 'requested_by' => $this->admin->id], $o));
    }

    /** Flow 01 — create ticket (SLA starts). */
    public function test_create_ticket(): void
    {
        $res = $this->api()->postJson('/api/platform/support/tickets', ['subject' => 'Lỗi biểu đồ', 'priority' => 'high', 'description' => 'chi tiết']);
        $res->assertCreated()->assertJsonPath('status', 'new');
        $this->assertNotNull($res->json('sla_due_at'));
        $this->assertDatabaseHas('support_audit_logs', ['action' => 'ticket.created']);
    }

    /** Flow 02 — assign ticket. */
    public function test_assign_ticket(): void
    {
        $team = SupportTeam::create(['code' => 'L1', 'name' => 'L1', 'level' => 'L1']);
        $t = $this->ticket();
        $this->api()->postJson("/api/platform/support/tickets/{$t->id}/assign", ['team_id' => $team->id])->assertOk();
        $this->assertSame($team->id, $t->fresh()->team_id);
    }

    /** Flow 03 — escalate critical ticket. */
    public function test_escalate_ticket(): void
    {
        $t = $this->ticket(['priority' => 'critical']);
        $this->api()->postJson("/api/platform/support/tickets/{$t->id}/escalate", ['to_level' => 'L2', 'reason' => 'SLA breach'])
            ->assertOk()->assertJsonPath('status', 'escalated');
        $this->assertDatabaseHas('support_escalations', ['support_ticket_id' => $t->id, 'to_level' => 'L2']);
    }

    /** Flow 04 — close + reopen. */
    public function test_close_and_reopen_ticket(): void
    {
        $t = $this->ticket();
        $this->api()->postJson("/api/platform/support/tickets/{$t->id}/close", ['resolution_summary' => 'done', 'csat_score' => 5])->assertOk()->assertJsonPath('status', 'closed');
        $this->api()->postJson("/api/platform/support/tickets/{$t->id}/reopen")->assertOk()->assertJsonPath('status', 'reopened');
    }

    /** Flow 05 — create data correction linked to ticket. */
    public function test_create_data_correction(): void
    {
        $t = $this->ticket();
        $res = $this->api()->postJson('/api/platform/support/data-correction-requests', ['data_type' => 'Hóa đơn', 'support_ticket_id' => $t->id, 'risk' => 'high', 'affected_records' => 48]);
        $res->assertCreated()->assertJsonPath('status', 'pending_approval');
    }

    /** Flow 06/07 — high-risk needs 2 approvals; execute requires snapshot first. */
    public function test_high_risk_two_person_approval_and_snapshot_gate(): void
    {
        $dcr = $this->dcr(['risk' => 'high']);
        // first approval → still pending
        $this->api()->postJson("/api/platform/support/data-correction-requests/{$dcr->id}/approve")->assertOk()->assertJsonPath('status', 'pending_approval');
        // second approver
        $admin2 = User::create(['name' => 'SA2', 'email' => 'sa2@x2bms.vn', 'password' => bcrypt('x'), 'account_type' => 'staff', 'is_platform_admin' => true]);
        $this->actingAs($admin2)->postJson("/api/platform/support/data-correction-requests/{$dcr->id}/approve")->assertOk()->assertJsonPath('status', 'approved');
        // execute without snapshot → 422
        $this->api()->postJson("/api/platform/support/data-fix-wizard/{$dcr->id}/execute", ['reason' => 'go'])->assertStatus(422);
        // snapshot then execute
        $this->api()->postJson("/api/platform/support/data-fix-wizard/{$dcr->id}/create-snapshot")->assertCreated();
        $this->api()->postJson("/api/platform/support/data-fix-wizard/{$dcr->id}/execute", ['reason' => 'go'])->assertOk()->assertJsonPath('status', 'executed');
        $this->assertDatabaseHas('support_audit_logs', ['action' => 'data_fix.executed']);
    }

    /** Flow 08 — rollback executed data fix. */
    public function test_rollback_data_fix(): void
    {
        $dcr = $this->dcr(['status' => 'executed']);
        $this->api()->postJson("/api/platform/support/data-fix-wizard/{$dcr->id}/rollback", ['reason' => 'issue'])->assertOk()->assertJsonPath('status', 'rolled_back');
        $this->assertDatabaseHas('data_fix_rollbacks', ['data_correction_request_id' => $dcr->id]);
    }

    /** Flow 09 — KB create + publish. */
    public function test_kb_create_and_publish(): void
    {
        $res = $this->api()->postJson('/api/platform/support/knowledge-base/articles', ['title' => 'Runbook SMTP', 'body' => '<p>x</p>']);
        $res->assertCreated();
        $id = $res->json('id');
        $this->api()->postJson("/api/platform/support/knowledge-base/articles/{$id}/publish")->assertOk()->assertJsonPath('status', 'published');
        $this->api()->getJson('/api/platform/support/knowledge-base/articles?q=Runbook')->assertOk();
    }

    /** Flow 10 — resolution report + dashboard. */
    public function test_report_and_dashboard(): void
    {
        SupportReport::create(['code' => 'RPT', 'period' => '2026-06', 'type' => 'resolution', 'metrics_json' => ['tickets_resolved' => 1248, 'sla_compliance' => 96.8, 'csat' => 4.7]]);
        $this->ticket(['priority' => 'critical']);
        $this->api()->getJson('/api/platform/support/reports/resolution')->assertOk()->assertJsonPath('metrics_json.tickets_resolved', 1248);
        $this->api()->getJson('/api/platform/support/dashboard')->assertOk()->assertJsonStructure(['open_tickets', 'by_priority']);
    }

    /** Non platform-admin forbidden. */
    public function test_non_admin_forbidden(): void
    {
        $u = User::create(['name' => 'U', 'email' => 'u@x.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $this->actingAs($u)->getJson('/api/platform/support/dashboard')->assertForbidden();
    }
}
