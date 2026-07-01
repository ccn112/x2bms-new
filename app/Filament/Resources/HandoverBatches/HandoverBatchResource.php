<?php

namespace App\Filament\Resources\HandoverBatches;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\HandoverBatches\Pages\CreateHandoverBatch;
use App\Filament\Resources\HandoverBatches\Pages\EditHandoverBatch;
use App\Filament\Resources\HandoverBatches\Pages\ListHandoverBatches;
use App\Filament\Resources\HandoverBatches\Schemas\HandoverBatchForm;
use App\Filament\Resources\HandoverBatches\Tables\HandoverBatchesTable;
use App\Models\HandoverBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HandoverBatchResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = HandoverBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return HandoverBatchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HandoverBatchesTable::configure($table);
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
            'index' => ListHandoverBatches::route('/'),
            'create' => CreateHandoverBatch::route('/create'),
            'edit' => EditHandoverBatch::route('/{record}/edit'),
        ];
    }
}
