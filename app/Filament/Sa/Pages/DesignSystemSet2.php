<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use BackedEnum;
use Filament\Pages\Page;

/**
 * Design System · DS-02 — Typography, Color, Icon, Spacing. 1 nav menu, các trang
 * (10 màn DS-02) gộp vào tab: Typography · Màu sắc · Icon · Spacing · States.
 * Chỉ ở /sa; chuẩn áp chung /sa /hq /admin qua theme.css + component x2.*.
 */
class DesignSystemSet2 extends Page
{
    use PlatformScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-vertical';

    protected static string|\UnitEnum|null $navigationGroup = 'Design System';

    protected static ?string $navigationLabel = 'DS-02 · Typography · Màu · Icon · Spacing';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Design System · DS-02 Typography · Color · Icon · Spacing';

    protected static ?string $slug = 'design-system/ds02';

    protected string $view = 'filament.sa.ds.set2';
}
