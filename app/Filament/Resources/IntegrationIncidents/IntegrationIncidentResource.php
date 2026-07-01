<?php

namespace App\Filament\Resources\IntegrationIncidents;

use App\Filament\Resources\IntegrationIncidents\Pages\CreateIntegrationIncident;
use App\Filament\Resources\IntegrationIncidents\Pages\EditIntegrationIncident;
use App\Filament\Resources\IntegrationIncidents\Pages\ListIntegrationIncidents;
use App\Filament\Resources\IntegrationIncidents\Schemas\IntegrationIncidentForm;
use App\Filament\Resources\IntegrationIncidents\Tables\IntegrationIncidentsTable;
use App\Models\IntegrationIncident;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntegrationIncidentResource extends Resource
{
    protected static ?string $model = IntegrationIncident::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IntegrationIncidentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntegrationIncidentsTable::configure($table);
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
            'index' => ListIntegrationIncidents::route('/'),
            'create' => CreateIntegrationIncident::route('/create'),
            'edit' => EditIntegrationIncident::route('/{record}/edit'),
        ];
    }
}
