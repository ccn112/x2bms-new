<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Poll $resource
 * Khảo sát/bình chọn (tab Cộng đồng). `percent` = option.vote_count / poll.vote_count.
 * `voted`/`voted_option_id` set từ controller qua $additional (theo resident của user).
 */
class PollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $total = max(1, (int) $this->vote_count);

        $options = $this->whenLoaded('options', function () use ($total) {
            return $this->options
                ->sortBy('sort')
                ->map(fn ($o) => [
                    'id' => (string) $o->id,
                    'label' => $o->label,
                    'votes' => (int) $o->vote_count,
                    'percent' => (int) round(((int) $o->vote_count) * 100 / $total),
                ])
                ->values()
                ->all();
        }, []);

        return [
            'id' => (string) $this->id,
            'question' => $this->question,
            'type' => $this->type,
            'status' => $this->status,
            'closes_at' => optional($this->closes_at)->toIso8601String(),
            'total_participants' => (int) $this->vote_count,
            'voted' => (bool) ($this->voted ?? false),
            'voted_option_id' => isset($this->voted_option_id) ? (string) $this->voted_option_id : null,
            'options' => $options,
        ];
    }
}
