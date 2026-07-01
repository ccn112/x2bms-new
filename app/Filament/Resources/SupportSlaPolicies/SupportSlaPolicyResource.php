<?php

namespace App\Filament\Resources\SupportSlaPolicies;

use App\Filament\Resources\SupportSlaPolicies\Pages\CreateSupportSlaPolicy;
use App\Filament\Resources\SupportSlaPolicies\Pages\EditSupportSlaPolicy;
use App\Filament\Resources\SupportSlaPolicies\Pages\ListSupportSlaPolicies;
use App\Filament\Resources\SupportSlaPolicies\Schemas\SupportSlaPolicyForm;
use App\Filament\Resources\SupportSlaPolicies\Tables\SupportSlaPoliciesTable;
use App\Models\SupportSlaPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupportSlaPolicyResource extends Resource
{
    protected static ?string $model = SupportSlaPolicy::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SupportSlaPolicyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportSlaPoliciesTable::configure($table);
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
            'index' => ListSupportSlaPolicies::route('/'),
            'create' => CreateSupportSlaPolicy::route('/create'),
            'edit' => EditSupportSlaPolicy::route('/{record}/edit'),
        ];
    }
}
