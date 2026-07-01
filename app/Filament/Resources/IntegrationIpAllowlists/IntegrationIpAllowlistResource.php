<?php

namespace App\Filament\Resources\IntegrationIpAllowlists;

use App\Filament\Resources\IntegrationIpAllowlists\Pages\CreateIntegrationIpAllowlist;
use App\Filament\Resources\IntegrationIpAllowlists\Pages\EditIntegrationIpAllowlist;
use App\Filament\Resources\IntegrationIpAllowlists\Pages\ListIntegrationIpAllowlists;
use App\Filament\Resources\IntegrationIpAllowlists\Schemas\IntegrationIpAllowlistForm;
use App\Filament\Resources\IntegrationIpAllowlists\Tables\IntegrationIpAllowlistsTable;
use App\Models\IntegrationIpAllowlist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntegrationIpAllowlistResource extends Resource
{
    protected static ?string $model = IntegrationIpAllowlist::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IntegrationIpAllowlistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntegrationIpAllowlistsTable::configure($table);
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
            'index' => ListIntegrationIpAllowlists::route('/'),
            'create' => CreateIntegrationIpAllowlist::route('/create'),
            'edit' => EditIntegrationIpAllowlist::route('/{record}/edit'),
        ];
    }
}
