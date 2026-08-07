<?php

namespace Tests\Feature\Communication;

use App\Enums\CommunicationContentType as CT;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Tenant;
use App\Services\Notifications\ContentSubtypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/** T2 — content_type: link Event/Poll canonical (không nhân đôi) + validate subtype. */
class ContentSubtypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_tao_entity_va_gan_link(): void
    {
        $n = $this->campaign(CT::Event);
        app(ContentSubtypeService::class)->syncEntity($n, CT::Event, [
            'venue' => 'Sân trung tâm', 'starts_at' => now()->addDays(5)->toDateTimeString(),
            'duration_minutes' => 120, 'capacity' => 300, 'qr_checkin' => true, 'fee_amount' => 0,
        ]);
        $n->save();

        $this->assertSame('event', $n->entity_type);
        $event = $n->fresh()->contentEvent();
        $this->assertNotNull($event);
        $this->assertSame('Sân trung tâm', $event->location);
        $this->assertSame(300, $event->capacity);
        $this->assertNotNull($event->ends_at);
        $this->assertDatabaseHas('events', ['id' => $event->id, 'tenant_id' => $n->tenant_id]);
    }

    public function test_poll_tao_entity_va_options(): void
    {
        $n = $this->campaign(CT::Poll);
        app(ContentSubtypeService::class)->syncEntity($n, CT::Poll, [
            'question' => 'Khung giờ gym?', 'allow_multiple' => false, 'vote_scope' => 'resident',
            'options' => [['key' => 'a', 'label' => '5:30-22:00'], ['key' => 'b', 'label' => '6:00-23:00']],
        ]);
        $n->save();

        $this->assertSame('poll', $n->entity_type);
        $poll = $n->fresh()->contentPoll();
        $this->assertNotNull($poll);
        $this->assertSame('single', $poll->type);
        $this->assertCount(2, $poll->options);
        $this->assertSame('a', $poll->options->first()->option_key);
    }

    public function test_validate_poll_thieu_lua_chon(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(ContentSubtypeService::class)->validate(CT::Poll, ['question' => 'x', 'options' => [['label' => 'chỉ 1']]]);
    }

    public function test_validate_event_thieu_venue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(ContentSubtypeService::class)->validate(CT::Event, ['starts_at' => now()->toDateTimeString()]);
    }

    public function test_news_luu_meta(): void
    {
        $n = $this->campaign(CT::News);
        app(ContentSubtypeService::class)->syncEntity($n, CT::News, ['category' => 'community', 'author' => 'BQL', 'featured' => true]);
        $n->save();

        $meta = $n->fresh()->content_meta;
        $this->assertSame('community', $meta['category']);
        $this->assertTrue($meta['featured']);
    }

    private function campaign(CT $type): Notification
    {
        $t = Tenant::create(['code' => 'T-'.$type->value, 'name' => 'T']);
        $p = Project::create(['tenant_id' => $t->id, 'code' => 'P-'.$type->value, 'name' => 'P']);

        return Notification::create([
            'owner_level' => 'project', 'tenant_id' => $t->id, 'project_id' => $p->id,
            'content_type' => $type->value, 'workflow_status' => 'draft', 'status' => 'draft',
            'title' => 'CD '.$type->value, 'body' => 'Nội dung', 'priority' => 'normal',
        ]);
    }
}
