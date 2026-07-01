<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Opt-in for Filament resources whose model uses SoftDeletes.
 *
 * Removes the SoftDeletingScope from the resource's base query so trashed rows
 * become reachable — the table's TrashedFilter then decides what is shown, and
 * RestoreAction / ForceDeleteAction can operate on soft-deleted records.
 *
 * Pair with TrashedFilter + Restore/ForceDelete actions in the table (see the
 * *Table classes) for the full trashed-management UX.
 */
trait SoftDeletableResource
{
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
