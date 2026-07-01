<?php

namespace Tests\Feature;

use App\Models\BillingAdjustment;
use App\Models\BillingAuditLog;
use App\Models\BillingInvoice;
use App\Models\PassThroughWallet;
use App\Models\Plan;
use App\Models\QuotaAlert;
use App\Models\SubscriptionAddon;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\UsagePeriod;
use App\Models\UsageRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Batch 07 — API SaaS billing (12 flow theo TEST_SCENARIOS). */
class Batch07BillingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tenant $tenant;

    private Plan $popular;

    private Plan $full;

    private Plan $intelligent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'SuperAdmin', 'email' => 'sa@x2bms.vn', 'password' => bcrypt('secret'),
            'account_type' => 'staff', 'is_platform_admin' => true,
        ]);
        $this->tenant = Tenant::create(['code' => 'TEN-T01', 'name' => 'Green Home PM', 'status' => 'active']);
        $this->popular = Plan::create(['code' => 'popular', 'name' => 'Pho bien', 'monthly_base_price' => 2_500_000]);
        $this->full = Plan::create(['code' => 'full', 'name' => 'Day du', 'monthly_base_price' => 8_500_000]);
        $this->intelligent = Plan::create(['code' => 'intelligent', 'name' => 'Thong minh', 'monthly_base_price' => 18_500_000]);
    }

    private function sub(array $override = []): TenantSubscription
    {
        return TenantSubscription::create(array_merge([
            'tenant_id' => $this->tenant->id, 'plan_id' => $this->full->id, 'status' => 'active',
            'billing_cycle' => 'monthly', 'start_date' => now()->subMonths(2), 'end_date' => now()->addMonths(10),
            'auto_renew' => true, 'mrr' => 8_500_000, 'arr' => 102_000_000, 'currency' => 'VND',
        ], $override));
    }

    public function test_non_platform_admin_is_forbidden(): void
    {
        $staff = User::create(['name' => 'Staff', 'email' => 's@x.vn', 'password' => bcrypt('x'), 'account_type' => 'staff', 'is_platform_admin' => false]);
        $this->actingAs($staff)->getJson('/api/platform/billing/revenue-dashboard')->assertForbidden();
    }

    public function test_flow01_create_subscription(): void
    {
        $res = $this->actingAs($this->admin)->postJson('/api/platform/billing/subscriptions', [
            'tenant_id' => $this->tenant->id, 'plan_id' => $this->popular->id, 'billing_cycle' => 'monthly', 'mode' => 'active',
        ])->assertCreated();

        $this->assertDatabaseHas('tenant_subscriptions', ['tenant_id' => $this->tenant->id, 'plan_id' => $this->popular->id, 'status' => 'active']);
        $this->assertDatabaseHas('subscription_items', ['subscription_id' => $res->json('id'), 'item_type' => 'plan']);
        $this->assertDatabaseHas('billing_audit_logs', ['action' => 'subscription.create']);
    }

    public function test_flow02_upgrade_recalculates_mrr(): void
    {
        $sub = $this->sub(['plan_id' => $this->full->id, 'mrr' => 8_500_000]);
        $this->actingAs($this->admin)->postJson("/api/platform/billing/subscriptions/{$sub->id}/upgrade", ['plan_id' => $this->intelligent->id])
            ->assertOk();
        $this->assertEquals(18_500_000, (float) $sub->fresh()->mrr);
        $this->assertEquals($this->intelligent->id, $sub->fresh()->plan_id);
        $this->assertDatabaseHas('billing_audit_logs', ['action' => 'subscription.upgrade', 'entity_id' => $sub->id]);
    }

    public function test_flow03_add_addon_increases_mrr(): void
    {
        $sub = $this->sub(['mrr' => 18_500_000]);
        $this->actingAs($this->admin)->postJson("/api/platform/billing/subscriptions/{$sub->id}/addons", [
            'name' => 'AI Token Pack', 'mrr' => 20_000_000, 'wallet_type' => 'ai_token',
        ])->assertCreated();
        $this->assertEquals(38_500_000, (float) $sub->fresh()->mrr);
        $this->assertDatabaseHas('subscription_addons', ['subscription_id' => $sub->id, 'name' => 'AI Token Pack']);
    }

    public function test_flow04_05_lock_usage_and_generate_overage_alert(): void
    {
        $period = UsagePeriod::create(['code' => 'USAGE-2026-05', 'period_start' => '2026-05-01', 'period_end' => '2026-05-31', 'status' => 'open']);
        UsageRecord::create([
            'usage_period_id' => $period->id, 'tenant_id' => $this->tenant->id, 'meter_type' => 'ai_tokens',
            'usage_value' => 28_600_000, 'included_limit' => 20_000_000, 'overage_value' => 8_600_000, 'overage_amount' => 96_000_000, 'status' => 'draft',
        ]);

        $this->actingAs($this->admin)->postJson("/api/platform/billing/usage-periods/{$period->id}/lock")
            ->assertOk()->assertJsonPath('status', 'locked');
        $this->actingAs($this->admin)->postJson("/api/platform/billing/usage-periods/{$period->id}/generate-alerts")
            ->assertOk()->assertJsonPath('created', 1);
        $this->assertDatabaseHas('quota_alerts', ['tenant_id' => $this->tenant->id, 'meter_type' => 'ai_tokens', 'status' => 'open']);
    }

    public function test_flow06_convert_quota_alert_to_addon(): void
    {
        $sub = $this->sub();
        $alert = QuotaAlert::create([
            'code' => 'QA-X', 'tenant_id' => $this->tenant->id, 'meter_type' => 'sms_count',
            'usage_value' => 12_800, 'included_limit' => 10_000, 'over_percent' => 28, 'estimated_fee' => 12_800_000, 'status' => 'open',
        ]);
        $this->actingAs($this->admin)->postJson("/api/platform/billing/quota-alerts/{$alert->id}/convert-to-addon")
            ->assertOk()->assertJsonPath('status', 'converted_to_addon');
        $this->assertTrue(SubscriptionAddon::where('subscription_id', $sub->id)->exists());
    }

    public function test_flow07_08_generate_invoice_and_partial_payment(): void
    {
        $sub = $this->sub(['mrr' => 200_000_000]);
        $period = UsagePeriod::create(['code' => 'USAGE-2026-05', 'period_start' => '2026-05-01', 'period_end' => '2026-05-31', 'status' => 'locked', 'locked_at' => now()]);
        UsageRecord::create(['usage_period_id' => $period->id, 'tenant_id' => $this->tenant->id, 'meter_type' => 'storage_gb', 'usage_value' => 100, 'included_limit' => 50, 'overage_value' => 50, 'overage_amount' => 60_000_000, 'status' => 'locked']);

        $this->actingAs($this->admin)->postJson('/api/platform/billing/invoices/generate', ['period_id' => $period->id])
            ->assertOk()->assertJsonPath('created', 1);
        $inv = BillingInvoice::where('subscription_id', $sub->id)->firstOrFail();
        $this->assertDatabaseHas('billing_invoice_lines', ['invoice_id' => $inv->id, 'line_type' => 'usage_overage']);

        // Partial payment.
        $this->actingAs($this->admin)->postJson("/api/platform/billing/invoices/{$inv->id}/payments", ['amount' => 100_000_000])
            ->assertOk()->assertJsonPath('status', 'partially_paid');
        $this->assertGreaterThan(0, (float) $inv->fresh()->remaining_amount);
    }

    public function test_flow09_wallet_deduction(): void
    {
        $w = PassThroughWallet::create(['tenant_id' => $this->tenant->id, 'wallet_type' => 'sms', 'balance' => 216_800_000, 'low_balance_threshold' => 5_000_000]);
        $this->actingAs($this->admin)->postJson("/api/platform/billing/wallets/{$w->id}/deduct", ['amount' => 2_400_000])->assertOk();
        $this->assertEquals(214_400_000, (float) $w->fresh()->balance);
        $this->assertDatabaseHas('pass_through_transactions', ['wallet_id' => $w->id, 'transaction_type' => 'deduct']);
    }

    public function test_flow10_adjustment_approve_and_credit_note(): void
    {
        $sub = $this->sub();
        $inv = BillingInvoice::create([
            'invoice_no' => 'INV-T-1', 'tenant_id' => $this->tenant->id, 'subscription_id' => $sub->id, 'period' => '2026-05',
            'status' => 'sent', 'total_amount' => 10_000_000, 'paid_amount' => 0, 'remaining_amount' => 10_000_000,
        ]);
        $adj = BillingAdjustment::create([
            'case_id' => 'ADJ-T-1', 'tenant_id' => $this->tenant->id, 'invoice_id' => $inv->id,
            'adjustment_type' => 'overcharge_sms', 'amount' => 4_250_000, 'status' => 'pending_approval',
        ]);
        $this->actingAs($this->admin)->postJson("/api/platform/billing/adjustments/{$adj->id}/approve")->assertOk();
        $this->actingAs($this->admin)->postJson("/api/platform/billing/adjustments/{$adj->id}/credit-note")->assertCreated();
        $this->assertDatabaseHas('credit_notes', ['adjustment_id' => $adj->id]);
        $this->assertEquals(5_750_000, (float) $inv->fresh()->remaining_amount);
    }

    public function test_flow11_12_suspend_and_restore(): void
    {
        $sub = $this->sub();
        $this->actingAs($this->admin)->postJson("/api/platform/billing/subscriptions/{$sub->id}/suspend")
            ->assertOk()->assertJsonPath('status', 'suspended');
        $this->actingAs($this->admin)->postJson("/api/platform/billing/subscriptions/{$sub->id}/resume")
            ->assertOk()->assertJsonPath('status', 'active');
        $this->assertDatabaseHas('billing_audit_logs', ['action' => 'subscription.suspend', 'entity_id' => $sub->id]);
        $this->assertDatabaseHas('billing_audit_logs', ['action' => 'subscription.resume', 'entity_id' => $sub->id]);
    }
}
