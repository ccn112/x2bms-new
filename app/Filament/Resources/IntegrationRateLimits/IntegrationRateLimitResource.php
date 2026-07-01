<?php

namespace App\Filament\Resources\IntegrationRateLimits;

use App\Filament\Resources\IntegrationRateLimits\Pages\CreateIntegrationRateLimit;
use App\Filament\Resources\IntegrationRateLimits\Pages\EditIntegrationRateLimit;
use App\Filament\Resources\IntegrationRateLimits\Pages\ListIntegrationRateLimits;
use App\Filament\Resources\IntegrationRateLimits\Schemas\IntegrationRateLimitForm;
use App\Filament\Resources\IntegrationRateLimits\Tables\IntegrationRateLimitsTable;
use App\Models\IntegrationRateLimit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntegrationRateLimitResource extends Resource
{
    protected static ?string $model = IntegrationRateLimit::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IntegrationRateLimitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntegrationRateLimitsTable::configure($table);
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
            'index' => ListIntegrationRateLimits::route('/'),
            'create' => CreateIntegrationRateLimit::route('/create'),
            'edit' => EditIntegrationRateLimit::route('/{record}/edit'),
        ];
    }
}
