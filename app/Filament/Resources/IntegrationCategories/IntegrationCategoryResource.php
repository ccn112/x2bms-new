<?php

namespace App\Filament\Resources\IntegrationCategories;

use App\Filament\Resources\IntegrationCategories\Pages\CreateIntegrationCategory;
use App\Filament\Resources\IntegrationCategories\Pages\EditIntegrationCategory;
use App\Filament\Resources\IntegrationCategories\Pages\ListIntegrationCategories;
use App\Filament\Resources\IntegrationCategories\Schemas\IntegrationCategoryForm;
use App\Filament\Resources\IntegrationCategories\Tables\IntegrationCategoriesTable;
use App\Models\IntegrationCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntegrationCategoryResource extends Resource
{
    protected static ?string $model = IntegrationCategory::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IntegrationCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntegrationCategoriesTable::configure($table);
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
            'index' => ListIntegrationCategories::route('/'),
            'create' => CreateIntegrationCategory::route('/create'),
            'edit' => EditIntegrationCategory::route('/{record}/edit'),
        ];
    }
}
