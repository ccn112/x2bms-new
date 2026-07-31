<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Concerns\SoftDeletableResource;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * CHỈ ĐỌC — xem `Tables/PaymentsTable.php` cho lý do (gate G10, tiền không có
 * Resource sửa được). Không còn `form()`/route create/edit.
 */
class PaymentResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Tài chính – Phí';

    protected static ?string $navigationLabel = 'Thanh toán';

    protected static ?int $navigationSort = 14;

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
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
            'index' => ListPayments::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
