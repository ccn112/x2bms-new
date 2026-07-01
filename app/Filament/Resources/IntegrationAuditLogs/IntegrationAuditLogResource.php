<?php

namespace App\Filament\Resources\IntegrationAuditLogs;

use App\Filament\Resources\IntegrationAuditLogs\Pages\CreateIntegrationAuditLog;
use App\Filament\Resources\IntegrationAuditLogs\Pages\EditIntegrationAuditLog;
use App\Filament\Resources\IntegrationAuditLogs\Pages\ListIntegrationAuditLogs;
use App\Filament\Resources\IntegrationAuditLogs\Schemas\IntegrationAuditLogForm;
use App\Filament\Resources\IntegrationAuditLogs\Tables\IntegrationAuditLogsTable;
use App\Models\IntegrationAuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntegrationAuditLogResource extends Resource
{
    protected static ?string $model = IntegrationAuditLog::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IntegrationAuditLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntegrationAuditLogsTable::configure($table);
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
            'index' => ListIntegrationAuditLogs::route('/'),
            'create' => CreateIntegrationAuditLog::route('/create'),
            'edit' => EditIntegrationAuditLog::route('/{record}/edit'),
        ];
    }
}
