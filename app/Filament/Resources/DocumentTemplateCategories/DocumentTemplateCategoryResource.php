<?php

namespace App\Filament\Resources\DocumentTemplateCategories;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\DocumentTemplateCategories\Pages\CreateDocumentTemplateCategory;
use App\Filament\Resources\DocumentTemplateCategories\Pages\EditDocumentTemplateCategory;
use App\Filament\Resources\DocumentTemplateCategories\Pages\ListDocumentTemplateCategories;
use App\Filament\Resources\DocumentTemplateCategories\Schemas\DocumentTemplateCategoryForm;
use App\Filament\Resources\DocumentTemplateCategories\Tables\DocumentTemplateCategoriesTable;
use App\Models\DocumentTemplateCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DocumentTemplateCategoryResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = DocumentTemplateCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DocumentTemplateCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentTemplateCategoriesTable::configure($table);
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
            'index' => ListDocumentTemplateCategories::route('/'),
            'create' => CreateDocumentTemplateCategory::route('/create'),
            'edit' => EditDocumentTemplateCategory::route('/{record}/edit'),
        ];
    }
}
