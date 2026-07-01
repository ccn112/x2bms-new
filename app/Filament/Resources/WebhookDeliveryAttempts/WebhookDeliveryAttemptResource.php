<?php

namespace App\Filament\Resources\WebhookDeliveryAttempts;

use App\Filament\Resources\WebhookDeliveryAttempts\Pages\CreateWebhookDeliveryAttempt;
use App\Filament\Resources\WebhookDeliveryAttempts\Pages\EditWebhookDeliveryAttempt;
use App\Filament\Resources\WebhookDeliveryAttempts\Pages\ListWebhookDeliveryAttempts;
use App\Filament\Resources\WebhookDeliveryAttempts\Schemas\WebhookDeliveryAttemptForm;
use App\Filament\Resources\WebhookDeliveryAttempts\Tables\WebhookDeliveryAttemptsTable;
use App\Models\WebhookDeliveryAttempt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WebhookDeliveryAttemptResource extends Resource
{
    protected static ?string $model = WebhookDeliveryAttempt::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return WebhookDeliveryAttemptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebhookDeliveryAttemptsTable::configure($table);
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
            'index' => ListWebhookDeliveryAttempts::route('/'),
            'create' => CreateWebhookDeliveryAttempt::route('/create'),
            'edit' => EditWebhookDeliveryAttempt::route('/{record}/edit'),
        ];
    }
}
