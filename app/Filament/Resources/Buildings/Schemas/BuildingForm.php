<?php

namespace App\Filament\Resources\Buildings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * WEB-FORM-01-03 — Tạo tòa / block / tầng (building portion).
 * Sections: thông tin tòa, quy mô.
 */
class BuildingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin tòa nhà')
                    ->columns(2)
                    ->schema([
                        Select::make('tenant_id')
                            ->label('Đơn vị quản lý')
                            ->relationship('tenant', 'name')
                            ->required(),
                        Select::make('project_id')
                            ->label('Dự án')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('block_id')
                            ->label('Phân khu / Block')
                            ->relationship('block', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('code')->label('Mã tòa')->required(),
                        TextInput::make('name')->label('Tên tòa')->required(),
                        Select::make('type')
                            ->label('Loại tòa')
                            ->options([
                                'residential' => 'Căn hộ',
                                'office' => 'Văn phòng',
                                'mixed' => 'Hỗn hợp',
                            ])
                            ->default('residential')
                            ->required(),
                        Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'active' => 'Hoạt động',
                                'inactive' => 'Ngừng',
                                'construction' => 'Đang xây',
                            ])
                            ->default('active')
                            ->required(),
                        TextInput::make('address')->label('Địa chỉ')->columnSpanFull(),
                    ]),

                Section::make('Quy mô')
                    ->columns(2)
                    ->schema([
                        TextInput::make('apartment_count')->label('Số căn hộ')->numeric()->default(0),
                        TextInput::make('floor_count')->label('Số tầng')->numeric()->default(0),
                        TextInput::make('basement_count')->label('Số tầng hầm')->numeric()->default(0),
                        TextInput::make('elevator_count')->label('Số thang máy')->numeric()->default(0),
                        DatePicker::make('handover_date')->label('Ngày bàn giao'),
                        Textarea::make('note')->label('Ghi chú')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }
}
