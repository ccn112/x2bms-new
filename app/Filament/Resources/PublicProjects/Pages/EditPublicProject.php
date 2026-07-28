<?php

namespace App\Filament\Resources\PublicProjects\Pages;

use App\Filament\Resources\PublicProjects\PublicProjectResource;
use App\Services\Projects\ProjectEnrichmentService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class EditPublicProject extends EditRecord
{
    protected static string $resource = PublicProjectResource::class;

    /** Ứng viên fetch 1 lần khi mở modal (giữ qua vòng đời modal). */
    public array $enrichImages = [];

    public array $enrichInfos = [];

    protected function getHeaderActions(): array
    {
        return [
            $this->enrichSearchAction(),
            DeleteAction::make(),
        ];
    }

    protected function enrichSearchAction(): Action
    {
        return Action::make('enrichSearch')
            ->label('Tìm ảnh & thông tin')
            ->icon('heroicon-o-magnifying-glass-circle')
            ->color('info')
            ->visible(fn (): bool => (bool) auth()->user()?->isPlatformAdmin())
            ->modalHeading('Tìm ảnh & thông tin chính thống')
            ->modalDescription(fn () => 'Provider: '.config('enrichment.provider', 'mock').'. Chọn ảnh bìa + ảnh gallery + thông tin, mỗi mục kèm nguồn. Ảnh chính thống sẽ thay ảnh batdongsan watermark.')
            ->modalSubmitActionLabel('Lưu lựa chọn')
            ->mountUsing(function (): void {
                $svc = app(ProjectEnrichmentService::class);
                $this->enrichImages = $svc->searchImages($this->record);
                $this->enrichInfos = $svc->searchInfo($this->record);
            })
            ->schema(fn (): array => $this->enrichSchema())
            ->action(function (array $data): void {
                $svc = app(ProjectEnrichmentService::class);
                $infos = [];
                foreach ((array) ($data['infos'] ?? []) as $idx) {
                    if (isset($this->enrichInfos[$idx])) {
                        $infos[] = $this->enrichInfos[$idx];
                    }
                }
                $svc->applySelection(
                    $this->record,
                    (array) ($data['images'] ?? []),
                    $data['cover'] ?? null,
                    $infos,
                    (string) config('enrichment.provider', 'mock'),
                );
                $this->refreshFormData(['description', 'metadata_json']);
                Notification::make()
                    ->title('Đã lưu ảnh & thông tin chính thống')
                    ->body(count((array) ($data['images'] ?? [])).' ảnh, '.count($infos).' thông tin.')
                    ->success()->send();
            });
    }

    /** @return array<\Filament\Schemas\Components\Component> */
    protected function enrichSchema(): array
    {
        $imgOpts = [];
        $imgDesc = [];
        foreach ($this->enrichImages as $i => $c) {
            $imgOpts[$c['url']] = '#'.($i + 1).' · '.($c['title'] ?: 'Ảnh');
            $imgDesc[$c['url']] = new HtmlString(
                '<img src="'.e($c['thumb']).'" style="height:64px;border-radius:6px;display:inline-block"/> '
                .'<a href="'.e($c['source_page']).'" target="_blank" rel="noopener" style="color:#2563eb">nguồn</a>'
            );
        }

        $infoOpts = [];
        $infoDesc = [];
        foreach ($this->enrichInfos as $i => $c) {
            $host = parse_url($c['source_url'] ?? '', PHP_URL_HOST) ?: 'nguồn';
            $infoOpts[$i] = Str::limit(($c['title'] ?: '').' — '.($c['snippet'] ?: ''), 120);
            $infoDesc[$i] = new HtmlString('<a href="'.e($c['source_url']).'" target="_blank" rel="noopener" style="color:#2563eb">'.e($host).'</a>');
        }

        return [
            Placeholder::make('enrich_preview')
                ->label('Ứng viên ảnh')
                ->content(function (): HtmlString {
                    if ($this->enrichImages === []) {
                        return new HtmlString('<span style="color:#9ca3af">Không có ứng viên ảnh.</span>');
                    }
                    $cells = '';
                    foreach ($this->enrichImages as $i => $c) {
                        $cells .= '<div style="text-align:center"><img src="'.e($c['thumb']).'" style="height:90px;border-radius:6px;border:1px solid #e5e7eb"/>'
                            .'<div style="font-size:11px">#'.($i + 1).'</div></div>';
                    }

                    return new HtmlString('<div style="display:flex;flex-wrap:wrap;gap:8px">'.$cells.'</div>');
                }),
            Select::make('cover')
                ->label('Ảnh bìa (official cover)')
                ->options($imgOpts)
                ->searchable(),
            CheckboxList::make('images')
                ->label('Ảnh gallery chính thống')
                ->options($imgOpts)
                ->descriptions($imgDesc),
            CheckboxList::make('infos')
                ->label('Thông tin (áp vào mô tả + lưu nguồn)')
                ->options($infoOpts)
                ->descriptions($infoDesc),
        ];
    }
}
