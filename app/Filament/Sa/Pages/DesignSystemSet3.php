<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use BackedEnum;
use Filament\Pages\Page;

/**
 * Design System · DS-03 — Button, Action, Badge, Status. 1 nav menu, 10 màn DS-03
 * gộp vào tab: Button Hierarchy · Page Action Bar · Compact Action Group · Header vs
 * Page Create · Row Actions · Bulk Action Bar · Split Button · Badge Count · Status
 * Pill · Action Decision Matrix. Chỉ ở /sa; chuẩn áp chung /sa /hq /admin.
 */
class DesignSystemSet3 extends Page
{
    use PlatformScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cursor-arrow-rays';

    protected static string|\UnitEnum|null $navigationGroup = 'Design System';

    protected static ?string $navigationLabel = 'DS-03 · Button · Action · Badge · Status';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Design System · DS-03 Button · Action · Badge · Status';

    protected static ?string $slug = 'design-system/ds03';

    protected string $view = 'filament.sa.ds.set3';
}
