<?php

namespace App\Filament\Sa\Resources\DocVersions;

use App\Filament\Sa\Resources\DocVersions\Pages\CreateDocVersion;
use App\Filament\Sa\Resources\DocVersions\Pages\EditDocVersion;
use App\Filament\Sa\Resources\DocVersions\Pages\ListDocVersions;
use App\Filament\Sa\Resources\DocVersions\RelationManagers\ItemsRelationManager;
use App\Filament\Sa\Resources\DocVersions\Schemas\DocVersionForm;
use App\Filament\Sa\Resources\DocVersions\Tables\DocVersionsTable;
use App\Models\DocVersion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DocVersionResource extends Resource
{
    protected static ?string $model = DocVersion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Tài liệu';

    protected static ?string $navigationLabel = 'Phiên bản & Backlog';

    protected static ?string $modelLabel = 'phiên bản tài liệu';

    protected static ?string $pluralModelLabel = 'phiên bản tài liệu';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return DocVersionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocVersionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocVersions::route('/'),
            'create' => CreateDocVersion::route('/create'),
            'edit' => EditDocVersion::route('/{record}/edit'),
        ];
    }
}
