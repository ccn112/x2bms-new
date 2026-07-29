<?php

namespace App\Http\Resources\Api\V1;

use App\Support\DemoImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\CommunityGroup $resource
 * Nhóm cộng đồng (tab Cộng đồng). `category`/`icon_key` chưa có cột → null;
 * `image_url` = ảnh demo theo chủ đề (DemoImage). `joined` = cư dân đã tham gia.
 */
class CommunityGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => null,
            'members' => (int) $this->member_count,
            'joined' => (bool) ($this->joined ?? false),
            'icon_key' => null,
            'image_url' => DemoImage::url('community,people,group', $this->id),

            // Bậc thang nhóm (chốt 29/07). App xếp nhóm theo đúng thứ tự này và
            // quyết định hiện nút "Đăng bài" hay không.
            'kind' => $this->kind,          // platform|project_interest|project_resident|private
            'verification_level' => $this->verification_level,
            // Nhãn badge trả TỪ SERVER — app chỉ vẽ, không tự đặt chữ. Badge phải
            // có nhãn ngữ nghĩa cho trình đọc màn hình (docs 01 §3).
            'verification_label' => \App\Enums\CommunityVerificationLevel::tryFrom(
                (string) $this->verification_level)?->label(),
            'verification_badge' => \App\Enums\CommunityVerificationLevel::tryFrom(
                (string) $this->verification_level)?->badgeKey(),
            'join_policy' => $this->join_policy,
            'project_name' => $this->project?->name,

            // Cư dân có được đăng bài ở nhóm này không. Tính ở SERVER chứ không
            // để app suy từ `kind`: quyền là chuyện của server, app chỉ vẽ.
            'can_post' => $this->post_policy === 'members',

            // Nhóm mặc định của dự án/hệ thống — không cho rời, app ẩn nút rời.
            'is_default' => (bool) $this->is_default,
        ];
    }
}
