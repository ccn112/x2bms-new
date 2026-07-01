<?php

namespace App\Filament\Resources\IntegrationApiKeyScopes;

use App\Filament\Resources\IntegrationApiKeyScopes\Pages\CreateIntegrationApiKeyScope;
use App\Filament\Resources\IntegrationApiKeyScopes\Pages\EditIntegrationApiKeyScope;
use App\Filament\Resources\IntegrationApiKeyScopes\Pages\ListIntegrationApiKeyScopes;
use App\Filament\Resources\IntegrationApiKeyScopes\Schemas\IntegrationApiKeyScopeForm;
use App\Filament\Resources\IntegrationApiKeyScopes\Tables\IntegrationApiKeyScopesTable;
use App\Models\IntegrationApiKeyScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntegrationApiKeyScopeResource extends Resource
{
    protected static ?string $model = IntegrationApiKeyScope::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IntegrationApiKeyScopeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntegrationApiKeyScopesTable::configure($table);
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
            'index' => ListIntegrationApiKeyScopes::route('/'),
            'create' => CreateIntegrationApiKeyScope::route('/create'),
            'edit' => EditIntegrationApiKeyScope::route('/{record}/edit'),
        ];
    }
}
