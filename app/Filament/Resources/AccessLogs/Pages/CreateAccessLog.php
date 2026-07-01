<?php

namespace App\Filament\Resources\AccessLogs\Pages;

use App\Filament\Resources\AccessLogs\AccessLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccessLog extends CreateRecord
{
    protected static string $resource = AccessLogResource::class;
}
