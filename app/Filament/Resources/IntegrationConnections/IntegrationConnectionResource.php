<?php

namespace App\Filament\Resources\IntegrationConnections;

use App\Filament\Resources\IntegrationConnections\Pages\CreateIntegrationConnection;
use App\Filament\Resources\IntegrationConnections\Pages\EditIntegrationConnection;
use App\Filament\Resources\IntegrationConnections\Pages\ListIntegrationConnections;
use App\Filament\Resources\IntegrationConnections\Schemas\IntegrationConnectionForm;
use App\Filament\Resources\IntegrationConnections\Tables\IntegrationConnectionsTable;
use App\Models\IntegrationConnection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntegrationConnectionResource extends Resource
{
    protected static ?string $model = IntegrationConnection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IntegrationConnectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntegrationConnectionsTable::configure($table);
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
            'index' => ListIntegrationConnections::route('/'),
            'create' => CreateIntegrationConnection::route('/create'),
            'edit' => EditIntegrationConnection::route('/{record}/edit'),
        ];
    }
}
