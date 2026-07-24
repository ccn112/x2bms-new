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
        ];
    }
}
