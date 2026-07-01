<?php

namespace App\Filament\Resources\FormFields;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\FormFields\Pages\CreateFormField;
use App\Filament\Resources\FormFields\Pages\EditFormField;
use App\Filament\Resources\FormFields\Pages\ListFormFields;
use App\Filament\Resources\FormFields\Schemas\FormFieldForm;
use App\Filament\Resources\FormFields\Tables\FormFieldsTable;
use App\Models\FormField;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FormFieldResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = FormField::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return FormFieldForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FormFieldsTable::configure($table);
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
            'index' => ListFormFields::route('/'),
            'create' => CreateFormField::route('/create'),
            'edit' => EditFormField::route('/{record}/edit'),
        ];
    }
}
