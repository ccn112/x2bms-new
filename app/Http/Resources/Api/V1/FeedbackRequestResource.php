<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\FeedbackRequest $resource
 * Phản ánh / yêu cầu dịch vụ của cư dân. `status` cast enum FeedbackStatus.
 * `timeline` chỉ kèm ở chi tiết (controller set transient `timeline`).
 */
class FeedbackRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof \BackedEnum ? $this->status->value : $this->status;

        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->whenLoaded('category', fn () => $this->category === null ? null : [
                'id' => (string) $this->category->id,
                'name' => $this->category->name,
                'color' => $this->category->color,
            ]),
            'status' => $status,
            'priority' => $this->priority,
            'sla_due_at' => optional($this->sla_due_at)->toIso8601String(),
            'resolved_at' => optional($this->resolved_at)->toIso8601String(),
            'rating' => $this->rating === null ? null : (int) $this->rating,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'timeline' => $this->when(
                isset($this->timeline),
                fn () => $this->timeline
            ),
        ];
    }
}
