<?php

namespace App\Filament\Resources\BillingReconciliations;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\BillingReconciliations\Pages\CreateBillingReconciliation;
use App\Filament\Resources\BillingReconciliations\Pages\EditBillingReconciliation;
use App\Filament\Resources\BillingReconciliations\Pages\ListBillingReconciliations;
use App\Filament\Resources\BillingReconciliations\Schemas\BillingReconciliationForm;
use App\Filament\Resources\BillingReconciliations\Tables\BillingReconciliationsTable;
use App\Models\BillingReconciliation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BillingReconciliationResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = BillingReconciliation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BillingReconciliationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillingReconciliationsTable::configure($table);
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
            'index' => ListBillingReconciliations::route('/'),
            'create' => CreateBillingReconciliation::route('/create'),
            'edit' => EditBillingReconciliation::route('/{record}/edit'),
        ];
    }
}
