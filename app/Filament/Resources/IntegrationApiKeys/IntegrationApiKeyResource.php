<?php

namespace App\Filament\Resources\IntegrationApiKeys;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\IntegrationApiKeys\Pages\CreateIntegrationApiKey;
use App\Filament\Resources\IntegrationApiKeys\Pages\EditIntegrationApiKey;
use App\Filament\Resources\IntegrationApiKeys\Pages\ListIntegrationApiKeys;
use App\Filament\Resources\IntegrationApiKeys\Schemas\IntegrationApiKeyForm;
use App\Filament\Resources\IntegrationApiKeys\Tables\IntegrationApiKeysTable;
use App\Models\IntegrationApiKey;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IntegrationApiKeyResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = IntegrationApiKey::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IntegrationApiKeyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntegrationApiKeysTable::configure($table);
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
            'index' => ListIntegrationApiKeys::route('/'),
            'create' => CreateIntegrationApiKey::route('/create'),
            'edit' => EditIntegrationApiKey::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
