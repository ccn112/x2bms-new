<?php

namespace App\Filament\Pages;

use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/**
 * BQL-00-08 — Permission / Invalid Context State.
 * Shared state screen shown when the user lacks permission or their project/building
 * context was revoked. Offers reselect context / back to dashboard / contact admin.
 * Hidden from navigation — reached via redirect from guards (EnsureProjectContext etc.).
 */
class PermissionState extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $title = 'Permission / Invalid Context State';

    protected static ?string $slug = 'access-denied';

    protected string $view = 'filament.pages.permission-state';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $ctx = app(CurrentContext::class);

        return [
            'user' => $user,
            'roleLabel' => $ctx->workspaceLabel(),
            'project' => $ctx->project(),
            'building' => $ctx->buildings()->first(),
            'reason' => request()->query('reason', 'invalid_context'),
        ];
    }
}
