<?php

namespace App\Filament\Resources\TenantModules;

use App\Filament\Resources\TenantModules\Pages\CreateTenantModule;
use App\Filament\Resources\TenantModules\Pages\EditTenantModule;
use App\Filament\Resources\TenantModules\Pages\ListTenantModules;
use App\Filament\Resources\TenantModules\Schemas\TenantModuleForm;
use App\Filament\Resources\TenantModules\Tables\TenantModulesTable;
use App\Models\TenantModule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TenantModuleResource extends Resource
{
    protected static ?string $model = TenantModule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TenantModuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantModulesTable::configure($table);
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
            'index' => ListTenantModules::route('/'),
            'create' => CreateTenantModule::route('/create'),
            'edit' => EditTenantModule::route('/{record}/edit'),
        ];
    }
}
