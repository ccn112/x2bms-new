<?php

namespace App\Filament\Sa\Resources\DocPages;

use App\Filament\Sa\Resources\DocPages\Pages\CreateDocPage;
use App\Filament\Sa\Resources\DocPages\Pages\EditDocPage;
use App\Filament\Sa\Resources\DocPages\Pages\ListDocPages;
use App\Filament\Sa\Resources\DocPages\RelationManagers\RevisionsRelationManager;
use App\Filament\Sa\Resources\DocPages\Schemas\DocPageForm;
use App\Filament\Sa\Resources\DocPages\Tables\DocPagesTable;
use App\Models\DocPage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DocPageResource extends Resource
{
    protected static ?string $model = DocPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Tài liệu';

    protected static ?string $navigationLabel = 'Trang tài liệu';

    protected static ?string $modelLabel = 'trang tài liệu';

    protected static ?string $pluralModelLabel = 'trang tài liệu';

    public static function form(Schema $schema): Schema
    {
        return DocPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocPagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RevisionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocPages::route('/'),
            'create' => CreateDocPage::route('/create'),
            'edit' => EditDocPage::route('/{record}/edit'),
        ];
    }
}
