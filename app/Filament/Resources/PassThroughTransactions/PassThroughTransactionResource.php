<?php

namespace App\Filament\Resources\PassThroughTransactions;

use App\Filament\Resources\PassThroughTransactions\Pages\CreatePassThroughTransaction;
use App\Filament\Resources\PassThroughTransactions\Pages\EditPassThroughTransaction;
use App\Filament\Resources\PassThroughTransactions\Pages\ListPassThroughTransactions;
use App\Filament\Resources\PassThroughTransactions\Schemas\PassThroughTransactionForm;
use App\Filament\Resources\PassThroughTransactions\Tables\PassThroughTransactionsTable;
use App\Models\PassThroughTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PassThroughTransactionResource extends Resource
{
    protected static ?string $model = PassThroughTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PassThroughTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PassThroughTransactionsTable::configure($table);
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
            'index' => ListPassThroughTransactions::route('/'),
            'create' => CreatePassThroughTransaction::route('/create'),
            'edit' => EditPassThroughTransaction::route('/{record}/edit'),
        ];
    }
}
