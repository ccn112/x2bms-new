<?php

namespace Tests\Feature\Communication;

use App\Enums\CommunicationContentType as CT;
use App\Http\Resources\Api\V1\NotificationDetailResource;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Tenant;
use App\Services\Notifications\ContentSubtypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** T5 — hợp đồng API cư dân: field cũ KHÔNG đổi (backward compat) + field mới additive. */
class CommunicationApiContractTest extends TestCase
{
    use RefreshDatabase;

    /** Key hợp đồng đang được app dùng — KHÔNG được mất. */
    private const LEGACY_LIST_KEYS = [
        'id', 'kind', 'category', 'subtype', 'action_key', 'entity', 'requires_ack',
        'acknowledged', 'title', 'summary', 'cover_url', 'priority', 'is_pinned', 'is_read',
        'comment_count', 'created_at',
    ];

    public function test_list_resource_giu_key_cu_va_them_content_type(): void
    {
        $n = $this->campaign(CT::Announcement);
        $arr = (new NotificationResource($n))->toArray(request());

        foreach (self::LEGACY_LIST_KEYS as $key) {
            $this->assertArrayHasKey($key, $arr, "mất key hợp đồng cũ: {$key}");
        }
        $this->assertArrayHasKey('content_type', $arr);
        $this->assertSame('announcement', $arr['content_type']);
        $this->assertArrayHasKey('cta', $arr);
        $this->assertArrayHasKey('allow_feedback', $arr);
    }

    public function test_detail_event_tra_summary(): void
    {
        $n = $this->campaign(CT::Event);
        app(ContentSubtypeService::class)->syncEntity($n, CT::Event, [
            'venue' => 'Sân', 'starts_at' => now()->addDays(3)->toDateTimeString(), 'capacity' => 100,
        ]);
        $n->save();

        $arr = (new NotificationDetailResource($n->fresh()))->toArray(request());
        $this->assertSame('event', $arr['content_type']);
        $this->assertNotNull($arr['event']);
        $this->assertSame('Sân', $arr['event']['venue']);
        $this->assertSame(100, $arr['event']['capacity']);
        $this->assertNull($arr['poll']);
    }

    public function test_detail_poll_tra_options(): void
    {
        $n = $this->campaign(CT::Poll);
        app(ContentSubtypeService::class)->syncEntity($n, CT::Poll, [
            'question' => 'Chọn giờ?', 'options' => [['key' => 'a', 'label' => 'Sáng'], ['key' => 'b', 'label' => 'Chiều']],
        ]);
        $n->save();

        $arr = (new NotificationDetailResource($n->fresh()))->toArray(request());
        $this->assertSame('poll', $arr['content_type']);
        $this->assertNotNull($arr['poll']);
        $this->assertCount(2, $arr['poll']['options']);
        $this->assertSame('a', $arr['poll']['options'][0]['key']);
    }

    private function campaign(CT $type): Notification
    {
        $t = Tenant::create(['code' => 'T-'.$type->value, 'name' => 'T']);
        $p = Project::create(['tenant_id' => $t->id, 'code' => 'P-'.$type->value, 'name' => 'P']);

        return Notification::create([
            'owner_level' => 'project', 'tenant_id' => $t->id, 'project_id' => $p->id,
            'content_type' => $type->value, 'type' => 'announcement', 'workflow_status' => 'completed',
            'status' => 'published', 'title' => 'CD '.$type->value, 'summary' => 'tóm tắt',
            'body' => 'nội dung', 'priority' => 'normal', 'published_at' => now(),
        ]);
    }
}
