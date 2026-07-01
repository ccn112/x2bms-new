<?php

namespace App\Filament\Resources\IntegrationCredentials;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\IntegrationCredentials\Pages\CreateIntegrationCredential;
use App\Filament\Resources\IntegrationCredentials\Pages\EditIntegrationCredential;
use App\Filament\Resources\IntegrationCredentials\Pages\ListIntegrationCredentials;
use App\Filament\Resources\IntegrationCredentials\Schemas\IntegrationCredentialForm;
use App\Filament\Resources\IntegrationCredentials\Tables\IntegrationCredentialsTable;
use App\Models\IntegrationCredential;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IntegrationCredentialResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = IntegrationCredential::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IntegrationCredentialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntegrationCredentialsTable::configure($table);
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
            'index' => ListIntegrationCredentials::route('/'),
            'create' => CreateIntegrationCredential::route('/create'),
            'edit' => EditIntegrationCredential::route('/{record}/edit'),
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
