<?php

namespace App\Filament\Resources\CommunityPosts;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\CommunityPosts\Pages\CreateCommunityPost;
use App\Filament\Resources\CommunityPosts\Pages\EditCommunityPost;
use App\Filament\Resources\CommunityPosts\Pages\ListCommunityPosts;
use App\Filament\Resources\CommunityPosts\Schemas\CommunityPostForm;
use App\Filament\Resources\CommunityPosts\Tables\CommunityPostsTable;
use App\Models\CommunityPost;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CommunityPostResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = CommunityPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CommunityPostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommunityPostsTable::configure($table);
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
            'index' => ListCommunityPosts::route('/'),
            'create' => CreateCommunityPost::route('/create'),
            'edit' => EditCommunityPost::route('/{record}/edit'),
        ];
    }
}
