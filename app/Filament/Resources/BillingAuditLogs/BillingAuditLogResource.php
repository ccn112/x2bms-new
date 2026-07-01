<?php

namespace App\Filament\Resources\BillingAuditLogs;

use App\Filament\Resources\BillingAuditLogs\Pages\CreateBillingAuditLog;
use App\Filament\Resources\BillingAuditLogs\Pages\EditBillingAuditLog;
use App\Filament\Resources\BillingAuditLogs\Pages\ListBillingAuditLogs;
use App\Filament\Resources\BillingAuditLogs\Schemas\BillingAuditLogForm;
use App\Filament\Resources\BillingAuditLogs\Tables\BillingAuditLogsTable;
use App\Models\BillingAuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BillingAuditLogResource extends Resource
{
    protected static ?string $model = BillingAuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BillingAuditLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillingAuditLogsTable::configure($table);
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
            'index' => ListBillingAuditLogs::route('/'),
            'create' => CreateBillingAuditLog::route('/create'),
            'edit' => EditBillingAuditLog::route('/{record}/edit'),
        ];
    }
}
