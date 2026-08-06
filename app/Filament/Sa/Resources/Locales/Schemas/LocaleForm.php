<?php

namespace App\Filament\Sa\Resources\Locales\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LocaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Mã locale (BCP-47)')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('name')
                    ->label('Tên (tiếng Anh)')
                    ->required()
                    ->maxLength(100),
                TextInput::make('native_name')
                    ->label('Tên bản địa')
                    ->required()
                    ->maxLength(100),
                Toggle::make('enabled')
                    ->label('Bật ngôn ngữ')
                    ->helperText('Chỉ ngôn ngữ được bật mới hiển thị cho người dùng.'),
                Toggle::make('is_default')
                    ->label('Đặt làm mặc định')
                    ->helperText('Chỉ một ngôn ngữ được làm mặc định; bật ở đây sẽ tự tắt ở các ngôn ngữ khác.'),
                TextInput::make('sort_order')
                    ->label('Thứ tự sắp xếp')
                    ->numeric()
                    ->default(100),
            ]);
    }
}
