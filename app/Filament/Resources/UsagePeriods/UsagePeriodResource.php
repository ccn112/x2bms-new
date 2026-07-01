<?php

namespace App\Filament\Resources\UsagePeriods;

use App\Filament\Resources\UsagePeriods\Pages\CreateUsagePeriod;
use App\Filament\Resources\UsagePeriods\Pages\EditUsagePeriod;
use App\Filament\Resources\UsagePeriods\Pages\ListUsagePeriods;
use App\Filament\Resources\UsagePeriods\Schemas\UsagePeriodForm;
use App\Filament\Resources\UsagePeriods\Tables\UsagePeriodsTable;
use App\Models\UsagePeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UsagePeriodResource extends Resource
{
    protected static ?string $model = UsagePeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return UsagePeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsagePeriodsTable::configure($table);
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
            'index' => ListUsagePeriods::route('/'),
            'create' => CreateUsagePeriod::route('/create'),
            'edit' => EditUsagePeriod::route('/{record}/edit'),
        ];
    }
}
