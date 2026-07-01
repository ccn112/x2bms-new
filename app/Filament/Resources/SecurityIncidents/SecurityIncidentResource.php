<?php

namespace App\Filament\Resources\SecurityIncidents;

use App\Filament\Resources\SecurityIncidents\Pages\CreateSecurityIncident;
use App\Filament\Resources\SecurityIncidents\Pages\EditSecurityIncident;
use App\Filament\Resources\SecurityIncidents\Pages\ListSecurityIncidents;
use App\Filament\Resources\SecurityIncidents\Schemas\SecurityIncidentForm;
use App\Filament\Resources\SecurityIncidents\Tables\SecurityIncidentsTable;
use App\Models\SecurityIncident;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SecurityIncidentResource extends Resource
{
    protected static ?string $model = SecurityIncident::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SecurityIncidentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SecurityIncidentsTable::configure($table);
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
            'index' => ListSecurityIncidents::route('/'),
            'create' => CreateSecurityIncident::route('/create'),
            'edit' => EditSecurityIncident::route('/{record}/edit'),
        ];
    }
}
