<?php

namespace App\Filament\Resources\PublicProjects\Tables;

use Filament\Actions\ForceDeleteBulkAction;

use Filament\Actions\RestoreBulkAction;

use Filament\Actions\ForceDeleteAction;

use Filament\Actions\RestoreAction;

use Filament\Tables\Filters\TrashedFilter;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use App\Models\PublicProject;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class PublicProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('Ảnh')
                    ->state(fn (PublicProject $p) => $p->metadata_json['cover_image'] ?? ($p->metadata_json['image'] ?? null))
                    ->height(40)
                    ->extraImgAttributes(['style' => 'border-radius:6px;object-fit:cover'])
                    ->defaultImageUrl(null),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('developer.name')
                    ->label('Chủ đầu tư')
                    ->placeholder('—')
                    ->description(fn (PublicProject $p) => $p->developer_id ? null : $p->developer_name)
                    ->searchable(['developer_name']),
                TextColumn::make('address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ward')
                    ->label('Địa điểm')
                    ->placeholder('—')
                    ->searchable(['ward', 'district', 'province'])
                    ->formatStateUsing(fn (PublicProject $p) => collect([$p->ward, $p->district])->filter()->implode(' · ') ?: ($p->province ?: '—'))
                    ->description(fn (PublicProject $p) => $p->province),
                TextColumn::make('province_new')
                    ->label('Tỉnh mới (2025)')
                    ->placeholder('—')
                    ->state(fn (PublicProject $p) => $p->metadata_json['address_new']['province_new'] ?? null)
                    ->badge()
                    ->color(fn (PublicProject $p) => match ($p->metadata_json['address_new_confidence'] ?? null) {
                        'high' => 'success', 'medium' => 'warning', default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('latitude')
                    ->label('Toạ độ')
                    ->placeholder('—')
                    ->formatStateUsing(function (PublicProject $p): string|HtmlString {
                        if ($p->latitude === null || $p->longitude === null) {
                            return '—';
                        }
                        $url = 'https://www.google.com/maps?q='.$p->latitude.','.$p->longitude;

                        return new HtmlString('<a href="'.e($url).'" target="_blank" rel="noopener" style="color:#2563eb;text-decoration:underline">📍 Maps</a>');
                    })
                    ->html()
                    ->toggleable(),
                TextColumn::make('project_type')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('blocks')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('apartments')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_public')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('province')->label('Tỉnh/TP')
                    ->options(fn () => PublicProject::query()->whereNotNull('province')->distinct()->orderBy('province')->pluck('province', 'province')->all())
                    ->searchable(),
                SelectFilter::make('district')->label('Quận/Huyện')
                    ->options(fn () => PublicProject::query()->whereNotNull('district')->distinct()->orderBy('district')->pluck('district', 'district')->all())
                    ->searchable(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                RestoreAction::make(),
                ForceDeleteAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
