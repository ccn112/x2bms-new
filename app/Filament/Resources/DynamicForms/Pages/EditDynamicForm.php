<?php

namespace App\Filament\Resources\DynamicForms\Pages;

use App\Filament\Resources\DynamicForms\DynamicFormResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDynamicForm extends EditRecord
{
    protected static string $resource = DynamicFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
