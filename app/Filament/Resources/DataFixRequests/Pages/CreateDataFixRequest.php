<?php

namespace App\Filament\Resources\DataFixRequests\Pages;

use App\Filament\Resources\DataFixRequests\DataFixRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDataFixRequest extends CreateRecord
{
    protected static string $resource = DataFixRequestResource::class;
}
