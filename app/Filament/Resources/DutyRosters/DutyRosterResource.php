<?php

namespace App\Filament\Resources\DutyRosters;

use App\Filament\Resources\DutyRosters\Pages\CreateDutyRoster;
use App\Filament\Resources\DutyRosters\Pages\EditDutyRoster;
use App\Filament\Resources\DutyRosters\Pages\ListDutyRosters;
use App\Filament\Resources\DutyRosters\Schemas\DutyRosterForm;
use App\Filament\Resources\DutyRosters\Tables\DutyRostersTable;
use App\Models\DutyRoster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DutyRosterResource extends Resource
{
    protected static ?string $model = DutyRoster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DutyRosterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DutyRostersTable::configure($table);
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
            'index' => ListDutyRosters::route('/'),
            'create' => CreateDutyRoster::route('/create'),
            'edit' => EditDutyRoster::route('/{record}/edit'),
        ];
    }
}
