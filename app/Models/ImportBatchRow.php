<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatchRow extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'row_number' => 'integer',
        'raw_payload' => 'array',
        'normalized_payload' => 'array',
        'validation_errors' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}
