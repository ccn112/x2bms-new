<?php

namespace App\Filament\Resources\ResidentUnitBindings;

use App\Filament\Resources\ResidentUnitBindings\Pages\CreateResidentUnitBinding;
use App\Filament\Resources\ResidentUnitBindings\Pages\EditResidentUnitBinding;
use App\Filament\Resources\ResidentUnitBindings\Pages\ListResidentUnitBindings;
use App\Filament\Resources\ResidentUnitBindings\Schemas\ResidentUnitBindingForm;
use App\Filament\Resources\ResidentUnitBindings\Tables\ResidentUnitBindingsTable;
use App\Models\ResidentUnitBinding;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ResidentUnitBindingResource extends Resource
{
    protected static ?string $model = ResidentUnitBinding::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ResidentUnitBindingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResidentUnitBindingsTable::configure($table);
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
            'index' => ListResidentUnitBindings::route('/'),
            'create' => CreateResidentUnitBinding::route('/create'),
            'edit' => EditResidentUnitBinding::route('/{record}/edit'),
        ];
    }
}
