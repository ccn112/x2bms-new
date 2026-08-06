<?php

namespace App\Filament\Sa\Resources\TranslationKeys\Pages;

use App\Filament\Sa\Resources\TranslationKeys\TranslationKeyResource;
use App\Services\Localization\PublishTranslationRelease;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListTranslationKeys extends ListRecords
{
    protected static string $resource = TranslationKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publishPack')
                ->label('Phát hành gói')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->modalHeading('Phát hành gói ngôn ngữ mới')
                ->modalDescription('Chụp toàn bộ bản dịch đang publish của namespace thành một phiên bản mới để app cư dân tải về. Bản đã phát hành là bất biến.')
                ->schema([
                    Select::make('namespace')
                        ->label('Namespace')
                        ->required()
                        ->options(fn () => DB::table('translation_namespaces')
                            ->orderBy('code')
                            ->pluck('code', 'code')
                            ->all())
                        ->helperText('Phát hành cho cả vi-VN và en-US.'),
                    TextInput::make('version')
                        ->label('Phiên bản (bỏ trống = tự sinh theo thời gian)')
                        ->placeholder('rel-YYYYMMDD-HHMMSS')
                        ->maxLength(50),
                ])
                ->action(function (array $data): void {
                    $service = app(PublishTranslationRelease::class);
                    $namespace = $data['namespace'];
                    $version = $data['version'] ?: null;
                    $ok = [];
                    $failed = [];

                    foreach (['vi-VN', 'en-US'] as $locale) {
                        try {
                            $service->publish($namespace, $locale, $version);
                            $ok[] = $locale;
                        } catch (\Throwable $e) {
                            $failed[] = "{$locale}: {$e->getMessage()}";
                        }
                    }

                    if ($ok !== []) {
                        Notification::make()
                            ->title('Đã phát hành gói mới')
                            ->body("{$namespace} — ".implode(', ', $ok).'. App sẽ nhận khi kiểm tra cập nhật.')
                            ->success()
                            ->send();
                    }

                    if ($failed !== []) {
                        Notification::make()
                            ->title('Một số ngôn ngữ chưa phát hành được')
                            ->body(implode(' · ', $failed))
                            ->warning()
                            ->send();
                    }
                }),
        ];
    }
}
