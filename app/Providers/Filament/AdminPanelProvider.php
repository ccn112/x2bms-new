<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('X2-BMS')
            ->colors([
                'primary' => Color::hex('#2563eb'),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(false) // custom header search (WEB-UX-10) instead of Filament's
            ->userMenuItems([
                MenuItem::make()
                    ->label('Hồ sơ của tôi')
                    ->icon('heroicon-o-user-circle')
                    ->url('#'),
                MenuItem::make()
                    ->label('Bảo mật & 2FA')
                    ->icon('heroicon-o-shield-check')
                    ->url('#'),
                MenuItem::make()
                    ->label('Phiên đăng nhập')
                    ->icon('heroicon-o-computer-desktop')
                    ->url('#'),
            ])
            ->navigationGroups([
                NavigationGroup::make('Cư dân & Căn hộ'),
                NavigationGroup::make('Vận hành'),
                NavigationGroup::make('Cơ cấu & Tổ chức'),
                NavigationGroup::make('Tài chính – Phí'),
                NavigationGroup::make('X2 AI Engine')->icon('heroicon-o-sparkles'),
                NavigationGroup::make('Nền tảng (SuperAdmin)')->icon('heroicon-o-building-library'),
                NavigationGroup::make('SaaS Billing')->icon('heroicon-o-banknotes'),
                NavigationGroup::make('Hệ thống'),
            ])
            // /admin shows only the designed custom pages. Raw CRUD resources live
            // on the stock /fila panel (FilaPanelProvider) to avoid slug clashes.
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            // Fonts: Inter (body) + Manrope (titles & menu) — WEB-UX typography.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:400,500,600,700|manrope:500,600,700,800&display=swap">',
            )
            // Sidebar: X2-BMS brand block pinned to the top of the navy rail (WEB-UX-00).
            ->renderHook(
                PanelsRenderHook::SIDEBAR_START,
                fn (): string => Blade::render('@include("filament.brand")'),
            )
            // Header left: sidebar toggle + page title + global search (WEB-UX-00/10).
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => Blade::render('@include("filament.hooks.topbar-start")'),
            )
            // Header: context selector + quick-create + notifications + messages + help,
            // placed just before the user avatar (WEB-UX-00/01/09).
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('@include("filament.hooks.header-cluster")'),
            )
            // X2AI floating chat — the single shared AI surface, fixed bottom-right
            // on every /admin screen (UI_IMPLEMENTATION_RULES: X2AI floating button).
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
            ]);
    }
}
