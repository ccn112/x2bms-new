<?php

namespace App\Filament\Resources\PaymentChannels;

use App\Filament\Concerns\SoftDeletableResource;
use App\Filament\Resources\PaymentChannels\Pages\CreatePaymentChannel;
use App\Filament\Resources\PaymentChannels\Pages\EditPaymentChannel;
use App\Filament\Resources\PaymentChannels\Pages\ListPaymentChannels;
use App\Filament\Resources\PaymentChannels\Schemas\PaymentChannelForm;
use App\Filament\Resources\PaymentChannels\Tables\PaymentChannelsTable;
use App\Models\PaymentChannel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Cổng thanh toán per tenant + per project. BQL/owner tự bật/tắt và nhập
 * tài khoản nhận VietQR. Khoá bí mật VNPay/MoMo cấu hình ở ENV (không nhập ở đây).
 */
class PaymentChannelResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = PaymentChannel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'Thanh toán';

    protected static ?string $navigationLabel = 'Cổng thanh toán';

    protected static ?string $modelLabel = 'Cổng thanh toán';

    protected static ?string $pluralModelLabel = 'Cổng thanh toán';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PaymentChannelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentChannelsTable::configure($table);
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
            'index' => ListPaymentChannels::route('/'),
            'create' => CreatePaymentChannel::route('/create'),
            'edit' => EditPaymentChannel::route('/{record}/edit'),
        ];
    }
}
