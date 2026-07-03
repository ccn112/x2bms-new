<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use BackedEnum;
use Filament\Pages\Page;

/** Design System · Tabs, Record Detail & Timeline — kiểu tab, bố cục bản ghi, info blocks, related lists, timeline, audit log, AI side panel. */
class DesignSystemRecords extends Page
{
    use PlatformScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|\UnitEnum|null $navigationGroup = 'Design System';

    protected static ?string $navigationLabel = 'Tabs, Chi tiết & Timeline';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Design System · Tabs, Record Detail & Timeline';

    protected static ?string $slug = 'design-system/records';

    protected string $view = 'filament.sa.ds.records';
}
