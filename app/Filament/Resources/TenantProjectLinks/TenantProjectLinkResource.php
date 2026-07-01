<?php

namespace App\Filament\Resources\TenantProjectLinks;

use App\Filament\Resources\TenantProjectLinks\Pages\CreateTenantProjectLink;
use App\Filament\Resources\TenantProjectLinks\Pages\EditTenantProjectLink;
use App\Filament\Resources\TenantProjectLinks\Pages\ListTenantProjectLinks;
use App\Filament\Resources\TenantProjectLinks\Schemas\TenantProjectLinkForm;
use App\Filament\Resources\TenantProjectLinks\Tables\TenantProjectLinksTable;
use App\Models\TenantProjectLink;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TenantProjectLinkResource extends Resource
{
    protected static ?string $model = TenantProjectLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TenantProjectLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantProjectLinksTable::configure($table);
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
            'index' => ListTenantProjectLinks::route('/'),
            'create' => CreateTenantProjectLink::route('/create'),
            'edit' => EditTenantProjectLink::route('/{record}/edit'),
        ];
    }
}
