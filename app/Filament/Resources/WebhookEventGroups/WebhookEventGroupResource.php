<?php

namespace App\Filament\Resources\WebhookEventGroups;

use App\Filament\Resources\WebhookEventGroups\Pages\CreateWebhookEventGroup;
use App\Filament\Resources\WebhookEventGroups\Pages\EditWebhookEventGroup;
use App\Filament\Resources\WebhookEventGroups\Pages\ListWebhookEventGroups;
use App\Filament\Resources\WebhookEventGroups\Schemas\WebhookEventGroupForm;
use App\Filament\Resources\WebhookEventGroups\Tables\WebhookEventGroupsTable;
use App\Models\WebhookEventGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WebhookEventGroupResource extends Resource
{
    protected static ?string $model = WebhookEventGroup::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return WebhookEventGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebhookEventGroupsTable::configure($table);
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
            'index' => ListWebhookEventGroups::route('/'),
            'create' => CreateWebhookEventGroup::route('/create'),
            'edit' => EditWebhookEventGroup::route('/{record}/edit'),
        ];
    }
}
