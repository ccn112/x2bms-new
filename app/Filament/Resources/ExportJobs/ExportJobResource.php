<?php

namespace App\Filament\Resources\ExportJobs;

use App\Filament\Resources\ExportJobs\Pages\CreateExportJob;
use App\Filament\Resources\ExportJobs\Pages\EditExportJob;
use App\Filament\Resources\ExportJobs\Pages\ListExportJobs;
use App\Filament\Resources\ExportJobs\Schemas\ExportJobForm;
use App\Filament\Resources\ExportJobs\Tables\ExportJobsTable;
use App\Models\ExportJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ExportJobResource extends Resource
{
    protected static ?string $model = ExportJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ExportJobForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExportJobsTable::configure($table);
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
            'index' => ListExportJobs::route('/'),
            'create' => CreateExportJob::route('/create'),
            'edit' => EditExportJob::route('/{record}/edit'),
        ];
    }
}
