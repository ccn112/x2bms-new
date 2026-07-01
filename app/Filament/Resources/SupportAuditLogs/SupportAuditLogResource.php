<?php

namespace App\Filament\Resources\SupportAuditLogs;

use App\Filament\Resources\SupportAuditLogs\Pages\CreateSupportAuditLog;
use App\Filament\Resources\SupportAuditLogs\Pages\EditSupportAuditLog;
use App\Filament\Resources\SupportAuditLogs\Pages\ListSupportAuditLogs;
use App\Filament\Resources\SupportAuditLogs\Schemas\SupportAuditLogForm;
use App\Filament\Resources\SupportAuditLogs\Tables\SupportAuditLogsTable;
use App\Models\SupportAuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupportAuditLogResource extends Resource
{
    protected static ?string $model = SupportAuditLog::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SupportAuditLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportAuditLogsTable::configure($table);
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
            'index' => ListSupportAuditLogs::route('/'),
            'create' => CreateSupportAuditLog::route('/create'),
            'edit' => EditSupportAuditLog::route('/{record}/edit'),
        ];
    }
}
