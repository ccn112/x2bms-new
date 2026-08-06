<?php

namespace App\Filament\Sa\Resources\Locales\Pages;

use App\Filament\Sa\Resources\Locales\LocaleResource;
use App\Models\Locale;
use Filament\Resources\Pages\EditRecord;

class EditLocale extends EditRecord
{
    protected static string $resource = LocaleResource::class;

    /**
     * Enforce the single-default invariant: promoting this locale to default demotes
     * every other locale. Kept here (not in the schema) so it holds on every save.
     */
    protected function afterSave(): void
    {
        if ($this->record->is_default) {
            Locale::query()
                ->where('code', '!=', $this->record->code)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }
    }
}
