<?php

namespace App\Observers;

use App\Models\Resident;

/**
 * Cascade XÓA MỀM cho cư dân — chạy ở MỌI code path. Xóa mềm cư dân → xóa mềm quan hệ
 * căn hộ + liên hệ khẩn cấp; khôi phục → khôi phục các bản ghi xóa cùng lúc (10 giây).
 */
class ResidentObserver
{
    public function deleting(Resident $resident): void
    {
        if ($resident->isForceDeleting()) {
            return;
        }
        $resident->apartmentRelations()->get()->each->delete();
        $resident->emergencyContacts()->get()->each->delete();
    }

    public function restoring(Resident $resident): void
    {
        $deletedAt = $resident->deleted_at;
        if ($deletedAt === null) {
            return;
        }
        $window = [$deletedAt->copy()->subSeconds(10), $deletedAt->copy()->addSeconds(10)];
        $resident->apartmentRelations()->onlyTrashed()->whereBetween('deleted_at', $window)->get()->each->restore();
        $resident->emergencyContacts()->onlyTrashed()->whereBetween('deleted_at', $window)->get()->each->restore();
    }
}
