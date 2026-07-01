<?php

namespace App\Filament\Resources\SupportEscalations;

use App\Filament\Resources\SupportEscalations\Pages\CreateSupportEscalation;
use App\Filament\Resources\SupportEscalations\Pages\EditSupportEscalation;
use App\Filament\Resources\SupportEscalations\Pages\ListSupportEscalations;
use App\Filament\Resources\SupportEscalations\Schemas\SupportEscalationForm;
use App\Filament\Resources\SupportEscalations\Tables\SupportEscalationsTable;
use App\Models\SupportEscalation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupportEscalationResource extends Resource
{
    protected static ?string $model = SupportEscalation::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SupportEscalationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportEscalationsTable::configure($table);
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
            'index' => ListSupportEscalations::route('/'),
            'create' => CreateSupportEscalation::route('/create'),
            'edit' => EditSupportEscalation::route('/{record}/edit'),
        ];
    }
}
