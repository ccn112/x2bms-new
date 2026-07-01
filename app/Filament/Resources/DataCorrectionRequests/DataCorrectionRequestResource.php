<?php

namespace App\Filament\Resources\DataCorrectionRequests;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\DataCorrectionRequests\Pages\CreateDataCorrectionRequest;
use App\Filament\Resources\DataCorrectionRequests\Pages\EditDataCorrectionRequest;
use App\Filament\Resources\DataCorrectionRequests\Pages\ListDataCorrectionRequests;
use App\Filament\Resources\DataCorrectionRequests\Schemas\DataCorrectionRequestForm;
use App\Filament\Resources\DataCorrectionRequests\Tables\DataCorrectionRequestsTable;
use App\Models\DataCorrectionRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DataCorrectionRequestResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = DataCorrectionRequest::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DataCorrectionRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DataCorrectionRequestsTable::configure($table);
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
            'index' => ListDataCorrectionRequests::route('/'),
            'create' => CreateDataCorrectionRequest::route('/create'),
            'edit' => EditDataCorrectionRequest::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
