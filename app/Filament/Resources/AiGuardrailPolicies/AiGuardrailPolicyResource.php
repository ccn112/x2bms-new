<?php

namespace App\Filament\Resources\AiGuardrailPolicies;

use App\Filament\Resources\AiGuardrailPolicies\Pages\CreateAiGuardrailPolicy;
use App\Filament\Resources\AiGuardrailPolicies\Pages\EditAiGuardrailPolicy;
use App\Filament\Resources\AiGuardrailPolicies\Pages\ListAiGuardrailPolicies;
use App\Filament\Resources\AiGuardrailPolicies\Schemas\AiGuardrailPolicyForm;
use App\Filament\Resources\AiGuardrailPolicies\Tables\AiGuardrailPoliciesTable;
use App\Models\AiGuardrailPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AiGuardrailPolicyResource extends Resource
{
    protected static ?string $model = AiGuardrailPolicy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AiGuardrailPolicyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiGuardrailPoliciesTable::configure($table);
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
            'index' => ListAiGuardrailPolicies::route('/'),
            'create' => CreateAiGuardrailPolicy::route('/create'),
            'edit' => EditAiGuardrailPolicy::route('/{record}/edit'),
        ];
    }
}
