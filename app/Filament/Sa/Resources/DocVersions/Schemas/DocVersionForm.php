<?php

namespace App\Filament\Sa\Resources\DocVersions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DocVersionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->label('Nhãn (vd v1.0)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('v1.0'),
                TextInput::make('name')
                    ->label('Tên đợt')
                    ->placeholder('Ra mắt / Nâng cấp lớn …'),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'planned' => 'Dự kiến',
                        'in_progress' => 'Đang làm',
                        'released' => 'Đã phát hành',
                    ])
                    ->default('planned')
                    ->native(false)
                    ->required(),
                DatePicker::make('released_at')
                    ->label('Ngày phát hành')
                    ->displayFormat('d/m/Y'),
                TextInput::make('sort')
                    ->label('Thứ tự')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_current')
                    ->label('Là phiên bản hiện hành (mặc định hiển thị)')
                    ->helperText('Chỉ 1 phiên bản được đặt hiện hành — bật cái này sẽ tự tắt các cái khác.')
                    ->default(false),
                Textarea::make('summary')
                    ->label('Tóm tắt')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
