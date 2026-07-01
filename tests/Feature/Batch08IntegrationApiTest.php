<?php

namespace Tests\Feature;

use App\Models\IntegrationApiKey;
use App\Models\IntegrationCategory;
use App\Models\IntegrationConnection;
use App\Models\IntegrationCredential;
use App\Models\IntegrationEvent;
use App\Models\IntegrationRetryJob;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Models\WebhookEventGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Batch 08 — Integration Center API (12 flow theo TEST_SCENARIOS). */
class Batch08IntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create([
            'name' => 'SuperAdmin', 'email' => 'sa@x2bms.vn', 'password' => bcrypt('secret'),
            'account_type' => 'staff', 'is_platform_admin' => true,
        ]);
        IntegrationCategory::create(['code' => 'communication', 'name' => 'Truyền thông']);
    }

    private function api(): static
    {
        return $this->actingAs($this->admin);
    }

    private function connection(array $o = []): IntegrationConnection
    {
        return IntegrationConnection::create(array_merge([
            'code' => 'CONN-'.uniqid(), 'name' => 'Email SMTP', 'provider_code' => 'smtp',
            'environment' => 'production', 'status' => 'active', 'sla_status' => 'healthy',
        ], $o));
    }

    /** Flow 01 — create + test external connection. */
    public function test_create_and_test_connection(): void
    {
        $res = $this->api()->postJson('/api/platform/integrations/connections', [
            'name' => 'Email SMTP', 'provider_code' => 'smtp', 'environment' => 'production',
        ]);
        $res->assertCreated()->assertJsonPath('status', 'disabled');
        $id = $res->json('id');

        $this->api()->postJson("/api/platform/integrations/connections/{$id}/test")
            ->assertOk()->assertJsonPath('result', 'success');

        $this->assertSame('active', IntegrationConnection::find($id)->status);
        $this->assertDatabaseHas('integration_audit_logs', ['action' => 'connection.created']);
        $this->assertDatabaseHas('integration_audit_logs', ['action' => 'connection.tested']);
    }

    /** Flow 03 — rotate connection secret, shown once, old rotated. */
    public function test_rotate_connection_secret(): void
    {
        $conn = $this->connection();
        IntegrationCredential::create(['connection_id' => $conn->id, 'credential_type' => 'api_key', 'status' => 'valid', 'encrypted_payload' => 'x', 'masked_summary' => 'sk_…aaaa']);

        $res = $this->api()->postJson("/api/platform/integrations/connections/{$conn->id}/rotate-secret");
        $res->assertOk()->assertJsonStructure(['secret', 'masked']);

        $this->assertDatabaseHas('integration_credentials', ['connection_id' => $conn->id, 'status' => 'rotated']);
        $this->assertSame(1, IntegrationCredential::where('connection_id', $conn->id)->where('status', 'valid')->count());
    }

    /** Flow 04 + 05 — create API key (secret once) then revoke. */
    public function test_create_and_revoke_api_key(): void
    {
        $res = $this->api()->postJson('/api/platform/integrations/api-keys', [
            'name' => 'Mobile App', 'environment' => 'production',
            'scopes' => ['resident:read', 'work_order:write'], 'require_hmac' => true,
        ]);
        $res->assertCreated()->assertJsonStructure(['client_id', 'secret']);
        $id = $res->json('api_key.id');
        $this->assertDatabaseCount('integration_api_key_scopes', 2);

        $this->api()->postJson("/api/platform/integrations/api-keys/{$id}/revoke", ['reason' => 'compromised'])
            ->assertOk()->assertJsonPath('status', 'revoked');
        $this->assertDatabaseHas('integration_audit_logs', ['action' => 'api_key.revoked']);
    }

    /** Flow 12 — suspend then resume API key. */
    public function test_suspend_and_resume_api_key(): void
    {
        $key = IntegrationApiKey::create(['name' => 'K', 'client_id' => 'clt_'.uniqid(), 'environment' => 'production', 'status' => 'active']);
        $this->api()->postJson("/api/platform/integrations/api-keys/{$key->id}/suspend")->assertOk()->assertJsonPath('status', 'suspended');
        $this->api()->postJson("/api/platform/integrations/api-keys/{$key->id}/resume")->assertOk()->assertJsonPath('status', 'active');
    }

    /** Flow 06 + 07 — create webhook then test it (activates + logs delivery). */
    public function test_create_and_test_webhook(): void
    {
        $grp = WebhookEventGroup::create(['code' => 'work_order', 'name' => 'Work Order']);
        $res = $this->api()->postJson('/api/platform/integrations/webhooks', [
            'endpoint_name' => '/api/v1/work-order', 'url' => 'https://partner.com/wh', 'event_group_id' => $grp->id, 'signature_type' => 'HMAC',
        ]);
        $res->assertCreated();
        $id = $res->json('id');

        $this->api()->postJson("/api/platform/integrations/webhooks/{$id}/test", ['event' => 'work_order.updated'])
            ->assertOk()->assertJsonPath('http_status', 200)->assertJsonPath('signature_verified', true);

        $this->assertSame('active', WebhookEndpoint::find($id)->status);
        $this->assertDatabaseHas('webhook_delivery_attempts', ['webhook_endpoint_id' => $id, 'status' => 'success']);
    }

    /** Flow 08 — retry failed event via retry queue. */
    public function test_retry_now(): void
    {
        $job = IntegrationRetryJob::create(['event_id' => 'evt_x', 'source' => 'Webhook Gateway', 'status' => 'failed', 'attempt_no' => 1, 'max_attempts' => 5]);
        $this->api()->postJson("/api/platform/integrations/retry-queue/{$job->id}/retry-now")
            ->assertOk()->assertJsonPath('status', 'succeeded');
        $this->assertSame(2, $job->fresh()->attempt_no);
    }

    /** Flow 09 — replay event is idempotent. */
    public function test_replay_is_idempotent(): void
    {
        $ev = IntegrationEvent::create(['event_id' => 'evt_dup', 'source' => 'VNPay', 'event_type' => 'payment.paid', 'status' => 'failed']);
        $this->api()->postJson("/api/platform/integrations/events/{$ev->id}/replay")->assertCreated();
        $this->api()->postJson("/api/platform/integrations/events/{$ev->id}/replay")->assertOk(); // already queued
        $this->assertSame(1, IntegrationRetryJob::where('event_id', 'evt_dup')->count());
    }

    /** Flow 10 — enforce HMAC flags unsigned endpoints. */
    public function test_enforce_hmac(): void
    {
        WebhookEndpoint::create(['code' => 'WH-1', 'endpoint_name' => 'a', 'url' => 'https://a.com', 'signature_type' => 'none', 'status' => 'active']);
        $this->api()->postJson('/api/platform/integrations/security-settings/enforce-hmac')
            ->assertOk()->assertJsonPath('flagged_unsigned', 1);
        $this->assertDatabaseHas('integration_audit_logs', ['action' => 'security.enforce_hmac']);
    }

    /** Flow 11 — emergency disable suspends everything (requires reason + platform admin). */
    public function test_emergency_disable(): void
    {
        $this->connection(['status' => 'active']);
        IntegrationApiKey::create(['name' => 'K', 'client_id' => 'clt_'.uniqid(), 'environment' => 'production', 'status' => 'active']);
        WebhookEndpoint::create(['code' => 'WH-2', 'endpoint_name' => 'b', 'url' => 'https://b.com', 'status' => 'active']);

        $this->api()->postJson('/api/platform/integrations/security-settings/emergency-disable')->assertStatus(422); // reason required
        $this->api()->postJson('/api/platform/integrations/security-settings/emergency-disable', ['reason' => 'breach'])->assertOk();

        $this->assertSame(0, IntegrationConnection::where('status', 'active')->count());
        $this->assertSame(0, IntegrationApiKey::where('status', 'active')->count());
        $this->assertSame(0, WebhookEndpoint::where('status', 'active')->count());
        $this->assertDatabaseHas('integration_audit_logs', ['action' => 'security.emergency_disable']);
    }

    /** Overview + audit endpoints. */
    public function test_overview_and_audit_endpoints(): void
    {
        $this->connection();
        $this->api()->getJson('/api/platform/integrations/overview')->assertOk()->assertJsonStructure(['connections', 'events_by_status']);
        $this->api()->getJson('/api/platform/integrations/audit-logs')->assertOk();
    }

    /** Non platform-admin is rejected by middleware. */
    public function test_non_admin_forbidden(): void
    {
        $user = User::create(['name' => 'U', 'email' => 'u@x.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $this->actingAs($user)->getJson('/api/platform/integrations/overview')->assertForbidden();
    }
}
