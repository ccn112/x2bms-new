<?php

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * WEB-FORM-01-01 — Tạo công ty / tenant.
 * Sections mirror the approved form: thông tin chung, liên hệ & địa chỉ,
 * gói dịch vụ, branding.
 */
class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin chung')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Mã đơn vị')
                            ->required(),
                        TextInput::make('name')
                            ->label('Tên đầy đủ')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('short_name')
                            ->label('Tên viết tắt'),
                        TextInput::make('tax_code')
                            ->label('Mã số thuế'),
                    ]),

                Section::make('Liên hệ & địa chỉ')
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')->label('Điện thoại')->tel(),
                        TextInput::make('email')->label('Email')->email(),
                        TextInput::make('website')->label('Website')->url(),
                        TextInput::make('city')->label('Tỉnh / Thành phố'),
                        TextInput::make('address')->label('Địa chỉ')->columnSpanFull(),
                        TextInput::make('legal_representative')->label('Người đại diện pháp luật'),
                        TextInput::make('contact_person')->label('Đầu mối liên hệ'),
                        TextInput::make('contact_phone')->label('SĐT liên hệ')->tel(),
                    ]),

                Section::make('Gói dịch vụ & trạng thái')
                    ->columns(2)
                    ->schema([
                        Select::make('plan')
                            ->label('Gói dịch vụ')
                            ->options([
                                'trial' => 'Dùng thử',
                                'standard' => 'Tiêu chuẩn',
                                'enterprise' => 'Doanh nghiệp',
                            ])
                            ->default('standard')
                            ->required(),
                        Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'active' => 'Hoạt động',
                                'inactive' => 'Ngừng',
                                'trial' => 'Dùng thử',
                            ])
                            ->default('active')
                            ->required(),
                    ]),

                Section::make('Branding')
                    ->columns(2)
                    ->schema([
                        ColorPicker::make('primary_color')->label('Màu chủ đạo'),
                        ColorPicker::make('secondary_color')->label('Màu phụ'),
                        TextInput::make('logo_path')->label('Logo (đường dẫn)')->columnSpanFull(),
                        Textarea::make('note')->label('Ghi chú')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }
}
