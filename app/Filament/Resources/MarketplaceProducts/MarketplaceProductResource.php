<?php

namespace App\Filament\Resources\MarketplaceProducts;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\MarketplaceProducts\Pages\CreateMarketplaceProduct;
use App\Filament\Resources\MarketplaceProducts\Pages\EditMarketplaceProduct;
use App\Filament\Resources\MarketplaceProducts\Pages\ListMarketplaceProducts;
use App\Filament\Resources\MarketplaceProducts\Schemas\MarketplaceProductForm;
use App\Filament\Resources\MarketplaceProducts\Tables\MarketplaceProductsTable;
use App\Models\MarketplaceProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MarketplaceProductResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = MarketplaceProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MarketplaceProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketplaceProductsTable::configure($table);
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
            'index' => ListMarketplaceProducts::route('/'),
            'create' => CreateMarketplaceProduct::route('/create'),
            'edit' => EditMarketplaceProduct::route('/{record}/edit'),
        ];
    }
}
