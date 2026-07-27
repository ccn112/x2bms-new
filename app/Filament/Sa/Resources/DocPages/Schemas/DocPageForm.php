<?php

namespace App\Filament\Sa\Resources\DocPages\Schemas;

use App\Models\DocPage;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DocPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vị trí')
                    ->columns(2)
                    ->schema([
                        Select::make('space_id')
                            ->label('Không gian')
                            ->relationship('space', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Select::make('parent_id')
                            ->label('Trang cha (tùy chọn)')
                            ->native(false)
                            ->searchable()
                            ->placeholder('— Không (trang gốc) —')
                            ->options(function (callable $get, ?DocPage $record) {
                                $spaceId = $get('space_id');
                                if (! $spaceId) {
                                    return [];
                                }

                                return DocPage::query()
                                    ->where('space_id', $spaceId)
                                    ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                                    ->orderBy('title')
                                    ->pluck('title', 'id');
                            }),
                        TextInput::make('sort')
                            ->label('Thứ tự')
                            ->numeric()
                            ->default(0),
                        Select::make('status')
                            ->label('Trạng thái')
                            ->options(['draft' => 'Nháp', 'published' => 'Xuất bản'])
                            ->default('draft')
                            ->native(false)
                            ->required(),
                    ]),
                Section::make('Nội dung')
                    ->schema([
                        TextInput::make('title')
                            ->label('Tiêu đề')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, callable $set, callable $get) {
                                if (blank($get('slug')) && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Slug (dùng trong URL)')
                            ->required()
                            ->helperText('Duy nhất trong cùng không gian + trang cha.'),
                        MarkdownEditor::make('body')
                            ->label('Nội dung (Markdown)')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('docs/attachments')
                            ->fileAttachmentsVisibility('public')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
