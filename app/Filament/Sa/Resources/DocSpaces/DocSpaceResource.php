<?php

namespace App\Filament\Sa\Resources\DocSpaces;

use App\Filament\Sa\Resources\DocSpaces\Pages\CreateDocSpace;
use App\Filament\Sa\Resources\DocSpaces\Pages\EditDocSpace;
use App\Filament\Sa\Resources\DocSpaces\Pages\ListDocSpaces;
use App\Filament\Sa\Resources\DocSpaces\Schemas\DocSpaceForm;
use App\Filament\Sa\Resources\DocSpaces\Tables\DocSpacesTable;
use App\Models\DocSpace;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DocSpaceResource extends Resource
{
    protected static ?string $model = DocSpace::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Tài liệu';

    protected static ?string $navigationLabel = 'Không gian tài liệu';

    protected static ?string $modelLabel = 'không gian tài liệu';

    protected static ?string $pluralModelLabel = 'không gian tài liệu';

    public static function form(Schema $schema): Schema
    {
        return DocSpaceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocSpacesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocSpaces::route('/'),
            'create' => CreateDocSpace::route('/create'),
            'edit' => EditDocSpace::route('/{record}/edit'),
        ];
    }
}
