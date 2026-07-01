<?php

namespace App\Filament\Resources\AiRetrievalLogs;

use App\Filament\Resources\AiRetrievalLogs\Pages\CreateAiRetrievalLog;
use App\Filament\Resources\AiRetrievalLogs\Pages\EditAiRetrievalLog;
use App\Filament\Resources\AiRetrievalLogs\Pages\ListAiRetrievalLogs;
use App\Filament\Resources\AiRetrievalLogs\Schemas\AiRetrievalLogForm;
use App\Filament\Resources\AiRetrievalLogs\Tables\AiRetrievalLogsTable;
use App\Models\AiRetrievalLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AiRetrievalLogResource extends Resource
{
    protected static ?string $model = AiRetrievalLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AiRetrievalLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiRetrievalLogsTable::configure($table);
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
            'index' => ListAiRetrievalLogs::route('/'),
            'create' => CreateAiRetrievalLog::route('/create'),
            'edit' => EditAiRetrievalLog::route('/{record}/edit'),
        ];
    }
}
