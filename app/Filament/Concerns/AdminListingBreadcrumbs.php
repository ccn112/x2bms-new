<?php

namespace App\Filament\Concerns;

use Illuminate\Support\HtmlString;

/**
 * Breadcrumb CHUẨN cho trang listing /admin (theo LISTING_PAGE_STANDARD / ResidentDirectory):
 * `Tổng quan → <tên trang>` với icon. Trang chỉ cần `use AdminListingBreadcrumbs;` là có
 * breadcrumb đồng bộ; muốn tùy biến thì override `getBreadcrumbs()` hoặc `breadcrumbIcon()`.
 */
trait AdminListingBreadcrumbs
{
    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => $this->crumb('heroicon-m-home', 'Tổng quan'),
            $this->crumb($this->breadcrumbIcon(), $this->breadcrumbLabel()),
        ];
    }

    protected function breadcrumbIcon(): string
    {
        return 'heroicon-m-rectangle-stack';
    }

    protected function breadcrumbLabel(): string
    {
        return static::$title
            ?? (method_exists(static::class, 'getNavigationLabel') ? static::getNavigationLabel() : class_basename(static::class));
    }

    protected function crumb(string $icon, string $label): HtmlString
    {
        return new HtmlString(
            '<span class="inline-flex items-center gap-1">'
            .svg($icon, 'h-4 w-4 shrink-0 opacity-70')->toHtml()
            .'<span>'.e($label).'</span></span>'
        );
    }
}
