<?php

namespace App\Filament\Resources\IntegrationSecurityPolicies;

use App\Filament\Resources\IntegrationSecurityPolicies\Pages\CreateIntegrationSecurityPolicy;
use App\Filament\Resources\IntegrationSecurityPolicies\Pages\EditIntegrationSecurityPolicy;
use App\Filament\Resources\IntegrationSecurityPolicies\Pages\ListIntegrationSecurityPolicies;
use App\Filament\Resources\IntegrationSecurityPolicies\Schemas\IntegrationSecurityPolicyForm;
use App\Filament\Resources\IntegrationSecurityPolicies\Tables\IntegrationSecurityPoliciesTable;
use App\Models\IntegrationSecurityPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntegrationSecurityPolicyResource extends Resource
{
    protected static ?string $model = IntegrationSecurityPolicy::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IntegrationSecurityPolicyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntegrationSecurityPoliciesTable::configure($table);
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
            'index' => ListIntegrationSecurityPolicies::route('/'),
            'create' => CreateIntegrationSecurityPolicy::route('/create'),
            'edit' => EditIntegrationSecurityPolicy::route('/{record}/edit'),
        ];
    }
}
