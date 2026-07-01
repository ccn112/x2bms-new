<?php

namespace App\Filament\Resources\IntercomEvents;

use App\Filament\Resources\IntercomEvents\Pages\CreateIntercomEvent;
use App\Filament\Resources\IntercomEvents\Pages\EditIntercomEvent;
use App\Filament\Resources\IntercomEvents\Pages\ListIntercomEvents;
use App\Filament\Resources\IntercomEvents\Schemas\IntercomEventForm;
use App\Filament\Resources\IntercomEvents\Tables\IntercomEventsTable;
use App\Models\IntercomEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntercomEventResource extends Resource
{
    protected static ?string $model = IntercomEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IntercomEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntercomEventsTable::configure($table);
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
            'index' => ListIntercomEvents::route('/'),
            'create' => CreateIntercomEvent::route('/create'),
            'edit' => EditIntercomEvent::route('/{record}/edit'),
        ];
    }
}
