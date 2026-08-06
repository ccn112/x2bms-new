<?php

namespace App\Filament\Sa\Resources\TranslationReleases;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Sa\Resources\TranslationReleases\Pages\ListTranslationReleases;
use App\Filament\Sa\Resources\TranslationReleases\Tables\TranslationReleasesTable;
use App\Models\TranslationRelease;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use UnitEnum;

/** Bản phát hành — danh sách gói dịch bất biến + phát hành/khôi phục (panel /sa). */
class TranslationReleaseResource extends Resource
{
    use PlatformScreen;

    protected static ?string $model = TranslationRelease::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rocket-launch';

    protected static string|UnitEnum|null $navigationGroup = 'Trung tâm dịch';

    protected static ?string $navigationLabel = 'Bản phát hành';

    protected static ?string $modelLabel = 'bản phát hành';

    protected static ?string $pluralModelLabel = 'bản phát hành';

    protected static ?int $navigationSort = 30;

    public static function table(Table $table): Table
    {
        return TranslationReleasesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTranslationReleases::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        // Releases are created via the "Phát hành gói mới" header action (immutable pipeline).
        return false;
    }
}
