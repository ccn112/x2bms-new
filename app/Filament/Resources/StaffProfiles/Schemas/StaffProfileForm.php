<?php

namespace App\Filament\Resources\StaffProfiles\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * WEB-FORM-03-01 — Tạo tài khoản nhân sự (hồ sơ nhân sự gắn 1:1 với user).
 */
class StaffProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tài khoản')
                    ->schema([
                        Select::make('user_id')
                            ->label('Tài khoản đăng nhập')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                Section::make('Hồ sơ nhân sự')
                    ->columns(2)
                    ->schema([
                        TextInput::make('employee_code')->label('Mã nhân viên'),
                        TextInput::make('position')->label('Chức danh'),
                        Select::make('department_id')
                            ->label('Phòng ban')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('phone')->label('Điện thoại')->tel(),
                        DatePicker::make('dob')->label('Ngày sinh'),
                        Select::make('gender')
                            ->label('Giới tính')
                            ->options(['Nam' => 'Nam', 'Nữ' => 'Nữ', 'Khác' => 'Khác']),
                        TextInput::make('id_no')->label('Số CCCD'),
                        DatePicker::make('hire_date')->label('Ngày vào làm'),
                        Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'active' => 'Đang làm',
                                'suspended' => 'Tạm ngừng',
                                'left' => 'Đã nghỉ',
                            ])
                            ->default('active')
                            ->required(),
                        Textarea::make('note')->label('Ghi chú')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }
}
