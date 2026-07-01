<?php

namespace App\Filament\Resources\AiRequests;

use App\Filament\Resources\AiRequests\Pages\CreateAiRequest;
use App\Filament\Resources\AiRequests\Pages\EditAiRequest;
use App\Filament\Resources\AiRequests\Pages\ListAiRequests;
use App\Filament\Resources\AiRequests\Schemas\AiRequestForm;
use App\Filament\Resources\AiRequests\Tables\AiRequestsTable;
use App\Models\AiRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AiRequestResource extends Resource
{
    protected static ?string $model = AiRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AiRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiRequestsTable::configure($table);
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
            'index' => ListAiRequests::route('/'),
            'create' => CreateAiRequest::route('/create'),
            'edit' => EditAiRequest::route('/{record}/edit'),
        ];
    }
}
