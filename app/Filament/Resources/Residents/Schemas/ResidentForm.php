<?php

namespace App\Filament\Resources\Residents\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * WEB-FORM-02-01 — Thêm cư dân.
 * Sections mirror the approved design: thông tin cá nhân, liên hệ, vai trò,
 * KYC & xác thực, thông tin bổ sung, tài liệu đính kèm.
 */
class ResidentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin cá nhân')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('avatar_path')
                            ->label('Ảnh đại diện')
                            ->avatar()
                            ->image()
                            ->disk('public')
                            ->directory('residents/avatars')
                            ->columnSpanFull(),
                        TextInput::make('full_name')->label('Họ tên')->required()->live(onBlur: true),
                        Select::make('gender')
                            ->label('Giới tính')
                            ->options(['Nam' => 'Nam', 'Nữ' => 'Nữ', 'Khác' => 'Khác'])
                            ->required()->live(),
                        DatePicker::make('dob')->label('Ngày sinh')->required()->live(),
                        Select::make('nationality')
                            ->label('Quốc tịch')
                            ->options(['Việt Nam' => 'Việt Nam', 'Khác' => 'Khác'])
                            ->default('Việt Nam')->required()->live(),
                        TextInput::make('id_no')->label('CCCD / CMND')->required()->live(onBlur: true),
                        DatePicker::make('id_issued_date')->label('Ngày cấp')->required(),
                        TextInput::make('id_issued_place')->label('Nơi cấp')->required(),
                    ]),

                Section::make('Thông tin liên hệ')
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')->label('SĐT đăng nhập')->tel()->required()->live(onBlur: true),
                        TextInput::make('email')->label('Email đăng nhập')->email()->required(),
                        TextInput::make('contact_phone')->label('SĐT liên hệ')->tel(),
                        TextInput::make('contact_email')->label('Email liên hệ')->email(),
                        Textarea::make('contact_address')
                            ->label('Địa chỉ thường trú')
                            ->maxLength(255)->required()->live(onBlur: true)->columnSpanFull(),
                    ]),

                Section::make('Vai trò cư dân')
                    ->columns(2)
                    ->schema([
                        Radio::make('requested_role')
                            ->label('Vai trò dự kiến')
                            ->options(['owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', 'member' => 'Thành viên hộ'])
                            ->default('owner')->inline()->required()->live()->columnSpanFull(),
                        Select::make('profile_status')
                            ->label('Trạng thái hồ sơ')
                            ->options([
                                'cho_bo_sung' => 'Chờ bổ sung',
                                'cho_duyet' => 'Chờ duyệt',
                                'hoat_dong' => 'Hoạt động',
                                'tu_choi' => 'Từ chối',
                            ])->default('cho_bo_sung')->required(),
                        Select::make('source')
                            ->label('Nguồn tạo hồ sơ')
                            ->options([
                                'bql_manual' => 'BQL thêm thủ công',
                                'app_self' => 'Cư dân tự đăng ký',
                                'import' => 'Import dữ liệu',
                            ])->default('bql_manual')->required(),
                    ]),

                Section::make('KYC & xác thực')
                    ->columns(2)
                    ->schema([
                        Select::make('kyc_status')
                            ->label('Trạng thái KYC')
                            ->options([
                                'unverified' => 'Chưa xác thực',
                                'pending' => 'Đang xác thực',
                                'verified' => 'Đã xác thực',
                                'rejected' => 'Từ chối',
                            ])->default('unverified')->required(),
                        Select::make('face_match_status')
                            ->label('Đối chiếu khuôn mặt')
                            ->options([
                                'not_checked' => 'Chưa đối chiếu',
                                'matched' => 'Khớp',
                                'mismatch' => 'Không khớp',
                            ])->default('not_checked'),
                        // KYC/CCCD is PII — stored on the PRIVATE disk (storage/app/private), never
                        // a public URL. Served only through the signed, authorized media route.
                        FileUpload::make('id_front_path')->label('Ảnh giấy tờ (mặt trước)')
                            ->image()->disk('local')->visibility('private')->directory('residents/kyc')
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])->maxSize(5120),
                        FileUpload::make('id_back_path')->label('Ảnh giấy tờ (mặt sau)')
                            ->image()->disk('local')->visibility('private')->directory('residents/kyc')
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])->maxSize(5120),
                        FileUpload::make('portrait_path')->label('Ảnh chân dung')
                            ->image()->disk('local')->visibility('private')->directory('residents/kyc')
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])->maxSize(5120),
                    ]),

                Section::make('Thông tin bổ sung')
                    ->columns(2)
                    ->schema([
                        TextInput::make('occupation')->label('Nghề nghiệp'),
                        Select::make('relationship_to_head')
                            ->label('Quan hệ với chủ hộ')
                            ->options([
                                'self' => 'Bản thân', 'spouse' => 'Vợ/Chồng', 'child' => 'Con',
                                'parent' => 'Cha/Mẹ', 'sibling' => 'Anh/Chị/Em', 'other' => 'Khác',
                            ]),
                        TextInput::make('vehicle_plate')->label('Biển số xe'),
                        Textarea::make('internal_note')->label('Ghi chú nội bộ')->maxLength(500)->columnSpanFull(),
                    ]),

                Section::make('Tài liệu đính kèm')
                    ->schema([
                        FileUpload::make('documents')
                            ->label('Hợp đồng / Sổ hồng / KT3 / Giấy ủy quyền')
                            ->multiple()
                            // Legal documents contain PII → private disk, served via signed media route.
                            ->disk('local')
                            ->visibility('private')
                            ->directory('residents/docs')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(10240),
                    ]),
            ]);
    }
}
