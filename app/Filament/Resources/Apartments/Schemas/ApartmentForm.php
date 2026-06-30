<?php

namespace App\Filament\Resources\Apartments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * WEB-FORM-01-04 — Tạo căn hộ & trạng thái căn.
 * Sections: vị trí, thông tin căn, trạng thái & phí.
 */
class ApartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vị trí')
                    ->columns(2)
                    ->schema([
                        Select::make('tenant_id')
                            ->label('Đơn vị quản lý')
                            ->relationship('tenant', 'name')
                            ->required(),
                        Select::make('building_id')
                            ->label('Tòa nhà')
                            ->relationship('building', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('floor_id')
                            ->label('Tầng')
                            ->relationship('floor', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('code')->label('Mã căn')->required(),
                    ]),

                Section::make('Thông tin căn')
                    ->columns(2)
                    ->schema([
                        TextInput::make('area_sqm')->label('Diện tích (m²)')->numeric(),
                        TextInput::make('type')->label('Loại căn'), // e.g. 2PN - 2WC
                        TextInput::make('bedroom_count')->label('Số phòng ngủ')->numeric(),
                        TextInput::make('bathroom_count')->label('Số WC')->numeric(),
                        TextInput::make('direction')->label('Hướng căn'),
                        TextInput::make('ownership_type')->label('Hình thức sở hữu'),
                    ]),

                Section::make('Trạng thái & phí')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Trạng thái căn')
                            ->options([
                                'occupied' => 'Đang ở',
                                'vacant' => 'Trống',
                                'handover' => 'Chờ bàn giao',
                                'locked' => 'Tạm khóa',
                            ])
                            ->default('occupied')
                            ->required(),
                        DatePicker::make('handover_date')->label('Ngày bàn giao'),
                        TextInput::make('management_fee')->label('Phí quản lý (đ/m²)')->numeric(),
                        Textarea::make('note')->label('Ghi chú')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }
}
