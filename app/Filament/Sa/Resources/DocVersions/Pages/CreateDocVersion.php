<?php

namespace App\Filament\Sa\Resources\DocVersions\Pages;

use App\Filament\Sa\Resources\DocVersions\DocVersionResource;
use App\Models\DocVersion;
use Filament\Resources\Pages\CreateRecord;

class CreateDocVersion extends CreateRecord
{
    protected static string $resource = DocVersionResource::class;

    /** Đặt version này hiện hành → bỏ current ở tất cả version khác. */
    protected function afterCreate(): void
    {
        if ($this->record->is_current) {
            DocVersion::where('id', '!=', $this->record->id)->update(['is_current' => false]);
        }
    }
}
