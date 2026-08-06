<?php

namespace App\Filament\Sa\Resources\Locales;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Sa\Resources\Locales\Pages\EditLocale;
use App\Filament\Sa\Resources\Locales\Pages\ListLocales;
use App\Filament\Sa\Resources\Locales\Schemas\LocaleForm;
use App\Filament\Sa\Resources\Locales\Tables\LocalesTable;
use App\Models\Locale;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Ngôn ngữ nền tảng (I18N) — danh mục locale, read-mostly (panel /sa). */
class LocaleResource extends Resource
{
    use PlatformScreen;

    protected static ?string $model = Locale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Trung tâm dịch';

    protected static ?string $navigationLabel = 'Ngôn ngữ';

    protected static ?string $modelLabel = 'ngôn ngữ';

    protected static ?string $pluralModelLabel = 'ngôn ngữ';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return LocaleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LocalesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocales::route('/'),
            'edit' => EditLocale::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        // Locales are seeded master data; adding a new one is deferred (BCP-47 + fallback wiring).
        return false;
    }
}
