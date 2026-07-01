<?php

namespace App\Filament\Resources\ServiceEvaluations\Pages;

use App\Filament\Resources\ServiceEvaluations\ServiceEvaluationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceEvaluation extends EditRecord
{
    protected static string $resource = ServiceEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
