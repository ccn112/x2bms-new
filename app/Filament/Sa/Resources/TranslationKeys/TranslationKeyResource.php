<?php

namespace App\Filament\Sa\Resources\TranslationKeys;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Sa\Resources\TranslationKeys\Pages\EditTranslationKey;
use App\Filament\Sa\Resources\TranslationKeys\Pages\ListTranslationKeys;
use App\Filament\Sa\Resources\TranslationKeys\Schemas\TranslationKeyForm;
use App\Filament\Sa\Resources\TranslationKeys\Tables\TranslationKeysTable;
use App\Models\TranslationKey;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Namespace & khóa dịch — màn sửa bản dịch product-scope (vi-VN / en-US) (panel /sa). */
class TranslationKeyResource extends Resource
{
    use PlatformScreen;

    protected static ?string $model = TranslationKey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Trung tâm dịch';

    protected static ?string $navigationLabel = 'Namespace & khóa dịch';

    protected static ?string $modelLabel = 'khóa dịch';

    protected static ?string $pluralModelLabel = 'khóa dịch';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return TranslationKeyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TranslationKeysTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTranslationKeys::route('/'),
            'edit' => EditTranslationKey::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        // Keys are defined by the codebase/seed (source of truth); adding keys via UI is deferred.
        return false;
    }
}
