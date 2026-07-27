<?php

namespace App\Filament\Sa\Resources\DocVersions\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Backlog hạng mục của một phiên bản sản phẩm.
 */
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Backlog phát triển';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category')
                ->label('Loại')
                ->options([
                    'feature' => 'Tính năng mới',
                    'improvement' => 'Cải tiến',
                    'fix' => 'Sửa lỗi',
                    'change' => 'Thay đổi',
                ])
                ->default('feature')
                ->native(false)
                ->required(),
            Select::make('status')
                ->label('Trạng thái')
                ->options([
                    'planned' => 'Dự kiến',
                    'in_progress' => 'Đang làm',
                    'done' => 'Hoàn thành',
                ])
                ->default('planned')
                ->native(false)
                ->required(),
            TextInput::make('title')
                ->label('Tiêu đề')
                ->required()
                ->columnSpanFull(),
            Textarea::make('detail')
                ->label('Chi tiết')
                ->rows(3)
                ->columnSpanFull(),
            Select::make('ref_page_id')
                ->label('Trang liên quan (tùy chọn)')
                ->relationship('refPage', 'title')
                ->searchable()
                ->preload(),
            TextInput::make('sort')
                ->label('Thứ tự')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('category')
                    ->label('Loại')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'feature' => 'Tính năng',
                        'improvement' => 'Cải tiến',
                        'fix' => 'Sửa lỗi',
                        default => 'Thay đổi',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'feature' => 'success',
                        'improvement' => 'info',
                        'fix' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'done' => 'Hoàn thành',
                        'in_progress' => 'Đang làm',
                        default => 'Dự kiến',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'done' => 'success',
                        'in_progress' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('refPage.title')
                    ->label('Trang liên quan')
                    ->placeholder('—')
                    ->limit(40),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Loại')
                    ->options([
                        'feature' => 'Tính năng mới',
                        'improvement' => 'Cải tiến',
                        'fix' => 'Sửa lỗi',
                        'change' => 'Thay đổi',
                    ]),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'planned' => 'Dự kiến',
                        'in_progress' => 'Đang làm',
                        'done' => 'Hoàn thành',
                    ]),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
