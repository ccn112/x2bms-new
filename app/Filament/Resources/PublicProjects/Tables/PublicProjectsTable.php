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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PublicProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
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
