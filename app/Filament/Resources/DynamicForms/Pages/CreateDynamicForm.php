<?php

namespace App\Filament\Resources\DynamicForms\Pages;

use App\Filament\Resources\DynamicForms\DynamicFormResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDynamicForm extends CreateRecord
{
    protected static string $resource = DynamicFormResource::class;
}
