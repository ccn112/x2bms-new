<?php

namespace App\Filament\Resources\IntegrationRetryJobs;

use App\Filament\Resources\IntegrationRetryJobs\Pages\CreateIntegrationRetryJob;
use App\Filament\Resources\IntegrationRetryJobs\Pages\EditIntegrationRetryJob;
use App\Filament\Resources\IntegrationRetryJobs\Pages\ListIntegrationRetryJobs;
use App\Filament\Resources\IntegrationRetryJobs\Schemas\IntegrationRetryJobForm;
use App\Filament\Resources\IntegrationRetryJobs\Tables\IntegrationRetryJobsTable;
use App\Models\IntegrationRetryJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntegrationRetryJobResource extends Resource
{
    protected static ?string $model = IntegrationRetryJob::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IntegrationRetryJobForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntegrationRetryJobsTable::configure($table);
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
            'index' => ListIntegrationRetryJobs::route('/'),
            'create' => CreateIntegrationRetryJob::route('/create'),
            'edit' => EditIntegrationRetryJob::route('/{record}/edit'),
        ];
    }
}
