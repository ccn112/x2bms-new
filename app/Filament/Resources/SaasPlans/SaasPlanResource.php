<?php

namespace App\Filament\Resources\SaasPlans;

use App\Filament\Resources\SaasPlans\Pages\CreateSaasPlan;
use App\Filament\Resources\SaasPlans\Pages\EditSaasPlan;
use App\Filament\Resources\SaasPlans\Pages\ListSaasPlans;
use App\Filament\Resources\SaasPlans\Schemas\SaasPlanForm;
use App\Filament\Resources\SaasPlans\Tables\SaasPlansTable;
use App\Models\SaasPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SaasPlanResource extends Resource
{
    protected static ?string $model = SaasPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SaasPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SaasPlansTable::configure($table);
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
            'index' => ListSaasPlans::route('/'),
            'create' => CreateSaasPlan::route('/create'),
            'edit' => EditSaasPlan::route('/{record}/edit'),
        ];
    }
}
