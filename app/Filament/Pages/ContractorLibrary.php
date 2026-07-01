<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\SharedPartnerLibrary;
use App\Filament\Concerns\WritesAudit;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;

/** WEB-UX-22-06 — Thư viện nhà thầu dùng chung (PCCC, thang máy, MEP, vệ sinh, an ninh…). */
class ContractorLibrary extends Page implements HasTable
{
    use InteractsWithTable, SharedPartnerLibrary {
        SharedPartnerLibrary::table insteadof InteractsWithTable;
    }
    use PlatformScreen;
    use WritesAudit;

    protected static function platformFeature(): ?string
    {
        return 'contractor_library';
    }

    protected function partnerType(): string
    {
        return 'contractor';
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'Nền tảng (SuperAdmin)';

    protected static ?string $navigationLabel = 'Thư viện nhà thầu';

    protected static ?int $navigationSort = 40;

    protected static ?string $title = 'Thư viện nhà thầu dùng chung';

    protected static ?string $slug = 'platform/contractors';

    protected string $view = 'filament.pages.shared-partner-library';
}
