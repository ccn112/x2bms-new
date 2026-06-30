<?php

namespace App\Filament\Resources\StaffProfiles\Pages;

use App\Filament\Resources\StaffProfiles\StaffProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStaffProfile extends EditRecord
{
    protected static string $resource = StaffProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
