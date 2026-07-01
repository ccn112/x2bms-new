<?php

namespace App\Filament\Resources\IntegrationConnectionChecks;

use App\Filament\Resources\IntegrationConnectionChecks\Pages\CreateIntegrationConnectionCheck;
use App\Filament\Resources\IntegrationConnectionChecks\Pages\EditIntegrationConnectionCheck;
use App\Filament\Resources\IntegrationConnectionChecks\Pages\ListIntegrationConnectionChecks;
use App\Filament\Resources\IntegrationConnectionChecks\Schemas\IntegrationConnectionCheckForm;
use App\Filament\Resources\IntegrationConnectionChecks\Tables\IntegrationConnectionChecksTable;
use App\Models\IntegrationConnectionCheck;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntegrationConnectionCheckResource extends Resource
{
    protected static ?string $model = IntegrationConnectionCheck::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IntegrationConnectionCheckForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntegrationConnectionChecksTable::configure($table);
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
            'index' => ListIntegrationConnectionChecks::route('/'),
            'create' => CreateIntegrationConnectionCheck::route('/create'),
            'edit' => EditIntegrationConnectionCheck::route('/{record}/edit'),
        ];
    }
}
