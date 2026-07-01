<?php

namespace App\Filament\Resources\SmartHomeAccounts;

use App\Filament\Resources\SmartHomeAccounts\Pages\CreateSmartHomeAccount;
use App\Filament\Resources\SmartHomeAccounts\Pages\EditSmartHomeAccount;
use App\Filament\Resources\SmartHomeAccounts\Pages\ListSmartHomeAccounts;
use App\Filament\Resources\SmartHomeAccounts\Schemas\SmartHomeAccountForm;
use App\Filament\Resources\SmartHomeAccounts\Tables\SmartHomeAccountsTable;
use App\Models\SmartHomeAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SmartHomeAccountResource extends Resource
{
    protected static ?string $model = SmartHomeAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SmartHomeAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SmartHomeAccountsTable::configure($table);
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
            'index' => ListSmartHomeAccounts::route('/'),
            'create' => CreateSmartHomeAccount::route('/create'),
            'edit' => EditSmartHomeAccount::route('/{record}/edit'),
        ];
    }
}
