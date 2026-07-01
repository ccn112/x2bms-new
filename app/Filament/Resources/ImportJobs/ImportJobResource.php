<?php

namespace App\Filament\Resources\ImportJobs;

use App\Filament\Resources\ImportJobs\Pages\CreateImportJob;
use App\Filament\Resources\ImportJobs\Pages\EditImportJob;
use App\Filament\Resources\ImportJobs\Pages\ListImportJobs;
use App\Filament\Resources\ImportJobs\Schemas\ImportJobForm;
use App\Filament\Resources\ImportJobs\Tables\ImportJobsTable;
use App\Models\ImportJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ImportJobResource extends Resource
{
    protected static ?string $model = ImportJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ImportJobForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImportJobsTable::configure($table);
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
            'index' => ListImportJobs::route('/'),
            'create' => CreateImportJob::route('/create'),
            'edit' => EditImportJob::route('/{record}/edit'),
        ];
    }
}
