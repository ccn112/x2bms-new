<?php

namespace App\Filament\Resources\SosAlerts;

use App\Filament\Resources\SosAlerts\Pages\CreateSosAlert;
use App\Filament\Resources\SosAlerts\Pages\EditSosAlert;
use App\Filament\Resources\SosAlerts\Pages\ListSosAlerts;
use App\Filament\Resources\SosAlerts\Schemas\SosAlertForm;
use App\Filament\Resources\SosAlerts\Tables\SosAlertsTable;
use App\Models\SosAlert;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SosAlertResource extends Resource
{
    protected static ?string $model = SosAlert::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SosAlertForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SosAlertsTable::configure($table);
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
            'index' => ListSosAlerts::route('/'),
            'create' => CreateSosAlert::route('/create'),
            'edit' => EditSosAlert::route('/{record}/edit'),
        ];
    }
}
