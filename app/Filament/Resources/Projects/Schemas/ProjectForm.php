<?php

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * WEB-FORM-01-02 — Tạo dự án / khu đô thị.
 * Sections: thông tin dự án, địa chỉ & vị trí, quy mô & pháp lý, liên hệ.
 */
class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin dự án')
                    ->columns(2)
                    ->schema([
                        Select::make('tenant_id')
                            ->label('Đơn vị quản lý')
                            ->relationship('tenant', 'name')
                            ->required(),
                        Select::make('company_id')
                            ->label('Công ty vận hành')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('code')->label('Mã dự án')->required(),
                        TextInput::make('name')->label('Tên dự án')->required(),
                        Select::make('type')
                            ->label('Loại hình')
                            ->options([
                                'apartment' => 'Chung cư',
                                'urban_area' => 'Khu đô thị',
                                'complex' => 'Phức hợp',
                                'office' => 'Văn phòng',
                            ])
                            ->default('apartment')
                            ->required(),
                        Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'planning' => 'Quy hoạch',
                                'construction' => 'Đang xây',
                                'active' => 'Hoạt động',
                                'inactive' => 'Ngừng',
                            ])
                            ->default('active')
                            ->required(),
                    ]),

                Section::make('Địa chỉ & vị trí')
                    ->columns(2)
                    ->schema([
                        TextInput::make('address')->label('Địa chỉ')->columnSpanFull(),
                        TextInput::make('ward')->label('Phường / Xã'),
                        TextInput::make('district')->label('Quận / Huyện'),
                        TextInput::make('city')->label('Tỉnh / Thành phố'),
                        TextInput::make('latitude')->label('Vĩ độ')->numeric(),
                        TextInput::make('longitude')->label('Kinh độ')->numeric(),
                    ]),

                Section::make('Quy mô & pháp lý')
                    ->columns(2)
                    ->schema([
                        TextInput::make('land_area_sqm')->label('Diện tích đất (m²)')->numeric(),
                        TextInput::make('building_count')->label('Số tòa')->numeric()->default(0),
                        TextInput::make('apartment_count')->label('Số căn hộ')->numeric()->default(0),
                        TextInput::make('investor')->label('Chủ đầu tư'),
                        TextInput::make('legal_no')->label('Số pháp lý'),
                        DatePicker::make('handover_date')->label('Ngày bàn giao'),
                    ]),

                Section::make('Liên hệ')
                    ->columns(2)
                    ->schema([
                        TextInput::make('contact_person')->label('Đầu mối liên hệ'),
                        TextInput::make('contact_phone')->label('SĐT liên hệ')->tel(),
                        Textarea::make('description')->label('Mô tả')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }
}
