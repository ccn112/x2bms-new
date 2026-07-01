<?php

namespace App\Filament\Resources\BillingInvoices;

use App\Filament\Resources\BillingInvoices\Pages\CreateBillingInvoice;
use App\Filament\Resources\BillingInvoices\Pages\EditBillingInvoice;
use App\Filament\Resources\BillingInvoices\Pages\ListBillingInvoices;
use App\Filament\Resources\BillingInvoices\Schemas\BillingInvoiceForm;
use App\Filament\Resources\BillingInvoices\Tables\BillingInvoicesTable;
use App\Models\BillingInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BillingInvoiceResource extends Resource
{
    protected static ?string $model = BillingInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BillingInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillingInvoicesTable::configure($table);
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
            'index' => ListBillingInvoices::route('/'),
            'create' => CreateBillingInvoice::route('/create'),
            'edit' => EditBillingInvoice::route('/{record}/edit'),
        ];
    }
}
