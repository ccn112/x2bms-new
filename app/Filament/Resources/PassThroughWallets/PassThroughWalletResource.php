<?php

namespace App\Filament\Resources\PassThroughWallets;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\PassThroughWallets\Pages\CreatePassThroughWallet;
use App\Filament\Resources\PassThroughWallets\Pages\EditPassThroughWallet;
use App\Filament\Resources\PassThroughWallets\Pages\ListPassThroughWallets;
use App\Filament\Resources\PassThroughWallets\Schemas\PassThroughWalletForm;
use App\Filament\Resources\PassThroughWallets\Tables\PassThroughWalletsTable;
use App\Models\PassThroughWallet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PassThroughWalletResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = PassThroughWallet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PassThroughWalletForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PassThroughWalletsTable::configure($table);
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
            'index' => ListPassThroughWallets::route('/'),
            'create' => CreatePassThroughWallet::route('/create'),
            'edit' => EditPassThroughWallet::route('/{record}/edit'),
        ];
    }
}
