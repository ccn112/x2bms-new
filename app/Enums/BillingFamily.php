<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\FeeType;

/**
 * NĂM nhóm phí gốc (billing family) — quyết định chủ dự án D2/D4/D6-bis
 * (`docs/BILLING_OWNER_DECISIONS_20260731.md`, 2026-07-31).
 *
 * Vì sao phải có enum này thay vì dùng `fee_types.category` sẵn có:
 * `category` gộp điện và nước chung vào `utility` (9 fee type: `Tiền điện`,
 * `Phí nước sinh hoạt`, `Nước nóng trung tâm`, `Điện theo khung giờ`…). Nhưng D4 chốt thứ
 * tự phân bổ mặc định là **Phí quản lý → Nước → Điện → Phương tiện → Khác** — với
 * `category` thì không có cách nào xếp Nước trước Điện. Nên 5 family không phải lựa chọn
 * kiến trúc, nó là điều kiện để D4 chạy được.
 *
 * Cấp 2 và 3 của mô hình phí (D6-bis):
 *   family (enum này) › fee_type (`fee_types`, danh mục ~39 dòng) › tài sản
 *   (`statement_lines.subject_*` → `vehicles` / `meters`)
 * Ví dụ: `vehicle` › "Phí gửi ô tô" › xe BKS 51K-838888.
 *
 * Lưu ở `statement_lines.fee_category` (cột đã có, đang NULL 66%).
 */
enum BillingFamily: string
{
    case Management = 'management';
    case Water = 'water';
    case Electricity = 'electricity';
    case Vehicle = 'vehicle';
    case Other = 'other';

    /** Nhãn cho cư dân và cho BQL — nhãn do SERVER trả, app không tự dịch. */
    public function label(): string
    {
        return match ($this) {
            self::Management => 'Phí quản lý',
            self::Water => 'Nước',
            self::Electricity => 'Điện',
            self::Vehicle => 'Phương tiện',
            self::Other => 'Phí khác',
        };
    }

    /**
     * Thứ tự phân bổ MẶC ĐỊNH khi BQL chưa sắp (D4).
     *
     * Chừa khoảng 100 giữa các bậc để BQL chèn loại phí mới mà không phải đánh số lại
     * toàn bộ. `Other` để 900 — khoản chưa gán family bao giờ cũng xếp cuối, nên một fee
     * type mới chưa ai phân loại không bao giờ chen lên trước phí quản lý.
     */
    public function defaultPriority(): int
    {
        return match ($this) {
            self::Management => 100,
            self::Water => 200,
            self::Electricity => 300,
            self::Vehicle => 400,
            self::Other => 900,
        };
    }

    /** Family theo thứ tự phân bổ mặc định, tăng dần. */
    public static function inAllocationOrder(): array
    {
        $cases = self::cases();
        usort($cases, fn (self $a, self $b) => $a->defaultPriority() <=> $b->defaultPriority());

        return $cases;
    }

    /**
     * Loại phí này có sinh ra theo TỪNG TÀI SẢN không (cấp 3 của D6-bis)?
     *
     * `per_vehicle` là tín hiệu có sẵn trong `fee_types.unit` (đã dùng ở `OTO`, `XEMAY`).
     * Điện/nước gắn với đồng hồ. Phí quản lý gắn với chính căn hộ nên không cần cấp 3.
     */
    public function requiresSubject(): bool
    {
        return match ($this) {
            self::Vehicle, self::Electricity, self::Water => true,
            self::Management, self::Other => false,
        };
    }

    /** Loại tài sản kỳ vọng ở cấp 3 — dùng để validate cột "Tài sản" khi import. */
    public function subjectKind(): ?string
    {
        return match ($this) {
            self::Vehicle => 'vehicle',
            self::Electricity, self::Water => 'meter',
            self::Management, self::Other => null,
        };
    }

    /**
     * Suy family từ một `FeeType` — CHỖ DUY NHẤT chứa logic này (backfill, import và
     * engine Phase 2 đều gọi vào đây; hai bản sao sẽ lệch).
     *
     * `utility` phải tách nước/điện bằng `code`/`name` vì `category` không phân biệt.
     * Không đoán được thì về `Other` (D2 chốt: BQL gán tay sau), KHÔNG đoán bừa — đoán sai
     * family là sai thứ tự phân bổ, tức là sai tiền vào đâu.
     */
    public static function fromFeeType(FeeType $feeType): self
    {
        return self::fromParts($feeType->category, $feeType->code, $feeType->name);
    }

    /** Bản thuần cho test và cho backfill bằng query thô (không cần hydrate model). */
    public static function fromParts(?string $category, ?string $code, ?string $name): self
    {
        $haystack = mb_strtolower(trim(($code ?? '').' '.($name ?? '')));

        return match ($category) {
            'management' => self::Management,
            'parking' => self::Vehicle,
            'utility' => self::splitUtility($haystack),
            // service | surcharge | reserve | other | null
            default => self::Other,
        };
    }

    /**
     * Tách `utility` → nước / điện.
     *
     * Kiểm NƯỚC TRƯỚC ĐIỆN có chủ ý: "Nước nóng trung tâm" chứa cả "nước", còn
     * "Phí sạc xe điện" chứa "điện" nhưng thực chất là phương tiện. Ba trường hợp mơ hồ
     * (`Phí điều hòa trung tâm`, `Phí sạc xe điện`, fee type utility mới) rơi về `Other`
     * để BQL gán tay — đúng chủ trương D2 "trường hợp nào chưa rõ cho vào phí khác".
     */
    private static function splitUtility(string $haystack): self
    {
        if (str_contains($haystack, 'nuoc') || str_contains($haystack, 'nước')) {
            return self::Water;
        }

        if (str_contains($haystack, 'dien') || str_contains($haystack, 'điện')) {
            // "Phí sạc xe điện" là phương tiện, không phải điện sinh hoạt.
            if (str_contains($haystack, 'xe')) {
                return self::Other;
            }

            return self::Electricity;
        }

        return self::Other;
    }
}
