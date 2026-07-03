<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Design System · Bộ tổng quan (App Shell & UI) — 1 nav menu, các trang gộp vào tab:
 * Nền tảng · KPI & Bảng · Nút & Hành động · Form & Lọc · Modal & AI · Tabs & Chi tiết.
 * HasForms để tab "Form & Lọc" render field Filament thật. Trang chỉ ở /sa;
 * chuẩn thiết kế áp chung cho /sa /hq /admin qua theme.css + component x2.*.
 */
class DesignSystemSet1 extends Page implements HasForms
{
    use InteractsWithForms;
    use PlatformScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    protected static string|\UnitEnum|null $navigationGroup = 'Design System';

    protected static ?string $navigationLabel = 'Bộ tổng quan (App Shell & UI)';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Design System · Tổng quan App Shell & UI';

    protected static ?string $slug = 'design-system';

    protected string $view = 'filament.sa.ds.set1';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'multi' => ['ky_thuat', 've_sinh'],
            'checks' => ['ky_thuat', 've_sinh'],
            'radio' => '1',
            'switch' => true,
            'label_ok' => 'Dữ liệu hợp lệ',
        ]);
    }

    protected function getForms(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        $depts = ['ky_thuat' => 'Kỹ thuật', 've_sinh' => 'Vệ sinh', 'an_ninh' => 'An ninh'];

        return $schema
            ->statePath('data')
            ->components([
                Grid::make(3)->schema([
                    Section::make('1. Các trường nhập liệu')
                        ->icon('heroicon-o-pencil')->columnSpan(1)
                        ->schema([
                            TextInput::make('text')->label('Nhập liệu văn bản')->placeholder('Nhập nội dung'),
                            TextInput::make('search')->label('Tìm kiếm')->placeholder('Tìm kiếm…')
                                ->prefixIcon('heroicon-m-magnifying-glass'),
                            TextInput::make('phone')->label('Số điện thoại')->tel()->prefix('+84')->placeholder('912 345 678'),
                            TextInput::make('amount')->label('Tiền tệ')->numeric()->prefix('VND')->placeholder('1.250.000'),
                            Textarea::make('note')->label('Vùng văn bản')->placeholder('Nhập nội dung chi tiết…')->rows(3)->maxLength(500),
                            FileUpload::make('file')->label('Tải tệp lên')->multiple()
                                ->disk('public')->directory('ds-demo')
                                ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg'])->maxSize(10240)
                                ->helperText('Hỗ trợ: .pdf, .jpg, .png (tối đa 10MB)'),
                            Grid::make(2)->schema([
                                DatePicker::make('from')->label('Từ ngày')->native(false),
                                DatePicker::make('to')->label('Đến ngày')->native(false),
                            ]),
                        ]),
                    Section::make('2. Lựa chọn & Điều khiển')
                        ->icon('heroicon-o-adjustments-horizontal')->columnSpan(1)
                        ->schema([
                            Select::make('single')->label('Chọn một (Select)')->placeholder('Chọn một tùy chọn')->native(false)->options($depts),
                            Select::make('multi')->label('Chọn nhiều (Multi-select)')->multiple()->native(false)->options($depts),
                            Grid::make(2)->schema([
                                CheckboxList::make('checks')->label('Checkbox')->options(['tat_ca' => 'Tất cả'] + $depts)->columns(1),
                                Radio::make('radio')->label('Radio')->options(['1' => 'Tùy chọn 1', '2' => 'Tùy chọn 2', '3' => 'Tùy chọn 3']),
                            ]),
                            Toggle::make('switch')->label('Switch (Bật/Tắt)')->inline(false),
                        ]),
                    Section::make('5. Xác thực & Thứ bậc nhãn')
                        ->icon('heroicon-o-check-circle')->columnSpan(1)
                        ->schema([
                            TextInput::make('label_req')->label('Nhãn chính (bắt buộc)')->required()->placeholder('Nhập nội dung')->helperText('Mô tả ngắn gọn về trường nhập liệu.'),
                            TextInput::make('label_opt')->label('Nhãn phụ (không bắt buộc)')->placeholder('Nhập nội dung')->helperText('Thông tin bổ sung.'),
                            TextInput::make('label_ok')->label('Thành công')->helperText('Dữ liệu hợp lệ.'),
                            TextInput::make('disabled')->label('Vô hiệu hóa')->disabled()->placeholder('Không thể chỉnh sửa'),
                            Placeholder::make('states_note')->label('')
                                ->content('Trạng thái focus (viền xanh), lỗi (viền đỏ + thông báo) hiển thị khi xác thực biểu mẫu.'),
                        ]),
                ]),
            ]);
    }
}
