<?php

namespace App\Filament\Resources\LoyaltyAccounts;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\LoyaltyAccounts\Pages\CreateLoyaltyAccount;
use App\Filament\Resources\LoyaltyAccounts\Pages\EditLoyaltyAccount;
use App\Filament\Resources\LoyaltyAccounts\Pages\ListLoyaltyAccounts;
use App\Filament\Resources\LoyaltyAccounts\Schemas\LoyaltyAccountForm;
use App\Filament\Resources\LoyaltyAccounts\Tables\LoyaltyAccountsTable;
use App\Models\LoyaltyAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LoyaltyAccountResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = LoyaltyAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return LoyaltyAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoyaltyAccountsTable::configure($table);
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
            'index' => ListLoyaltyAccounts::route('/'),
            'create' => CreateLoyaltyAccount::route('/create'),
            'edit' => EditLoyaltyAccount::route('/{record}/edit'),
        ];
    }
}
