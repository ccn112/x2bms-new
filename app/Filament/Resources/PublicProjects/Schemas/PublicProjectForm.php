<?php

namespace App\Filament\Resources\PublicProjects\Schemas;

use App\Models\PublicProject;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class PublicProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin cơ bản')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')->label('Mã')->required(),
                        TextInput::make('name')->label('Tên dự án')->required(),
                        TextInput::make('developer_name')->label('Chủ đầu tư'),
                        TextInput::make('project_type')->label('Loại hình'),
                        TextInput::make('status')->label('Trạng thái')->required()->default('operating'),
                        Toggle::make('is_public')->label('Công khai')->required(),
                        TextInput::make('blocks')->label('Số block/tòa')->numeric()->default(0)->required(),
                        TextInput::make('apartments')->label('Số căn hộ')->numeric()->default(0)->required(),
                        Textarea::make('description')->label('Mô tả')->columnSpanFull(),
                    ]),

                Section::make('Địa chỉ & vị trí')
                    ->columns(2)
                    ->schema([
                        TextInput::make('address')->label('Địa chỉ đầy đủ')->columnSpanFull(),
                        TextInput::make('ward')->label('Phường/Xã'),
                        TextInput::make('district')->label('Quận/Huyện'),
                        TextInput::make('province')->label('Tỉnh/TP'),
                        TextInput::make('latitude')->label('Vĩ độ (latitude)')->numeric(),
                        TextInput::make('longitude')->label('Kinh độ (longitude)')->numeric(),
                        Placeholder::make('map_link')
                            ->label('Bản đồ')
                            ->columnSpanFull()
                            ->content(function (?PublicProject $record): HtmlString {
                                if (! $record || $record->latitude === null || $record->longitude === null) {
                                    return new HtmlString('<span style="color:#9ca3af">Chưa có toạ độ</span>');
                                }
                                $q = $record->latitude.','.$record->longitude;
                                $url = 'https://www.google.com/maps?q='.$q;

                                return new HtmlString(
                                    '<a href="'.e($url).'" target="_blank" rel="noopener" '
                                    .'style="color:#2563eb;text-decoration:underline">Mở Google Maps ('.e($q).')</a>'
                                );
                            }),
                    ]),

                Section::make('Thông tin chi tiết (batdongsan)')
                    ->collapsible()
                    ->schema([
                        Placeholder::make('detail_table')
                            ->label('Bảng thông tin dự án')
                            ->content(function (?PublicProject $record): HtmlString {
                                $meta = (array) ($record?->metadata_json ?? []);
                                $detail = (array) ($meta['detail'] ?? []);
                                // Bổ sung các trường suy ra riêng.
                                $extra = array_filter([
                                    'Mức giá' => $meta['price'] ?? null,
                                    'Pháp lý' => $meta['legal'] ?? null,
                                    'Đơn vị phát triển' => $meta['developer_unit'] ?? null,
                                ]);
                                $rows = $detail + $extra;
                                if ($rows === []) {
                                    return new HtmlString('<span style="color:#9ca3af">Chưa có dữ liệu chi tiết — bấm "Lấy tiếp" hoặc chạy enrich.</span>');
                                }
                                $html = '<table style="width:100%;border-collapse:collapse">';
                                foreach ($rows as $k => $v) {
                                    $html .= '<tr>'
                                        .'<td style="padding:4px 8px;border:1px solid #e5e7eb;font-weight:600;width:40%">'.e($k).'</td>'
                                        .'<td style="padding:4px 8px;border:1px solid #e5e7eb">'.e((string) $v).'</td>'
                                        .'</tr>';
                                }
                                $html .= '</table>';

                                return new HtmlString($html);
                            }),
                        Placeholder::make('images_gallery')
                            ->label('Ảnh dự án')
                            ->content(function (?PublicProject $record): HtmlString {
                                $meta = (array) ($record?->metadata_json ?? []);
                                $images = (array) ($meta['images'] ?? []);
                                if ($images === [] && ! empty($meta['cover_image'])) {
                                    $images = [$meta['cover_image']];
                                }
                                if ($images === []) {
                                    return new HtmlString('<span style="color:#9ca3af">Chưa có ảnh</span>');
                                }
                                $wm = ! empty($meta['images_watermarked'])
                                    ? '<div style="color:#b45309;font-size:12px;margin-bottom:6px">⚠ Ảnh có watermark batdongsan — thay bằng ảnh chính thống khi có.</div>'
                                    : '';
                                $thumbs = '';
                                foreach (array_slice($images, 0, 12) as $u) {
                                    $thumbs .= '<a href="'.e($u).'" target="_blank" rel="noopener">'
                                        .'<img src="'.e($u).'" style="height:96px;border-radius:6px;border:1px solid #e5e7eb"/></a>';
                                }

                                return new HtmlString($wm.'<div style="display:flex;flex-wrap:wrap;gap:8px">'.$thumbs.'</div>');
                            }),
                    ]),
            ]);
    }
}
