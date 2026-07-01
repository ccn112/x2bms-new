<?php

namespace App\Filament\Resources\SupportEntitlements;

use App\Filament\Resources\SupportEntitlements\Pages\CreateSupportEntitlement;
use App\Filament\Resources\SupportEntitlements\Pages\EditSupportEntitlement;
use App\Filament\Resources\SupportEntitlements\Pages\ListSupportEntitlements;
use App\Filament\Resources\SupportEntitlements\Schemas\SupportEntitlementForm;
use App\Filament\Resources\SupportEntitlements\Tables\SupportEntitlementsTable;
use App\Models\SupportEntitlement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupportEntitlementResource extends Resource
{
    protected static ?string $model = SupportEntitlement::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SupportEntitlementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportEntitlementsTable::configure($table);
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
            'index' => ListSupportEntitlements::route('/'),
            'create' => CreateSupportEntitlement::route('/create'),
            'edit' => EditSupportEntitlement::route('/{record}/edit'),
        ];
    }
}
