<?php

namespace App\Filament\Resources\TenantSubscriptions\Pages;

use App\Filament\Resources\TenantSubscriptions\TenantSubscriptionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenantSubscription extends CreateRecord
{
    protected static string $resource = TenantSubscriptionResource::class;
}
