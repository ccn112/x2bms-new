<?php

namespace App\Filament\Resources\IntegrationEvents;

use App\Filament\Resources\IntegrationEvents\Pages\CreateIntegrationEvent;
use App\Filament\Resources\IntegrationEvents\Pages\EditIntegrationEvent;
use App\Filament\Resources\IntegrationEvents\Pages\ListIntegrationEvents;
use App\Filament\Resources\IntegrationEvents\Schemas\IntegrationEventForm;
use App\Filament\Resources\IntegrationEvents\Tables\IntegrationEventsTable;
use App\Models\IntegrationEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntegrationEventResource extends Resource
{
    protected static ?string $model = IntegrationEvent::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IntegrationEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntegrationEventsTable::configure($table);
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
            'index' => ListIntegrationEvents::route('/'),
            'create' => CreateIntegrationEvent::route('/create'),
            'edit' => EditIntegrationEvent::route('/{record}/edit'),
        ];
    }
}
