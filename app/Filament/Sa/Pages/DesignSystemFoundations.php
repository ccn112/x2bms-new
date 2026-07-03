<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use BackedEnum;
use Filament\Pages\Page;

/**
 * Design System · Nền tảng — Typography, Màu sắc, Spacing, Bo góc, Điều hướng,
 * Bố cục trang, Nguyên tắc thiết kế. Living style guide đọc token thật từ theme.css.
 */
class DesignSystemFoundations extends Page
{
    use PlatformScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    protected static string|\UnitEnum|null $navigationGroup = 'Design System';

    protected static ?string $navigationLabel = 'App Shell & Điều hướng';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Design System · App Shell & Navigation';

    protected static ?string $slug = 'design-system';

    protected string $view = 'filament.sa.ds.foundations';
}
