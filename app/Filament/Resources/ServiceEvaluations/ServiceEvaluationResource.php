<?php

namespace App\Filament\Resources\ServiceEvaluations;

use App\Filament\Resources\ServiceEvaluations\Pages\CreateServiceEvaluation;
use App\Filament\Resources\ServiceEvaluations\Pages\EditServiceEvaluation;
use App\Filament\Resources\ServiceEvaluations\Pages\ListServiceEvaluations;
use App\Filament\Resources\ServiceEvaluations\Schemas\ServiceEvaluationForm;
use App\Filament\Resources\ServiceEvaluations\Tables\ServiceEvaluationsTable;
use App\Models\ServiceEvaluation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ServiceEvaluationResource extends Resource
{
    protected static ?string $model = ServiceEvaluation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ServiceEvaluationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceEvaluationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceEvaluations::route('/'),
            'create' => CreateServiceEvaluation::route('/create'),
            'edit' => EditServiceEvaluation::route('/{record}/edit'),
        ];
    }
}
