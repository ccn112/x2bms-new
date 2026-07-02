<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureHqAccess;
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
 * HQ Portal — Cổng Công ty (Tenant HQ / công ty quản lý vận hành đa dự án).
 *
 * Tầng GIỮA của mô hình 3 tầng: Platform (SuperAdmin, /admin) → HQ (/hq) → BQL dự án.
 * Context = tenant hiện tại + tập project được chọn (đa dự án). Chỉ platform admin
 * hoặc tenant operator vào được (EnsureHqAccess). Dùng lại bộ component X2 + theme navy/gold.
 */
class HqPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('hq')
            ->path('hq')
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
                NavigationGroup::make('Tổng quan'),
                NavigationGroup::make('Quản lý dự án'),
                NavigationGroup::make('Nhân sự & BQL'),
                NavigationGroup::make('Billing & Gói dịch vụ'),
                NavigationGroup::make('Biểu mẫu & Tri thức'),
                NavigationGroup::make('Hỗ trợ & Phân quyền'),
                NavigationGroup::make('Báo cáo'),
            ])
            ->discoverPages(in: app_path('Filament/Hq/Pages'), for: 'App\\Filament\\Hq\\Pages')
            ->discoverWidgets(in: app_path('Filament/Hq/Widgets'), for: 'App\\Filament\\Hq\\Widgets')
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:400,500,600,700|manrope:500,600,700,800&display=swap">',
            )
            // Sidebar brand block — reuse the shared X2-BMS brand, labelled HQ PORTAL.
            ->renderHook(
                PanelsRenderHook::SIDEBAR_START,
                fn (): string => Blade::render('@include("filament.hq.brand")'),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => Blade::render('@include("filament.hooks.topbar-start")'),
            )
            // Header: company context selector + multi-project scope + notifications + help.
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('@include("filament.hq.header-cluster")'),
            )
            // Shared X2AI floating chat on every HQ screen.
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('<x-x2.ai-fab />'),
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
                EnsureHqAccess::class,
            ]);
    }
}
