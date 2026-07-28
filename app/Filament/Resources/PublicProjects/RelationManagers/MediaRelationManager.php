<?php

namespace App\Filament\Resources\PublicProjects\RelationManagers;

use App\Models\ProjectMedia;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/** Thư viện ảnh dự án — batdongsan (watermark) / official / manual, chọn ảnh bìa. */
class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Thư viện ảnh';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('media_type')->label('Loại')
                ->options(['image' => 'Ảnh', 'video' => 'Video', 'brochure' => 'Brochure', 'map' => 'Bản đồ', 'floor_plan' => 'Mặt bằng'])
                ->default('image')->required(),
            TextInput::make('title')->label('Tiêu đề'),
            FileUpload::make('file_url')->label('Tải ảnh lên (hoặc nhập URL bên dưới)')
                ->image()->disk('public')->directory('project-media')->visibility('public'),
            TextInput::make('file_url')->label('Hoặc URL ảnh')->url()
                ->helperText('Nếu đã tải file ở trên thì bỏ trống ô này.'),
            TextInput::make('sort_order')->label('Thứ tự')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('file_url')->label('Ảnh')->height(56)
                    ->extraImgAttributes(['style' => 'border-radius:6px;object-fit:cover']),
                IconColumn::make('is_cover')->label('Bìa')->boolean()
                    ->trueIcon('heroicon-s-star')->falseIcon('heroicon-o-star')
                    ->trueColor('warning')->alignCenter(),
                TextColumn::make('source')->label('Nguồn')->badge()
                    ->placeholder('—')
                    ->color(fn (?string $s) => match ($s) {
                        'official' => 'success', 'manual' => 'info', 'batdongsan' => 'gray', default => 'gray',
                    }),
                IconColumn::make('is_watermarked')->label('WM')->boolean()
                    ->trueIcon('heroicon-s-exclamation-triangle')->trueColor('warning')
                    ->falseIcon('heroicon-o-check-circle')->falseColor('success')->alignCenter(),
                TextColumn::make('media_type')->label('Loại')->badge()->color('gray'),
                TextColumn::make('title')->label('Tiêu đề')->placeholder('—')->limit(30),
                ToggleColumn::make('is_active')->label('Hiện'),
                TextColumn::make('sort_order')->label('Thứ tự')->alignCenter(),
            ])
            ->headerActions([
                CreateAction::make()->label('Thêm ảnh thủ công')
                    ->mutateDataUsing(function (array $data): array {
                        $data['source'] = 'manual';
                        $data['is_watermarked'] = false;
                        $data['is_active'] = $data['is_active'] ?? true;

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('setCover')->label('Đặt làm ảnh bìa')->icon('heroicon-o-star')->color('warning')
                    ->hidden(fn (ProjectMedia $m) => (bool) $m->is_cover)
                    ->action(function (ProjectMedia $m): void {
                        $m->publicProject->media()->where('is_cover', true)->update(['is_cover' => false]);
                        $m->update(['is_cover' => true]);
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
