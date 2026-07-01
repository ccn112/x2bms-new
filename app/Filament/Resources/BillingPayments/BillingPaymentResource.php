<?php

namespace App\Filament\Resources\BillingPayments;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\BillingPayments\Pages\CreateBillingPayment;
use App\Filament\Resources\BillingPayments\Pages\EditBillingPayment;
use App\Filament\Resources\BillingPayments\Pages\ListBillingPayments;
use App\Filament\Resources\BillingPayments\Schemas\BillingPaymentForm;
use App\Filament\Resources\BillingPayments\Tables\BillingPaymentsTable;
use App\Models\BillingPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BillingPaymentResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = BillingPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BillingPaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillingPaymentsTable::configure($table);
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
            'index' => ListBillingPayments::route('/'),
            'create' => CreateBillingPayment::route('/create'),
            'edit' => EditBillingPayment::route('/{record}/edit'),
        ];
    }
}
