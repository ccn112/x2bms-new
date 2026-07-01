<?php

namespace App\Filament\Resources\CommunityPosts\Pages;

use App\Filament\Resources\CommunityPosts\CommunityPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommunityPost extends CreateRecord
{
    protected static string $resource = CommunityPostResource::class;
}
