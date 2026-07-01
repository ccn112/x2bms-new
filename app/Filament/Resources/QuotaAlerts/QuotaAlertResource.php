<?php

namespace App\Filament\Resources\QuotaAlerts;

use App\Filament\Resources\QuotaAlerts\Pages\CreateQuotaAlert;
use App\Filament\Resources\QuotaAlerts\Pages\EditQuotaAlert;
use App\Filament\Resources\QuotaAlerts\Pages\ListQuotaAlerts;
use App\Filament\Resources\QuotaAlerts\Schemas\QuotaAlertForm;
use App\Filament\Resources\QuotaAlerts\Tables\QuotaAlertsTable;
use App\Models\QuotaAlert;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class QuotaAlertResource extends Resource
{
    protected static ?string $model = QuotaAlert::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return QuotaAlertForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuotaAlertsTable::configure($table);
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
            'index' => ListQuotaAlerts::route('/'),
            'create' => CreateQuotaAlert::route('/create'),
            'edit' => EditQuotaAlert::route('/{record}/edit'),
        ];
    }
}
