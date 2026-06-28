<?php

namespace App\Filament\Resources\Floors;

use App\Filament\Resources\Floors\Pages\CreateFloor;
use App\Filament\Resources\Floors\Pages\EditFloor;
use App\Filament\Resources\Floors\Pages\ListFloors;
use App\Filament\Resources\Floors\Schemas\FloorForm;
use App\Filament\Resources\Floors\Tables\FloorsTable;
use App\Models\Floor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FloorResource extends Resource
{
    protected static ?string $model = Floor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return FloorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FloorsTable::configure($table);
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
            'index' => ListFloors::route('/'),
            'create' => CreateFloor::route('/create'),
            'edit' => EditFloor::route('/{record}/edit'),
        ];
    }
}
