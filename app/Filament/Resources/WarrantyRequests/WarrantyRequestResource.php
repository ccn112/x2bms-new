<?php

namespace App\Filament\Resources\WarrantyRequests;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\WarrantyRequests\Pages\CreateWarrantyRequest;
use App\Filament\Resources\WarrantyRequests\Pages\EditWarrantyRequest;
use App\Filament\Resources\WarrantyRequests\Pages\ListWarrantyRequests;
use App\Filament\Resources\WarrantyRequests\Schemas\WarrantyRequestForm;
use App\Filament\Resources\WarrantyRequests\Tables\WarrantyRequestsTable;
use App\Models\WarrantyRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WarrantyRequestResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = WarrantyRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return WarrantyRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarrantyRequestsTable::configure($table);
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
            'index' => ListWarrantyRequests::route('/'),
            'create' => CreateWarrantyRequest::route('/create'),
            'edit' => EditWarrantyRequest::route('/{record}/edit'),
        ];
    }
}
