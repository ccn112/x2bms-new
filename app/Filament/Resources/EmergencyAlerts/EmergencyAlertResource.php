<?php

namespace App\Filament\Resources\EmergencyAlerts;

use App\Filament\Resources\EmergencyAlerts\Pages\CreateEmergencyAlert;
use App\Filament\Resources\EmergencyAlerts\Pages\EditEmergencyAlert;
use App\Filament\Resources\EmergencyAlerts\Pages\ListEmergencyAlerts;
use App\Filament\Resources\EmergencyAlerts\Schemas\EmergencyAlertForm;
use App\Filament\Resources\EmergencyAlerts\Tables\EmergencyAlertsTable;
use App\Models\EmergencyAlert;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EmergencyAlertResource extends Resource
{
    protected static ?string $model = EmergencyAlert::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return EmergencyAlertForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmergencyAlertsTable::configure($table);
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
            'index' => ListEmergencyAlerts::route('/'),
            'create' => CreateEmergencyAlert::route('/create'),
            'edit' => EditEmergencyAlert::route('/{record}/edit'),
        ];
    }
}
