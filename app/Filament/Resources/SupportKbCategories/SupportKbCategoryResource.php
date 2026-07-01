<?php

namespace App\Filament\Resources\SupportKbCategories;

use App\Filament\Resources\SupportKbCategories\Pages\CreateSupportKbCategory;
use App\Filament\Resources\SupportKbCategories\Pages\EditSupportKbCategory;
use App\Filament\Resources\SupportKbCategories\Pages\ListSupportKbCategories;
use App\Filament\Resources\SupportKbCategories\Schemas\SupportKbCategoryForm;
use App\Filament\Resources\SupportKbCategories\Tables\SupportKbCategoriesTable;
use App\Models\SupportKbCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupportKbCategoryResource extends Resource
{
    protected static ?string $model = SupportKbCategory::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SupportKbCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportKbCategoriesTable::configure($table);
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
            'index' => ListSupportKbCategories::route('/'),
            'create' => CreateSupportKbCategory::route('/create'),
            'edit' => EditSupportKbCategory::route('/{record}/edit'),
        ];
    }
}
