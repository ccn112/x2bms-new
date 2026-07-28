<?php

namespace App\Filament\Sa\Resources\Developers\Pages;

use App\Filament\Sa\Resources\Developers\DeveloperResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDeveloper extends CreateRecord
{
    protected static string $resource = DeveloperResource::class;
}
