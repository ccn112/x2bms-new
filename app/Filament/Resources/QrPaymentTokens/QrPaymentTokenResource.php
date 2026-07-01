<?php

namespace App\Filament\Resources\QrPaymentTokens;

use App\Filament\Resources\QrPaymentTokens\Pages\CreateQrPaymentToken;
use App\Filament\Resources\QrPaymentTokens\Pages\EditQrPaymentToken;
use App\Filament\Resources\QrPaymentTokens\Pages\ListQrPaymentTokens;
use App\Filament\Resources\QrPaymentTokens\Schemas\QrPaymentTokenForm;
use App\Filament\Resources\QrPaymentTokens\Tables\QrPaymentTokensTable;
use App\Models\QrPaymentToken;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class QrPaymentTokenResource extends Resource
{
    protected static ?string $model = QrPaymentToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return QrPaymentTokenForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QrPaymentTokensTable::configure($table);
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
            'index' => ListQrPaymentTokens::route('/'),
            'create' => CreateQrPaymentToken::route('/create'),
            'edit' => EditQrPaymentToken::route('/{record}/edit'),
        ];
    }
}
