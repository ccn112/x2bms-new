<?php

namespace App\Filament\Resources\BillingPeriods;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\BillingPeriods\Pages\CreateBillingPeriod;
use App\Filament\Resources\BillingPeriods\Pages\EditBillingPeriod;
use App\Filament\Resources\BillingPeriods\Pages\ListBillingPeriods;
use App\Filament\Resources\BillingPeriods\Schemas\BillingPeriodForm;
use App\Filament\Resources\BillingPeriods\Tables\BillingPeriodsTable;
use App\Models\BillingPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BillingPeriodResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = BillingPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Tài chính – Phí';

    protected static ?string $navigationLabel = 'Kỳ phí';

    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return BillingPeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillingPeriodsTable::configure($table);
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
            'index' => ListBillingPeriods::route('/'),
            'create' => CreateBillingPeriod::route('/create'),
            'edit' => EditBillingPeriod::route('/{record}/edit'),
        ];
    }
}
