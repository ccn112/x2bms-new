<?php

namespace App\Filament\Resources\PlatformContents;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\PlatformContents\Pages\CreatePlatformContent;
use App\Filament\Resources\PlatformContents\Pages\EditPlatformContent;
use App\Filament\Resources\PlatformContents\Pages\ListPlatformContents;
use App\Filament\Resources\PlatformContents\Schemas\PlatformContentForm;
use App\Filament\Resources\PlatformContents\Tables\PlatformContentsTable;
use App\Models\PlatformContent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PlatformContentResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = PlatformContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PlatformContentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlatformContentsTable::configure($table);
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
            'index' => ListPlatformContents::route('/'),
            'create' => CreatePlatformContent::route('/create'),
            'edit' => EditPlatformContent::route('/{record}/edit'),
        ];
    }
}
