<?php

namespace App\Filament\Resources\SupportReports;

use App\Filament\Resources\SupportReports\Pages\CreateSupportReport;
use App\Filament\Resources\SupportReports\Pages\EditSupportReport;
use App\Filament\Resources\SupportReports\Pages\ListSupportReports;
use App\Filament\Resources\SupportReports\Schemas\SupportReportForm;
use App\Filament\Resources\SupportReports\Tables\SupportReportsTable;
use App\Models\SupportReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupportReportResource extends Resource
{
    protected static ?string $model = SupportReport::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SupportReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportReportsTable::configure($table);
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
            'index' => ListSupportReports::route('/'),
            'create' => CreateSupportReport::route('/create'),
            'edit' => EditSupportReport::route('/{record}/edit'),
        ];
    }
}
