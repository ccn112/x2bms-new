<?php

namespace App\Filament\Resources\Statements\Pages;

use App\Filament\Resources\Statements\StatementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStatement extends EditRecord
{
    protected static string $resource = StatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
