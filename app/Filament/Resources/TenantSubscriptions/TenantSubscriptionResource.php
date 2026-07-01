<?php

namespace App\Filament\Resources\TenantSubscriptions;

use App\Filament\Resources\TenantSubscriptions\Pages\CreateTenantSubscription;
use App\Filament\Resources\TenantSubscriptions\Pages\EditTenantSubscription;
use App\Filament\Resources\TenantSubscriptions\Pages\ListTenantSubscriptions;
use App\Filament\Resources\TenantSubscriptions\Schemas\TenantSubscriptionForm;
use App\Filament\Resources\TenantSubscriptions\Tables\TenantSubscriptionsTable;
use App\Models\TenantSubscription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TenantSubscriptionResource extends Resource
{
    protected static ?string $model = TenantSubscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TenantSubscriptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantSubscriptionsTable::configure($table);
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
            'index' => ListTenantSubscriptions::route('/'),
            'create' => CreateTenantSubscription::route('/create'),
            'edit' => EditTenantSubscription::route('/{record}/edit'),
        ];
    }
}
