<?php

namespace App\Filament\Resources\BillingAdjustments;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\BillingAdjustments\Pages\CreateBillingAdjustment;
use App\Filament\Resources\BillingAdjustments\Pages\EditBillingAdjustment;
use App\Filament\Resources\BillingAdjustments\Pages\ListBillingAdjustments;
use App\Filament\Resources\BillingAdjustments\Schemas\BillingAdjustmentForm;
use App\Filament\Resources\BillingAdjustments\Tables\BillingAdjustmentsTable;
use App\Models\BillingAdjustment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BillingAdjustmentResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = BillingAdjustment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BillingAdjustmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillingAdjustmentsTable::configure($table);
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
            'index' => ListBillingAdjustments::route('/'),
            'create' => CreateBillingAdjustment::route('/create'),
            'edit' => EditBillingAdjustment::route('/{record}/edit'),
        ];
    }
}
