<?php

namespace App\Filament\Resources\CashVouchers;

use App\Filament\Resources\CashVouchers\Pages\CreateCashVoucher;
use App\Filament\Resources\CashVouchers\Pages\EditCashVoucher;
use App\Filament\Resources\CashVouchers\Pages\ListCashVouchers;
use App\Filament\Resources\CashVouchers\Schemas\CashVoucherForm;
use App\Filament\Resources\CashVouchers\Tables\CashVouchersTable;
use App\Models\CashVoucher;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashVoucherResource extends Resource
{
    protected static ?string $model = CashVoucher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CashVoucherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashVouchersTable::configure($table);
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
            'index' => ListCashVouchers::route('/'),
            'create' => CreateCashVoucher::route('/create'),
            'edit' => EditCashVoucher::route('/{record}/edit'),
        ];
    }
}
