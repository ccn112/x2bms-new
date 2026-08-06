<?php

namespace App\Filament\Sa\Resources\TranslationKeys\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TranslationKeyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Khóa dịch')
                    ->description('Định danh khóa là nguồn sự thật của mã nguồn — không sửa qua màn này.')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('namespace_code')
                            ->label('Namespace')
                            ->content(fn ($record) => $record?->namespace?->code ?? '—'),
                        TextInput::make('key')
                            ->label('Khóa')
                            ->disabled()
                            ->dehydrated(false),
                        Textarea::make('description')
                            ->label('Mô tả (ngữ cảnh cho người dịch)')
                            ->rows(2)
                            ->columnSpanFull(),
                        Toggle::make('is_critical')
                            ->label('Khóa hệ thống (critical)')
                            ->disabled(fn ($record) => (bool) $record?->is_critical)
                            ->helperText('Khóa hệ thống được bảo vệ, không thể sửa cờ này qua màn.'),
                        Toggle::make('allow_tenant_override')
                            ->label('Cho phép tenant ghi đè')
                            ->disabled(fn ($record) => (bool) $record?->is_critical),
                    ]),
                Section::make('Giá trị bản dịch (product scope)')
                    ->description('Lưu vào translation_values (status=published). Lưu ý: phải PHÁT HÀNH gói mới thì app cư dân mới nhận được thay đổi.')
                    ->schema([
                        Textarea::make('value_vi')
                            ->label('Tiếng Việt (vi-VN)')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('value_en')
                            ->label('English (en-US)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
