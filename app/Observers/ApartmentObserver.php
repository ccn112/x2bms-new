<?php

namespace App\Observers;

use App\Models\Apartment;

/**
 * Cascade XÓA MỀM cho căn hộ — chạy ở MỌI code path (Filament, console, seed), bổ trợ
 * cảnh báo ràng buộc ở tầng Filament. Xóa mềm căn hộ → xóa mềm quan hệ cư dân↔căn hộ;
 * khôi phục căn hộ → khôi phục các quan hệ đã bị xóa CÙNG LÚC (cửa sổ 10 giây).
 * KHÔNG cascade khi force-delete (để composite FK/DB quyết định).
 */
class ApartmentObserver
{
    public function deleting(Apartment $apartment): void
    {
        if ($apartment->isForceDeleting()) {
            return;
        }
        $apartment->apartmentRelations()->get()->each->delete();
    }

    public function restoring(Apartment $apartment): void
    {
        $deletedAt = $apartment->deleted_at;
        if ($deletedAt === null) {
            return;
        }
        $apartment->apartmentRelations()->onlyTrashed()
            ->whereBetween('deleted_at', [$deletedAt->copy()->subSeconds(10), $deletedAt->copy()->addSeconds(10)])
            ->get()->each->restore();
    }
}
