<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use BackedEnum;
use Filament\Pages\Page;

/** Design System · Form, Filter & Search — trường nhập liệu, lựa chọn/điều khiển, filter bar, drawer lọc, validation. */
class DesignSystemForms extends Page
{
    use PlatformScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Design System';

    protected static ?string $navigationLabel = 'Form, Bộ lọc & Tìm kiếm';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Design System · Form, Filter & Search';

    protected static ?string $slug = 'design-system/forms';

    protected string $view = 'filament.sa.ds.forms';
}
