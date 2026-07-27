<?php

namespace App\Filament\Sa\Resources\DocPages\RelationManagers;

use App\Models\DocPageRevision;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Lịch sử version của trang tài liệu (chỉ đọc) + action Khôi phục.
 * Khôi phục = ghi title/body của revision cũ trở lại trang → observer tự tạo
 * thêm 1 version mới (không xóa lịch sử).
 */
class RevisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'revisions';

    protected static ?string $title = 'Lịch sử version';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->defaultSort('version', 'desc')
            ->columns([
                TextColumn::make('version')
                    ->label('Version')
                    ->badge()
                    ->color('info'),
                TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->limit(50),
                TextColumn::make('note')
                    ->label('Ghi chú')
                    ->color('gray'),
                TextColumn::make('editor.name')
                    ->label('Người sửa')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Thời điểm')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Xem')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (DocPageRevision $record) => 'Version '.$record->version.' — '.$record->title)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng')
                    ->schema(fn (Schema $schema): Schema => $schema->components([
                        TextInput::make('title')->label('Tiêu đề')->disabled(),
                        Textarea::make('body')->label('Nội dung (Markdown)')->rows(20)->disabled(),
                    ]))
                    ->fillForm(fn (DocPageRevision $record) => [
                        'title' => $record->title,
                        'body' => $record->body,
                    ]),
                Action::make('restore')
                    ->label('Khôi phục')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Khôi phục version này?')
                    ->modalDescription(fn (DocPageRevision $record) => 'Nội dung của trang sẽ được đặt về version '.$record->version.'. Một version mới sẽ được tạo để ghi nhận thao tác này.')
                    ->action(function (DocPageRevision $record) {
                        $page = $this->getOwnerRecord();
                        $page->update([
                            'title' => $record->title,
                            'body' => $record->body,
                            'updated_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Đã khôi phục về version '.$record->version)
                            ->send();
                    }),
            ]);
    }
}
