<?php

namespace App\Filament\Resources\DataFixRequests;

use App\Filament\Resources\DataFixRequests\Pages\CreateDataFixRequest;
use App\Filament\Resources\DataFixRequests\Pages\EditDataFixRequest;
use App\Filament\Resources\DataFixRequests\Pages\ListDataFixRequests;
use App\Filament\Resources\DataFixRequests\Schemas\DataFixRequestForm;
use App\Filament\Resources\DataFixRequests\Tables\DataFixRequestsTable;
use App\Models\DataFixRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DataFixRequestResource extends Resource
{
    protected static ?string $model = DataFixRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DataFixRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DataFixRequestsTable::configure($table);
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
            'index' => ListDataFixRequests::route('/'),
            'create' => CreateDataFixRequest::route('/create'),
            'edit' => EditDataFixRequest::route('/{record}/edit'),
        ];
    }
}
