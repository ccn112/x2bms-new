<?php

namespace App\Filament\Resources\DynamicForms;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\DynamicForms\Pages\CreateDynamicForm;
use App\Filament\Resources\DynamicForms\Pages\EditDynamicForm;
use App\Filament\Resources\DynamicForms\Pages\ListDynamicForms;
use App\Filament\Resources\DynamicForms\Schemas\DynamicFormForm;
use App\Filament\Resources\DynamicForms\Tables\DynamicFormsTable;
use App\Models\DynamicForm;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DynamicFormResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = DynamicForm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DynamicFormForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DynamicFormsTable::configure($table);
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
            'index' => ListDynamicForms::route('/'),
            'create' => CreateDynamicForm::route('/create'),
            'edit' => EditDynamicForm::route('/{record}/edit'),
        ];
    }
}
