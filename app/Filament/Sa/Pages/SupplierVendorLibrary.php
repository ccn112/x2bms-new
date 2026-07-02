<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\SharedPartnerLibrary;
use App\Filament\Concerns\WritesAudit;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;

/** WEB-UX-22-07 — Thư viện NCC vật tư/thiết bị/dịch vụ dùng chung (có catalog sản phẩm + giá tham khảo). */
class SupplierVendorLibrary extends Page implements HasTable
{
    use InteractsWithTable, SharedPartnerLibrary {
        SharedPartnerLibrary::table insteadof InteractsWithTable;
    }
    use PlatformScreen;
    use WritesAudit;

    protected static function platformFeature(): ?string
    {
        return 'supplier_library';
    }

    protected function partnerType(): string
    {
        return 'supplier';
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|\UnitEnum|null $navigationGroup = 'Nền tảng (SuperAdmin)';

    protected static ?string $navigationLabel = 'Thư viện NCC vật tư';

    protected static ?int $navigationSort = 41;

    protected static ?string $title = 'Thư viện nhà cung cấp & vật tư';

    protected static ?string $slug = 'platform/suppliers';

    protected string $view = 'filament.pages.shared-partner-library';
}
