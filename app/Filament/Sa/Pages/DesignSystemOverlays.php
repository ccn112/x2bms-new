<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use BackedEnum;
use Filament\Pages\Page;

/** Design System · Modal, Drawer, Notification & AI — modal/drawer, wizard, thông báo (toast/banner/dropdown), approval, AI, system states. */
class DesignSystemOverlays extends Page
{
    use PlatformScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-window';

    protected static string|\UnitEnum|null $navigationGroup = 'Design System';

    protected static ?string $navigationLabel = 'Modal, Thông báo & AI';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Design System · Modal, Drawer, Notification & AI';

    protected static ?string $slug = 'design-system/overlays';

    protected string $view = 'filament.sa.ds.overlays';
}
