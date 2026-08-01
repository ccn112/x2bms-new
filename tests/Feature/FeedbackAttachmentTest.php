<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Attachment;
use App\Models\Building;
use App\Models\FeedbackAttachment;
use App\Models\FeedbackRequest;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Ý kiến kiến nghị — đính kèm ẢNH/VIDEO/PDF (UX review 2026-08-01 ý 1, phần C).
 * Upload là opt-in `kind=media` (không nới quyền cho caller ảnh cũ); feedback lưu
 * vào bảng riêng feedback_attachments, chỉ nhận file do CHÍNH user upload.
 */
class FeedbackAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private function makeResident(string $tag): array
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
        $user = User::create([
            'name' => "User $tag", 'email' => strtolower($tag).'-fba@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident', 'tenant_id' => $tenant->id,
        ]);
        $resident = Resident::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id,
            'code' => "RES-$tag", 'full_name' => "Resident $tag",
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id,
            'role' => 'owner', 'is_primary' => true,
        ]);

        return compact('user', 'tenant', 'building', 'apartment', 'resident');
    }

    private function pdfAttachment(array $r): Attachment
    {
        return Attachment::create([
            'tenant_id' => $r['tenant']->id, 'disk' => 'public',
            'path' => 'resident-uploads/'.$r['user']->id.'/tai-lieu.pdf',
            'file_name' => 'tai-lieu.pdf', 'mime_type' => 'application/pdf',
            'size' => 12345, 'uploaded_by' => $r['user']->id,
        ]);
    }

    public function test_upload_tu_choi_pdf_khi_khong_kind_media(): void
    {
        Storage::fake('public');
        $r = $this->makeResident('A1');
        Sanctum::actingAs($r['user'], ['resident']);

        $this->post('/api/v1/resident/uploads', [
            'file' => UploadedFile::fake()->createWithContent('doc.pdf', '%PDF-1.4 fake'),
        ])->assertStatus(422);
    }

    public function test_upload_nhan_pdf_khi_kind_media(): void
    {
        Storage::fake('public');
        $r = $this->makeResident('A2');
        Sanctum::actingAs($r['user'], ['resident']);

        $this->post('/api/v1/resident/uploads', [
            'file' => UploadedFile::fake()->createWithContent('doc.pdf', '%PDF-1.4 fake'),
            'kind' => 'media',
        ])->assertCreated()->assertJsonPath('data.is_image', false);
    }

    public function test_feedback_dinh_kem_qua_attachment_ids(): void
    {
        $r = $this->makeResident('A3');
        $att = $this->pdfAttachment($r);
        Sanctum::actingAs($r['user'], ['resident']);

        $res = $this->postJson('/api/v1/resident/feedback', [
            'title' => 'Nứt tường', 'description' => 'Kèm ảnh + tài liệu.',
            'attachment_ids' => [$att->id],
        ])->assertCreated();

        $res->assertJsonPath('data.attachments.0.is_image', false)
            ->assertJsonPath('data.attachments.0.name', 'tai-lieu.pdf');
        $this->assertNotNull($res->json('data.attachments.0.url'));
        $this->assertSame(1, FeedbackAttachment::count());
    }

    public function test_binh_luan_dinh_kem(): void
    {
        $r = $this->makeResident('A4');
        $fb = FeedbackRequest::create([
            'tenant_id' => $r['tenant']->id, 'building_id' => $r['building']->id,
            'apartment_id' => $r['apartment']->id, 'resident_id' => $r['resident']->id,
            'user_id' => $r['user']->id, 'code' => 'PA-A4', 'title' => 't', 'description' => 'd',
            'priority' => 'normal', 'channel' => 'app', 'status' => 'new',
        ]);
        $att = $this->pdfAttachment($r);
        Sanctum::actingAs($r['user'], ['resident']);

        $this->postJson("/api/v1/resident/feedback/{$fb->id}/comments", [
            'body' => 'Gửi kèm tài liệu', 'attachment_ids' => [$att->id],
        ])->assertCreated()->assertJsonPath('data.attachments.0.name', 'tai-lieu.pdf');
    }

    public function test_khong_muon_duoc_attachment_cua_nguoi_khac(): void
    {
        $me = $this->makeResident('A5');
        $other = $this->makeResident('A6');
        $foreignAtt = $this->pdfAttachment($other);
        Sanctum::actingAs($me['user'], ['resident']);

        $res = $this->postJson('/api/v1/resident/feedback', [
            'title' => 'x', 'description' => 'y', 'attachment_ids' => [$foreignAtt->id],
        ])->assertCreated();

        $this->assertCount(0, $res->json('data.attachments'));
        $this->assertSame(0, FeedbackAttachment::count());
    }
}
