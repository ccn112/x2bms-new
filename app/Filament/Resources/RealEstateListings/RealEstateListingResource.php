<?php

namespace App\Filament\Resources\RealEstateListings;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\RealEstateListings\Pages\CreateRealEstateListing;
use App\Filament\Resources\RealEstateListings\Pages\EditRealEstateListing;
use App\Filament\Resources\RealEstateListings\Pages\ListRealEstateListings;
use App\Filament\Resources\RealEstateListings\Schemas\RealEstateListingForm;
use App\Filament\Resources\RealEstateListings\Tables\RealEstateListingsTable;
use App\Models\RealEstateListing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RealEstateListingResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = RealEstateListing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return RealEstateListingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RealEstateListingsTable::configure($table);
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
            'index' => ListRealEstateListings::route('/'),
            'create' => CreateRealEstateListing::route('/create'),
            'edit' => EditRealEstateListing::route('/{record}/edit'),
        ];
    }
}
