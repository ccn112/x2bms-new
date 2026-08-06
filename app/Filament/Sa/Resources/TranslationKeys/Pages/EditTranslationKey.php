<?php

namespace App\Filament\Sa\Resources\TranslationKeys\Pages;

use App\Filament\Sa\Resources\TranslationKeys\TranslationKeyResource;
use App\Services\Localization\TranslationValueWriter;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditTranslationKey extends EditRecord
{
    protected static string $resource = TranslationKeyResource::class;

    /** Load the current published product-scope values into the virtual value fields. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['value_vi'] = $this->currentValue('vi-VN');
        $data['value_en'] = $this->currentValue('en-US');

        return $data;
    }

    /**
     * Persist editable key attributes, then hand the value edits to the domain service
     * (keeps the Resource thin + guarantees the audit trail).
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $valueVi = $data['value_vi'] ?? null;
        $valueEn = $data['value_en'] ?? null;
        unset($data['value_vi'], $data['value_en']);

        // Remaining $data holds only key metadata (disabled critical toggles are not dehydrated).
        $record->update($data);

        $writer = app(TranslationValueWriter::class);
        $writer->writeProductValue($record->id, 'vi-VN', $valueVi);
        $writer->writeProductValue($record->id, 'en-US', $valueEn);

        return $record;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Đã lưu bản dịch')
            ->body('Nhớ PHÁT HÀNH gói mới ở màn "Bản phát hành" để app cư dân nhận thay đổi.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('goToReleases')
                ->label('Tới màn phát hành')
                ->icon('heroicon-o-rocket-launch')
                ->color('gray')
                ->url(fn () => \App\Filament\Sa\Resources\TranslationReleases\TranslationReleaseResource::getUrl('index')),
        ];
    }

    private function currentValue(string $locale): ?string
    {
        return DB::table('translation_values')
            ->where('translation_key_id', $this->record->id)
            ->where('locale', $locale)
            ->where('scope_type', 'product')
            ->where('scope_id', '')
            ->where('status', 'published')
            ->value('value');
    }
}
