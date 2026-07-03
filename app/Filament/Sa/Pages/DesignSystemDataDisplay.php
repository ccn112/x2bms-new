<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use BackedEnum;
use Filament\Pages\Page;

/** Design System · KPI Cards & Data Table — thẻ KPI, các loại card, bảng dữ liệu, trạng thái bảng. */
class DesignSystemDataDisplay extends Page
{
    use PlatformScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    protected static string|\UnitEnum|null $navigationGroup = 'Design System';

    protected static ?string $navigationLabel = 'KPI & Bảng dữ liệu';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Design System · KPI Cards & Data Table';

    protected static ?string $slug = 'design-system/data-display';

    protected string $view = 'filament.sa.ds.data-display';
}
