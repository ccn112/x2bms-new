<?php

namespace App\Filament\Resources\SharedPartners;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\SharedPartners\Pages\CreateSharedPartner;
use App\Filament\Resources\SharedPartners\Pages\EditSharedPartner;
use App\Filament\Resources\SharedPartners\Pages\ListSharedPartners;
use App\Filament\Resources\SharedPartners\Schemas\SharedPartnerForm;
use App\Filament\Resources\SharedPartners\Tables\SharedPartnersTable;
use App\Models\SharedPartner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SharedPartnerResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = SharedPartner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SharedPartnerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SharedPartnersTable::configure($table);
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
            'index' => ListSharedPartners::route('/'),
            'create' => CreateSharedPartner::route('/create'),
            'edit' => EditSharedPartner::route('/{record}/edit'),
        ];
    }
}
