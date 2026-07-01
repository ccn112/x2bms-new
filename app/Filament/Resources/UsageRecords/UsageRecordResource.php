<?php

namespace App\Filament\Resources\UsageRecords;

use App\Filament\Resources\UsageRecords\Pages\CreateUsageRecord;
use App\Filament\Resources\UsageRecords\Pages\EditUsageRecord;
use App\Filament\Resources\UsageRecords\Pages\ListUsageRecords;
use App\Filament\Resources\UsageRecords\Schemas\UsageRecordForm;
use App\Filament\Resources\UsageRecords\Tables\UsageRecordsTable;
use App\Models\UsageRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UsageRecordResource extends Resource
{
    protected static ?string $model = UsageRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return UsageRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsageRecordsTable::configure($table);
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
            'index' => ListUsageRecords::route('/'),
            'create' => CreateUsageRecord::route('/create'),
            'edit' => EditUsageRecord::route('/{record}/edit'),
        ];
    }
}
