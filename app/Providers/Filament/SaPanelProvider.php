<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsurePlatformAdmin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * SuperAdmin / Platform — Cổng nền tảng SaaS (/sa).
 *
 * Tầng CAO NHẤT của mô hình 3 tầng: Platform (/sa) → HQ (/hq) → BQL dự án (/admin).
 * Chứa quản trị nền tảng: định danh gốc & thư viện mẫu 3 cấp, SaaS billing/subscription,
 * Integration Center, Support Center, nội dung nền tảng. Chỉ platform admin vào được
 * (EnsurePlatformAdmin). Dùng lại bộ component X2 + theme navy/gold.
 */
class SaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('sa')
            ->path('sa')
            ->login()
            ->brandName('X2-BMS')
            ->colors([
                'primary' => Color::hex('#2563eb'),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(false)
            ->navigationGroups([
                NavigationGroup::make('Nền tảng (SuperAdmin)')->icon('heroicon-o-building-library'),
                NavigationGroup::make('SaaS Billing')->icon('heroicon-o-banknotes'),
                NavigationGroup::make('Lưu trữ & Sao lưu')->icon('heroicon-o-archive-box'),
                NavigationGroup::make('Integration Center')->icon('heroicon-o-bolt'),
                NavigationGroup::make('Support Center')->icon('heroicon-o-lifebuoy'),
                NavigationGroup::make('Design System')->icon('heroicon-o-swatch'),
            ])
            ->discoverPages(in: app_path('Filament/Sa/Pages'), for: 'App\\Filament\\Sa\\Pages')
            ->discoverWidgets(in: app_path('Filament/Sa/Widgets'), for: 'App\\Filament\\Sa\\Widgets')
            // Fonts (DS-01): Inter (body) + Plus Jakarta Sans (titles/menu/KPI).
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<link rel="stylesheet" href="/fonts/x2-fonts.css">',
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_START,
                fn (): string => Blade::render('@include("filament.brand")'),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => Blade::render('@include("filament.hooks.topbar-start")'),
            )
            // Header: workspace switcher (Platform ↔ HQ ↔ BQL) placed before the avatar.
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('@include("filament.sa.header-cluster")'),
            )
            // WEB-UX-MOBILE — responsive app shell below lg (shared component).
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => Blade::render('<x-x2.mobile-shell />'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('<x-x2.ai-fab /> @auth @livewire(\'global-search\') @livewire(\'context-switcher\') @endauth'),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsurePlatformAdmin::class,
            ]);
    }
}
