<?php

namespace App\Filament\Sa\Resources\DocVersions\Pages;

use App\Filament\Sa\Resources\DocVersions\DocVersionResource;
use App\Models\DocVersion;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDocVersion extends EditRecord
{
    protected static string $resource = DocVersionResource::class;

    /** Đảm bảo chỉ 1 version hiện hành. */
    protected function afterSave(): void
    {
        if ($this->record->is_current) {
            DocVersion::where('id', '!=', $this->record->id)->update(['is_current' => false]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
