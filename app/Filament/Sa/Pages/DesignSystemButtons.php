<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use BackedEnum;
use Filament\Pages\Page;

/** Design System · Buttons & Header Actions — thứ bậc nút, split/group, topbar, dropdown/kebab, badges, quick actions. */
class DesignSystemButtons extends Page
{
    use PlatformScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cursor-arrow-rays';

    protected static string|\UnitEnum|null $navigationGroup = 'Design System';

    protected static ?string $navigationLabel = 'Nút & Hành động';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Design System · Buttons & Header Actions';

    protected static ?string $slug = 'design-system/buttons';

    protected string $view = 'filament.sa.ds.buttons';
}
