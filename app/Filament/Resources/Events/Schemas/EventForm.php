<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('project_id')
                    ->relationship('project', 'name'),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('location'),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                TextInput::make('capacity')
                    ->numeric(),
                TextInput::make('registered_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                // Select chứ KHÔNG phải ô chữ tự do: ô tự do chính là đường mà
                // giá trị `published` (quy ước của bảng nội dung, không thuộc
                // vòng đời sự kiện) lọt vào cột này, khiến sự kiện không lên
                // được app cư dân. Nhãn tiếng Việt để BQL không phải đoán.
                Select::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'upcoming' => 'Sắp diễn ra (cư dân thấy)',
                        'ongoing' => 'Đang diễn ra (cư dân thấy)',
                        'finished' => 'Đã kết thúc',
                        'cancelled' => 'Đã huỷ',
                    ])
                    ->required()
                    ->native(false)
                    ->default('upcoming'),
            ]);
    }
}
