<?php

namespace App\Filament\Resources\SubscriptionContracts;

use App\Filament\Resources\SubscriptionContracts\Pages\CreateSubscriptionContract;
use App\Filament\Resources\SubscriptionContracts\Pages\EditSubscriptionContract;
use App\Filament\Resources\SubscriptionContracts\Pages\ListSubscriptionContracts;
use App\Filament\Resources\SubscriptionContracts\Schemas\SubscriptionContractForm;
use App\Filament\Resources\SubscriptionContracts\Tables\SubscriptionContractsTable;
use App\Models\SubscriptionContract;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SubscriptionContractResource extends Resource
{
    protected static ?string $model = SubscriptionContract::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SubscriptionContractForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionContractsTable::configure($table);
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
            'index' => ListSubscriptionContracts::route('/'),
            'create' => CreateSubscriptionContract::route('/create'),
            'edit' => EditSubscriptionContract::route('/{record}/edit'),
        ];
    }
}
