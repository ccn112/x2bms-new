<?php

namespace App\Filament\Resources\SubscriptionAddons;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\SubscriptionAddons\Pages\CreateSubscriptionAddon;
use App\Filament\Resources\SubscriptionAddons\Pages\EditSubscriptionAddon;
use App\Filament\Resources\SubscriptionAddons\Pages\ListSubscriptionAddons;
use App\Filament\Resources\SubscriptionAddons\Schemas\SubscriptionAddonForm;
use App\Filament\Resources\SubscriptionAddons\Tables\SubscriptionAddonsTable;
use App\Models\SubscriptionAddon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SubscriptionAddonResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = SubscriptionAddon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SubscriptionAddonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionAddonsTable::configure($table);
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
            'index' => ListSubscriptionAddons::route('/'),
            'create' => CreateSubscriptionAddon::route('/create'),
            'edit' => EditSubscriptionAddon::route('/{record}/edit'),
        ];
    }
}
