<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Apartment $resource
 *
 * Căn hộ đang chọn + thành viên hộ (household). `label` ghép sẵn
 * project · building · code để app không phải tự nối id; `short_label`
 * (building · code) dùng cho pill/context. Cần eager-load
 * `building.project` + `apartmentRelations.resident`.
 */
class ApartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $building = $this->building;
        $project = $building?->project;
        $userId = $request->user()?->id;

        /** @var \Illuminate\Support\Collection $relations */
        $relations = $this->apartmentRelations ?? collect();

        // Quan hệ của chính người dùng trong căn này (role/is_primary hiển thị).
        $mine = $relations->first(
            fn ($rel) => $rel->resident?->user_id !== null && $rel->resident->user_id === $userId
        );

        $label = implode(' · ', array_filter([
            $project?->name,
            $building?->name,
            $this->code,
        ]));
        $shortLabel = implode(' · ', array_filter([
            $building?->name,
            $this->code,
        ]));

        return [
            'id' => $this->id,
            'code' => $this->code,
            'label' => $label,
            'short_label' => $shortLabel,
            'area_sqm' => $this->area_sqm === null ? null : (string) $this->area_sqm,
            'role' => $mine?->role,
            'is_primary' => (bool) ($mine?->is_primary ?? false),
            'building' => $building === null ? null : [
                'id' => $building->id,
                'code' => $building->code,
                'name' => $building->name,
            ],
            'project' => $project === null ? null : [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'members' => $relations->map(fn ($rel) => [
                'resident_id' => $rel->resident_id,
                'full_name' => $rel->resident?->full_name,
                'role' => $rel->role,
                'is_primary' => (bool) $rel->is_primary,
                'phone' => $rel->resident?->phone,
                'avatar_url' => $rel->resident?->avatar_url,
                'is_me' => $rel->resident?->user_id !== null && $rel->resident->user_id === $userId,
            ])->values()->all(),
        ];
    }
}
