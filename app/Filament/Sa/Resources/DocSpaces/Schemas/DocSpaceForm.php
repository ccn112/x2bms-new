<?php

namespace App\Filament\Sa\Resources\DocSpaces\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DocSpaceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Tiêu đề')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set, callable $get) {
                        if (blank($get('key')) && filled($state)) {
                            $set('key', Str::slug($state));
                        }
                    }),
                TextInput::make('key')
                    ->label('Khóa (slug URL)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('Dùng trong URL /docs/{key}. Chỉ chữ thường, số, dấu gạch ngang.'),
                Textarea::make('description')
                    ->label('Mô tả')
                    ->rows(2)
                    ->columnSpanFull(),
                Select::make('audience')
                    ->label('Đối tượng đọc')
                    ->options([
                        'dev' => 'Nhà phát triển (dev)',
                        'ops' => 'Vận hành/tích hợp (ops)',
                        'bql' => 'Ban Quản lý (bql)',
                        'hq' => 'Cổng công ty (hq)',
                        'sa' => 'SuperAdmin (sa)',
                        'resident' => 'Cư dân (resident)',
                    ])
                    ->required()
                    ->native(false),
                TextInput::make('icon')
                    ->label('Icon (heroicon, tùy chọn)')
                    ->placeholder('heroicon-o-book-open'),
                TextInput::make('sort')
                    ->label('Thứ tự')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_published')
                    ->label('Đã xuất bản')
                    ->default(true),
                Toggle::make('is_public')
                    ->label('Công khai (cho xem không cần đăng nhập)')
                    ->helperText('Bật = hiển thị trên site tài liệu công khai doc.x2.fino.vn cho khách chưa đăng nhập.')
                    ->default(false),
            ]);
    }
}
