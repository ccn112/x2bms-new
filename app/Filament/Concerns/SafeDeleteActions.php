<?php

namespace App\Filament\Concerns;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

/**
 * Xóa MỀM có kiểm ràng buộc cho các bảng Filament (BQL/HQ).
 *
 * - Chỉ xóa MỀM (deleted_at) — khôi phục được qua bộ lọc "Đã xóa" + RestoreAction.
 * - Nếu còn quan hệ CHẶN (thường là tiền: bảng kê/công nợ) → cảnh báo rõ + DỪNG.
 * - Ngược lại cascade xóa mềm các quan hệ con để giữ nhất quán dữ liệu.
 * - KHÔNG dùng ForceDelete trên UI thường (mất dữ liệu + phá vết audit + tạo mồ côi).
 */
trait SafeDeleteActions
{
    /**
     * @param  array<string,string>  $blockers  method quan hệ => nhãn (chặn nếu count > 0)
     * @param  array<int,string>  $cascades  method quan hệ con để xóa mềm theo
     */
    protected static function safeSoftDelete(string $entityLabel, array $blockers = [], array $cascades = []): DeleteAction
    {
        return DeleteAction::make()
            ->label('Xóa (mềm)')
            ->modalHeading("Xóa mềm {$entityLabel}")
            ->modalDescription(
                "{$entityLabel} sẽ được xóa MỀM (khôi phục được từ bộ lọc \"Đã xóa\"). "
                .'Dữ liệu con liên quan (không ràng buộc tiền) được xóa mềm theo để giữ nhất quán.'
            )
            ->before(function (Model $record, DeleteAction $action) use ($blockers): void {
                $problems = [];
                foreach ($blockers as $relation => $label) {
                    $count = $record->{$relation}()->count();
                    if ($count > 0) {
                        $problems[] = "{$count} {$label}";
                    }
                }
                if ($problems !== []) {
                    Notification::make()
                        ->danger()
                        ->title('Không thể xóa — còn ràng buộc')
                        ->body('Còn liên quan: '.implode(' · ', $problems).'. Hãy xử lý (chuyển/đóng/hoàn) trước khi xóa.')
                        ->persistent()
                        ->send();
                    $action->halt();
                }
            })
            ->after(function (Model $record) use ($cascades): void {
                foreach ($cascades as $relation) {
                    $record->{$relation}()->get()->each(function (Model $child): void {
                        $child->delete(); // xóa mềm nếu model dùng SoftDeletes
                    });
                }
            });
    }
}
