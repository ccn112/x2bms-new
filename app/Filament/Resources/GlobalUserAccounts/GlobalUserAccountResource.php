<?php

namespace App\Filament\Resources\GlobalUserAccounts;

use App\Filament\Resources\GlobalUserAccounts\Pages\CreateGlobalUserAccount;
use App\Filament\Resources\GlobalUserAccounts\Pages\EditGlobalUserAccount;
use App\Filament\Resources\GlobalUserAccounts\Pages\ListGlobalUserAccounts;
use App\Filament\Resources\GlobalUserAccounts\Schemas\GlobalUserAccountForm;
use App\Filament\Resources\GlobalUserAccounts\Tables\GlobalUserAccountsTable;
use App\Models\GlobalUserAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GlobalUserAccountResource extends Resource
{
    protected static ?string $model = GlobalUserAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return GlobalUserAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GlobalUserAccountsTable::configure($table);
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
            'index' => ListGlobalUserAccounts::route('/'),
            'create' => CreateGlobalUserAccount::route('/create'),
            'edit' => EditGlobalUserAccount::route('/{record}/edit'),
        ];
    }
}
