<?php

namespace App\Filament\Resources\PublicProjects;

use App\Filament\Resources\PublicProjects\Pages\CreatePublicProject;
use App\Filament\Resources\PublicProjects\Pages\EditPublicProject;
use App\Filament\Resources\PublicProjects\Pages\ListPublicProjects;
use App\Filament\Resources\PublicProjects\Schemas\PublicProjectForm;
use App\Filament\Resources\PublicProjects\Tables\PublicProjectsTable;
use App\Models\PublicProject;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PublicProjectResource extends Resource
{
    protected static ?string $model = PublicProject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PublicProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PublicProjectsTable::configure($table);
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
            'index' => ListPublicProjects::route('/'),
            'create' => CreatePublicProject::route('/create'),
            'edit' => EditPublicProject::route('/{record}/edit'),
        ];
    }
}
