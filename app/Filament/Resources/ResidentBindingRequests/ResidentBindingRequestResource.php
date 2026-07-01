<?php

namespace App\Filament\Resources\ResidentBindingRequests;

use App\Filament\Resources\ResidentBindingRequests\Pages\CreateResidentBindingRequest;
use App\Filament\Resources\ResidentBindingRequests\Pages\EditResidentBindingRequest;
use App\Filament\Resources\ResidentBindingRequests\Pages\ListResidentBindingRequests;
use App\Filament\Resources\ResidentBindingRequests\Schemas\ResidentBindingRequestForm;
use App\Filament\Resources\ResidentBindingRequests\Tables\ResidentBindingRequestsTable;
use App\Models\ResidentBindingRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ResidentBindingRequestResource extends Resource
{
    protected static ?string $model = ResidentBindingRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ResidentBindingRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResidentBindingRequestsTable::configure($table);
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
            'index' => ListResidentBindingRequests::route('/'),
            'create' => CreateResidentBindingRequest::route('/create'),
            'edit' => EditResidentBindingRequest::route('/{record}/edit'),
        ];
    }
}
