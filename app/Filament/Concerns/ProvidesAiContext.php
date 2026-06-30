<?php

namespace App\Filament\Concerns;

/**
 * Lets a Filament page feed its on-screen context into the ONE shared X2AI
 * floating chat (<x-x2.ai-fab>), instead of rendering a separate inline AI panel.
 *
 * Owner rule: every screen that "has AI" surfaces its local context into the
 * common floating chat. Call shareAiContext() from getViewData()/mount().
 *
 * Context shape:
 *   ['title' => string, 'lines' => string[], 'suggestions' => [['title'=>.., 'sub'=>..], ...]]
 */
trait ProvidesAiContext
{
    protected function shareAiContext(?array $context): void
    {
        view()->share('x2aiContext', $context);
    }
}
