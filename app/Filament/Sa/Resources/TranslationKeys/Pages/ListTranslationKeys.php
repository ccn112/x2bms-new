<?php

namespace App\Filament\Sa\Resources\TranslationKeys\Pages;

use App\Filament\Sa\Resources\TranslationKeys\TranslationKeyResource;
use Filament\Resources\Pages\ListRecords;

class ListTranslationKeys extends ListRecords
{
    protected static string $resource = TranslationKeyResource::class;
}
