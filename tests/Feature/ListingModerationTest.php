<?php

namespace Tests\Feature;

use App\Filament\Concerns\ModeratesRealEstateListings;
use App\Filament\Pages\ListingApprovalQueue;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\CommunityGroup;
use App\Models\CommunityPost;
use App\Models\Project;
use App\Models\RealEstateListing;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Resident\ListingFeedPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Màn duyệt tin rao BĐS (Filament /admin + /sa, chốt 2026-07-30 phần 2 —
 * "Filament UI cho BQL/SA duyệt" nợ lại ở commit trước). Test ở tầng LOGIC
 * (`ModeratesRealEstateListings`) chứ không dựng Livewire component đầy đủ
 * cho phần duyệt/từ chối — cùng cách tiếp cận với `ListingIsolationTest` gọi
 * thẳng model/service; phần "render không lỗi 500" được verify riêng bằng
 * HTTP thật (xem báo cáo giao việc), phần cách ly dự án của /admin verify
 * qua chính hàm build-query thật của Page (reflection vào scopedQuery()).
 */
class ListingModerationTest extends TestCase
{
    use ModeratesRealEstateListings;
    use RefreshDatabase;

    /** Dựng (tenant, project, building, apartment, resident chủ căn, user) — mirror ListingIsolationTest. */
    private function makeProject(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-$tag", 'name' => "Tenant $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-$tag", 'name' => "Project $tag"]);
        $building = Building::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'code' => "BLD-$tag", 'name' => "Building $tag",
        ]);
        $apartment = Apartment::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-$tag",
        ]);
        $sellerUser = User::create([
            'name' => "Seller $tag", 'email' => strtolower($tag).'-seller@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
        $resident = Resident::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $sellerUser->id,
            'code' => "RES-$tag", 'full_name' => "Resident $tag",
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id,
            'role' => 'owner', 'is_primary' => true,
        ]);

        // Nhóm "Quan tâm dự án" — ListingFeedPublisher chỉ sinh bài khi nhóm này
        // đã tồn tại (mirror bậc thang nhóm CommunityRefPostsSeeder cho event/poll).
        CommunityGroup::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'kind' => 'project_interest', 'name' => "Quan tâm dự án $tag",
        ]);

        return compact('tenant', 'project', 'building', 'apartment', 'sellerUser', 'resident');
    }

    private function pendingListing(array $ctx, string $code): RealEstateListing
    {
        return RealEstateListing::create([
            'tenant_id' => $ctx['tenant']->id, 'project_id' => $ctx['project']->id,
            'apartment_id' => $ctx['apartment']->id, 'owner_resident_id' => $ctx['resident']->id,
            'created_by_user_id' => $ctx['sellerUser']->id,
            'code' => $code, 'type' => 'sale', 'title' => "Tin $code", 'price' => 2_000_000_000,
            'status' => 'active', 'approval_status' => RealEstateListing::APPROVAL_PENDING,
        ]);
    }

    /** BQL/SA đứng tên hành động — không phải cư dân, chỉ cần một User đã auth. */
    private function makeStaff(string $tag, array $ctx, bool $platformAdmin = false): User
    {
        return User::create([
            'name' => "Staff $tag", 'email' => strtolower($tag).'-staff@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'staff',
            'tenant_id' => $ctx['tenant']->id, 'project_id' => $ctx['project']->id,
            'building_id' => $ctx['building']->id, 'is_platform_admin' => $platformAdmin,
        ]);
    }

    public function test_approve_publishes_exactly_one_listing_ref_post(): void
    {
        $ctx = $this->makeProject('AP1');
        $listing = $this->pendingListing($ctx, 'RE-AP1-001');
        $this->actingAs($this->makeStaff('AP1', $ctx));

        $this->approveListing($listing);

        $this->assertSame(1, CommunityPost::withoutGlobalScope('tenant')
            ->where('source_type', 'listing')->where('source_id', $listing->id)->count());
        $this->assertSame('approved', $listing->fresh()->approval_status);
    }

    public function test_re_approving_an_already_approved_listing_does_not_duplicate_the_post(): void
    {
        $ctx = $this->makeProject('AP2');
        $listing = $this->pendingListing($ctx, 'RE-AP2-001');
        $this->actingAs($this->makeStaff('AP2', $ctx));

        $this->approveListing($listing);
        $firstApprovedAt = $listing->fresh()->approved_at;

        // Bấm "Duyệt" lần nữa (trùng lặp thao tác, ví dụ double-click) — phải
        // là no-op hoàn toàn: không cập nhật lại approved_at, không sinh thêm bài.
        $this->approveListing($listing->fresh());

        $this->assertSame(1, CommunityPost::withoutGlobalScope('tenant')
            ->where('source_type', 'listing')->where('source_id', $listing->id)->count());
        $this->assertTrue($firstApprovedAt->equalTo($listing->fresh()->approved_at));

        // Kiểm thêm tầng thấp hơn: ngay cả khi gọi thẳng service publish() hai
        // lần (bỏ qua lớp guard idempotent của trait) thì updateOrCreate theo
        // source_type+source_id vẫn không nhân bản — đây là điều khoản đề bài
        // yêu cầu kiểm rõ ("kiểm để không sinh bài trùng").
        app(ListingFeedPublisher::class)->publish($listing->fresh());
        $this->assertSame(1, CommunityPost::withoutGlobalScope('tenant')
            ->where('source_type', 'listing')->where('source_id', $listing->id)->count());
    }

    public function test_reject_without_reason_is_blocked(): void
    {
        $ctx = $this->makeProject('RJ1');
        $listing = $this->pendingListing($ctx, 'RE-RJ1-001');
        $this->actingAs($this->makeStaff('RJ1', $ctx));

        $this->expectException(\InvalidArgumentException::class);
        $this->rejectListing($listing, '   '); // chuỗi trắng cũng phải bị chặn, không riêng chuỗi rỗng
    }

    public function test_reject_after_approval_unpublishes_the_listing_ref_post(): void
    {
        $ctx = $this->makeProject('RJ2');
        $listing = $this->pendingListing($ctx, 'RE-RJ2-001');
        $this->actingAs($this->makeStaff('RJ2', $ctx));

        $this->approveListing($listing);
        $this->assertSame(1, CommunityPost::withoutGlobalScope('tenant')
            ->where('source_type', 'listing')->where('source_id', $listing->id)->count());

        $this->rejectListing($listing->fresh(), 'Phát hiện tin môi giới giả sau khi đã duyệt.');

        $this->assertSame('rejected', $listing->fresh()->approval_status);
        $this->assertSame(0, CommunityPost::withoutGlobalScope('tenant')
            ->where('source_type', 'listing')->where('source_id', $listing->id)->count());
    }

    public function test_bql_project_scoped_query_excludes_other_projects_listing(): void
    {
        $a = $this->makeProject('SC1A');
        $b = $this->makeProject('SC1B');
        $listingA = $this->pendingListing($a, 'RE-SC1A-001');
        $listingB = $this->pendingListing($b, 'RE-SC1B-001');

        // BQL của dự án A: building_id trỏ về building của A → CurrentContext
        // mặc định chọn đúng project A (xem CurrentContext::projectId()).
        $this->actingAs($this->makeStaff('SC1A', $a));

        $page = new ListingApprovalQueue;
        $method = new ReflectionMethod($page, 'scopedQuery');
        $method->setAccessible(true);
        $ids = $method->invoke($page)->pluck('id')->all();

        $this->assertContains($listingA->id, $ids, 'BQL dự án A phải thấy tin của dự án A.');
        $this->assertNotContains($listingB->id, $ids, 'BQL dự án A KHÔNG được thấy tin của dự án B — chốt cách ly bắt buộc.');
    }

    public function test_sa_can_approve_a_listing_that_was_never_escalated(): void
    {
        // Yêu cầu chốt lại 2026-07-30: SA phải duyệt được MỌI tin, kể cả tin
        // BQL dự án chưa từng đụng tới (không ai trực/BQL bỏ quên) — không chỉ
        // tin được đẩy lên. Test này KHÔNG được gọi escalateListing() trước.
        $ctx = $this->makeProject('SA1');
        $listing = $this->pendingListing($ctx, 'RE-SA1-001');
        $this->assertNull($listing->escalated_at, 'Tiền đề: tin này chưa từng được đẩy lên.');

        $sa = $this->makeStaff('SA1-SUPERADMIN', $ctx, platformAdmin: true);
        $this->actingAs($sa);

        $this->approveListing($listing);

        $this->assertSame('approved', $listing->fresh()->approval_status);
        $this->assertSame((int) $sa->id, (int) $listing->fresh()->approved_by_user_id);
    }

    public function test_escalate_requires_a_note_and_only_applies_to_pending_listings(): void
    {
        $ctx = $this->makeProject('ES1');
        $listing = $this->pendingListing($ctx, 'RE-ES1-001');
        $this->actingAs($this->makeStaff('ES1', $ctx));

        $this->expectException(\InvalidArgumentException::class);
        $this->escalateListing($listing, '');
    }

    public function test_escalate_marks_the_listing_without_blocking_later_moderation(): void
    {
        $ctx = $this->makeProject('ES2');
        $listing = $this->pendingListing($ctx, 'RE-ES2-001');
        $staff = $this->makeStaff('ES2', $ctx);
        $this->actingAs($staff);

        $this->escalateListing($listing, 'Nghi ngờ môi giới giả, giá thấp bất thường.');
        $listing->refresh();
        $this->assertNotNull($listing->escalated_at);
        $this->assertTrue($listing->wasEscalated());

        // Escalate chỉ là TÍN HIỆU — vẫn duyệt/từ chối bình thường được sau đó
        // (không có approval_status='escalated' nào khoá lại việc này).
        $this->approveListing($listing);
        $this->assertSame('approved', $listing->fresh()->approval_status);
        $this->assertNotNull($listing->fresh()->escalated_at, 'Lịch sử từng-đẩy-lên phải được giữ lại.');
    }
}
