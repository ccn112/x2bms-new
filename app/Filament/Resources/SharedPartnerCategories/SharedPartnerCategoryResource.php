<?php

namespace App\Filament\Resources\SharedPartnerCategories;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\SharedPartnerCategories\Pages\CreateSharedPartnerCategory;
use App\Filament\Resources\SharedPartnerCategories\Pages\EditSharedPartnerCategory;
use App\Filament\Resources\SharedPartnerCategories\Pages\ListSharedPartnerCategories;
use App\Filament\Resources\SharedPartnerCategories\Schemas\SharedPartnerCategoryForm;
use App\Filament\Resources\SharedPartnerCategories\Tables\SharedPartnerCategoriesTable;
use App\Models\SharedPartnerCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SharedPartnerCategoryResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = SharedPartnerCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SharedPartnerCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SharedPartnerCategoriesTable::configure($table);
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
            'index' => ListSharedPartnerCategories::route('/'),
            'create' => CreateSharedPartnerCategory::route('/create'),
            'edit' => EditSharedPartnerCategory::route('/{record}/edit'),
        ];
    }
}
