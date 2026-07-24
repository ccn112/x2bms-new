<?php

namespace App\Filament\Resources\PaymentChannels\Schemas;

use App\Models\Project;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Form cổng thanh toán. Nhóm "Cấu hình VietQR" hiện động khi channel = vietqr;
 * VNPay/MoMo chỉ hiện ghi chú (khoá bí mật ở ENV). Các field VietQR map vào JSON
 * `config` bằng dot notation (config.bank_bin ...) nhờ cast config => array.
 */
class PaymentChannelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Phạm vi áp dụng')
                    ->columns(2)
                    ->schema([
                        Select::make('tenant_id')
                            ->label('Đơn vị quản lý')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Select::make('project_id')
                            ->label('Dự án')
                            ->placeholder('Tất cả dự án')
                            ->helperText('Bỏ trống = áp dụng cho tất cả dự án của đơn vị.')
                            ->options(fn (Get $get): array => filled($get('tenant_id'))
                                ? Project::query()
                                    ->where('tenant_id', $get('tenant_id'))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all()
                                : [])
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]),

                Section::make('Cổng thanh toán')
                    ->columns(2)
                    ->schema([
                        Select::make('channel')
                            ->label('Loại cổng')
                            ->options([
                                'vietqr' => 'VietQR',
                                'vnpay' => 'VNPay',
                                'momo' => 'MoMo',
                            ])
                            ->required()
                            ->live(),
                        TextInput::make('display_name')
                            ->label('Tên hiển thị')
                            ->maxLength(255),
                        Toggle::make('is_enabled')
                            ->label('Đang bật')
                            ->default(true)
                            ->inline(false),
                        TextInput::make('sort')
                            ->label('Thứ tự')
                            ->numeric()
                            ->default(0),
                    ]),

                Section::make('Cấu hình VietQR')
                    ->description('Tài khoản nhận tiền hiển thị trên mã VietQR.')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('channel') === 'vietqr')
                    ->schema([
                        TextInput::make('config.bank_bin')
                            ->label('Mã BIN ngân hàng')
                            ->helperText('Mã BIN 6 số theo chuẩn Napas (VD: 970436 - Vietcombank).')
                            ->maxLength(20),
                        TextInput::make('config.bank_code')
                            ->label('Mã ngân hàng')
                            ->helperText('VD: VCB, TCB, MB...')
                            ->maxLength(50),
                        TextInput::make('config.account_no')
                            ->label('Số tài khoản')
                            ->maxLength(50),
                        TextInput::make('config.account_name')
                            ->label('Tên chủ tài khoản')
                            ->maxLength(255),
                    ]),

                Section::make('Cấu hình cổng')
                    ->visible(fn (Get $get): bool => in_array($get('channel'), ['vnpay', 'momo'], true))
                    ->schema([
                        Placeholder::make('secret_note')
                            ->label('Ghi chú')
                            ->content('Khoá bí mật (secret key / access key) của VNPay/MoMo được cấu hình ở ENV, KHÔNG nhập tại đây. Chỉ chọn môi trường bên dưới.'),
                        Select::make('config.env')
                            ->label('Môi trường')
                            ->options([
                                'sandbox' => 'Sandbox (thử nghiệm)',
                                'production' => 'Production (chính thức)',
                            ])
                            ->default('sandbox'),
                    ]),
            ]);
    }
}
