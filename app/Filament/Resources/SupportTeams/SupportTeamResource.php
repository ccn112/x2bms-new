<?php

namespace App\Filament\Resources\SupportTeams;

use App\Filament\Resources\SupportTeams\Pages\CreateSupportTeam;
use App\Filament\Resources\SupportTeams\Pages\EditSupportTeam;
use App\Filament\Resources\SupportTeams\Pages\ListSupportTeams;
use App\Filament\Resources\SupportTeams\Schemas\SupportTeamForm;
use App\Filament\Resources\SupportTeams\Tables\SupportTeamsTable;
use App\Models\SupportTeam;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupportTeamResource extends Resource
{
    protected static ?string $model = SupportTeam::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SupportTeamForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportTeamsTable::configure($table);
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
            'index' => ListSupportTeams::route('/'),
            'create' => CreateSupportTeam::route('/create'),
            'edit' => EditSupportTeam::route('/{record}/edit'),
        ];
    }
}
