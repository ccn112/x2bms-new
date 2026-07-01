<?php

namespace App\Filament\Resources\TenantSupportProfiles;

use App\Filament\Resources\TenantSupportProfiles\Pages\CreateTenantSupportProfile;
use App\Filament\Resources\TenantSupportProfiles\Pages\EditTenantSupportProfile;
use App\Filament\Resources\TenantSupportProfiles\Pages\ListTenantSupportProfiles;
use App\Filament\Resources\TenantSupportProfiles\Schemas\TenantSupportProfileForm;
use App\Filament\Resources\TenantSupportProfiles\Tables\TenantSupportProfilesTable;
use App\Models\TenantSupportProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TenantSupportProfileResource extends Resource
{
    protected static ?string $model = TenantSupportProfile::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TenantSupportProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantSupportProfilesTable::configure($table);
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
            'index' => ListTenantSupportProfiles::route('/'),
            'create' => CreateTenantSupportProfile::route('/create'),
            'edit' => EditTenantSupportProfile::route('/{record}/edit'),
        ];
    }
}
