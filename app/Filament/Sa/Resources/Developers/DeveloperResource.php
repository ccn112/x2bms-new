<?php

namespace App\Filament\Sa\Resources\Developers;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Sa\Resources\Developers\Pages\CreateDeveloper;
use App\Filament\Sa\Resources\Developers\Pages\EditDeveloper;
use App\Filament\Sa\Resources\Developers\Pages\ListDevelopers;
use App\Filament\Sa\Resources\Developers\RelationManagers\PublicProjectsRelationManager;
use App\Filament\Sa\Resources\Developers\Schemas\DeveloperForm;
use App\Filament\Sa\Resources\Developers\Tables\DevelopersTable;
use App\Models\Developer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Chủ đầu tư — entity dùng chung cho thư viện dự án public (panel /sa). */
class DeveloperResource extends Resource
{
    use PlatformScreen;

    protected static ?string $model = Developer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|UnitEnum|null $navigationGroup = 'Dự án';

    protected static ?string $navigationLabel = 'Chủ đầu tư';

    protected static ?string $modelLabel = 'chủ đầu tư';

    protected static ?string $pluralModelLabel = 'chủ đầu tư';

    protected static ?int $navigationSort = 32;

    public static function form(Schema $schema): Schema
    {
        return DeveloperForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DevelopersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PublicProjectsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDevelopers::route('/'),
            'create' => CreateDeveloper::route('/create'),
            'edit' => EditDeveloper::route('/{record}/edit'),
        ];
    }
}
