<?php

namespace App\Filament\Resources\CommunityPosts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CommunityPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('project_id')
                    ->numeric(),
                TextInput::make('community_group_id')
                    ->numeric(),
                TextInput::make('author_resident_id')
                    ->numeric(),
                TextInput::make('title'),
                Textarea::make('body')
                    ->columnSpanFull(),
                TextInput::make('like_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('comment_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('status')
                    ->required()
                    ->default('published'),
            ]);
    }
}
