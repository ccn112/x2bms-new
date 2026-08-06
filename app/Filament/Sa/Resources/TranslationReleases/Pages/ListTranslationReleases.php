<?php

namespace App\Filament\Sa\Resources\TranslationReleases\Pages;

use App\Filament\Sa\Resources\TranslationReleases\TranslationReleaseResource;
use App\Models\Locale;
use App\Models\TranslationNamespace;
use App\Services\Localization\PublishTranslationRelease;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTranslationReleases extends ListRecords
{
    protected static string $resource = TranslationReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')
                ->label('Phát hành gói mới')
                ->icon('heroicon-m-rocket-launch')
                ->color('primary')
                ->modalHeading('Phát hành gói dịch mới')
                ->modalDescription('Chụp ảnh các giá trị đang published (product scope) của namespace + locale, tính checksum và tạo bản phát hành mới (bất biến).')
                ->schema([
                    Select::make('namespace_code')
                        ->label('Namespace')
                        ->options(fn () => TranslationNamespace::query()->orderBy('code')->pluck('code', 'code')->all())
                        ->required()
                        ->searchable(),
                    Select::make('locale')
                        ->label('Locale')
                        ->options(fn () => [
                            '__all__' => 'Tất cả ngôn ngữ đang bật',
                        ] + Locale::query()->where('enabled', true)->orderBy('sort_order')->pluck('code', 'code')->all())
                        ->default('__all__')
                        ->required(),
                    TextInput::make('version')
                        ->label('Phiên bản (để trống = tự động rel-{ngày-giờ})')
                        ->placeholder('rel-20260806-153000')
                        ->maxLength(50),
                ])
                ->action(function (array $data): void {
                    $service = app(PublishTranslationRelease::class);
                    $namespace = $data['namespace_code'];
                    $version = filled($data['version'] ?? null) ? $data['version'] : null;

                    $locales = $data['locale'] === '__all__'
                        ? Locale::query()->where('enabled', true)->orderBy('sort_order')->pluck('code')->all()
                        : [$data['locale']];

                    $done = [];
                    $failed = [];
                    foreach ($locales as $locale) {
                        try {
                            $service->publish($namespace, $locale, $version);
                            $done[] = $locale;
                        } catch (\Throwable $e) {
                            $failed[] = $locale.' ('.$e->getMessage().')';
                        }
                    }

                    if ($done !== []) {
                        Notification::make()->success()
                            ->title('Đã phát hành gói mới')
                            ->body('Locale: '.implode(', ', $done).'. Cache pack đã được làm mới.')
                            ->send();
                    }
                    if ($failed !== []) {
                        Notification::make()->warning()
                            ->title('Một số locale không phát hành được')
                            ->body(implode('; ', $failed))
                            ->send();
                    }
                }),
        ];
    }
}
