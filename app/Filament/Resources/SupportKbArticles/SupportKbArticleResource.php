<?php

namespace App\Filament\Resources\SupportKbArticles;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\SupportKbArticles\Pages\CreateSupportKbArticle;
use App\Filament\Resources\SupportKbArticles\Pages\EditSupportKbArticle;
use App\Filament\Resources\SupportKbArticles\Pages\ListSupportKbArticles;
use App\Filament\Resources\SupportKbArticles\Schemas\SupportKbArticleForm;
use App\Filament\Resources\SupportKbArticles\Tables\SupportKbArticlesTable;
use App\Models\SupportKbArticle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupportKbArticleResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = SupportKbArticle::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SupportKbArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportKbArticlesTable::configure($table);
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
            'index' => ListSupportKbArticles::route('/'),
            'create' => CreateSupportKbArticle::route('/create'),
            'edit' => EditSupportKbArticle::route('/{record}/edit'),
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
