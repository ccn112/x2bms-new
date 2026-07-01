<?php

namespace App\Filament\Resources\TenantEntitlements;

use App\Filament\Resources\TenantEntitlements\Pages\CreateTenantEntitlement;
use App\Filament\Resources\TenantEntitlements\Pages\EditTenantEntitlement;
use App\Filament\Resources\TenantEntitlements\Pages\ListTenantEntitlements;
use App\Filament\Resources\TenantEntitlements\Schemas\TenantEntitlementForm;
use App\Filament\Resources\TenantEntitlements\Tables\TenantEntitlementsTable;
use App\Models\TenantEntitlement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TenantEntitlementResource extends Resource
{
    protected static ?string $model = TenantEntitlement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TenantEntitlementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantEntitlementsTable::configure($table);
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
            'index' => ListTenantEntitlements::route('/'),
            'create' => CreateTenantEntitlement::route('/create'),
            'edit' => EditTenantEntitlement::route('/{record}/edit'),
        ];
    }
}
