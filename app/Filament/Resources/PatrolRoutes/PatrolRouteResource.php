<?php

namespace App\Filament\Resources\PatrolRoutes;

use App\Filament\Resources\PatrolRoutes\Pages\CreatePatrolRoute;
use App\Filament\Resources\PatrolRoutes\Pages\EditPatrolRoute;
use App\Filament\Resources\PatrolRoutes\Pages\ListPatrolRoutes;
use App\Filament\Resources\PatrolRoutes\Schemas\PatrolRouteForm;
use App\Filament\Resources\PatrolRoutes\Tables\PatrolRoutesTable;
use App\Models\PatrolRoute;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PatrolRouteResource extends Resource
{
    protected static ?string $model = PatrolRoute::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PatrolRouteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatrolRoutesTable::configure($table);
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
            'index' => ListPatrolRoutes::route('/'),
            'create' => CreatePatrolRoute::route('/create'),
            'edit' => EditPatrolRoute::route('/{record}/edit'),
        ];
    }
}
