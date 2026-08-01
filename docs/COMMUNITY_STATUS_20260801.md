# Cộng đồng — Đối chiếu hiện trạng vs 2 handoff (2026-08-01)

> Cập nhật theo yêu cầu chủ dự án, đối chiếu code THỰC TẾ (x2bms + x2mobile) với:
> - **Domain Handoff** `handoff/x2mobile/X2_BMS_COMMUNITY_DOMAIN_HANDOFF_20260729` (spec chuẩn hóa miền, 19 doc, 8 mục tiêu, = nguồn của kế hoạch 9 giai đoạn `COMMUNITY_IMPLEMENTATION_PLAN.md`).
> - **Mass Seed Handoff** `handoff/x2bms/X2_BMS_COMMUNITY_MASS_SEED_HANDOFF_20260726` (bộ công cụ seed quy mô + load test, = doc 15 của handoff domain / GĐ9).
>
> Ký hiệu: ✅ xong · 🟡 một phần · ❌ chưa · (cả hai đầu) = cần cả backend lẫn app.

## A. 8 mục tiêu của Domain Handoff

| # | Mục tiêu | Trạng thái |
|---|---|---|
| 1 | Một Community Domain DUY NHẤT (không tách "Public"/"Resident") | 🟡 App có 1 feature `resident_community` (không còn codebase public riêng); backend 1 bộ controller. Về cơ bản đạt, chưa có audit hợp nhất chính thức |
| 2 | 3 tầng guest → member → verified_resident | ✅ `ExperienceMode` + `ability` |
| 3 | 4 lớp không gian (X2Living · Kênh quan tâm dự án · Nhóm cư dân chính thức/xác minh · Nhóm tự lập) | 🟡 backend `group_type` (6 giá trị) + `scope` trong Resource; **app ĐÃ consume 01/08 (P2)** gom nhóm theo `sectionKey`. Thiếu: `parent_group_id`/cây phân cấp |
| 4 | Quyền do backend trả `capabilities`, Flutter chỉ render | ✅ backend trả `capabilities{}`; **app ĐÃ đọc 01/08 (P2)** (`effectiveCanPost`/`effectiveCanLeave`) |
| 5 | Theo dõi dự án là dữ liệu chuẩn; hashtag chỉ là lớp khám phá | 🟡 **Follow XONG 01/08** (backend GĐ4 + app P1). Hashtag: ❌ chưa |
| 6 | Nhóm tích xanh/vàng có nghĩa nghiệp vụ | ✅ `verification_badge`/`verification_label` (server tính, app render) |
| 7 | **Bình luận cộng đồng là MODULE RIÊNG**, không tái dùng bình luận phiếu | ❌ **CHƯA** — vẫn dùng `CommentThread` chung (`features/comments`). 01/08 chỉ nâng UX FB-style (ô nhập ghim đáy) TRÊN module chung. Tách module = GĐ7 |
| 8 | Tương thích: feed/đăng/reaction/comment/moderation đã có; groups/events/polls một phần | ✅ đúng hiện trạng |

## B. Kế hoạch 9 giai đoạn (Domain Handoff)

| GĐ | Nội dung | Trạng thái |
|---|---|---|
| GĐ1 | Nền: `CommunityAccessService`, capability resolver hợp nhất, error code chuẩn, idempotency middleware | ❌ chưa |
| GĐ2 | `group_type`/scope/hierarchy/verification | 🟡 backend Resource có `group_type`/`scope`/`capabilities`/`verification_badge`; **app consume 01/08**. Thiếu backend expose `parent_group_id`/`post_count`/`lifecycle_state` + UI cây |
| GĐ3 | Membership & grants | ✅ backend `MembershipService` + `community_membership_grants` + wiring (duyệt cư dân/bootstrap/join-leave). App đọc `joined` |
| GĐ4 | Theo dõi dự án (`me/project-follows`) | ✅ backend + **app 01/08** (feature `project_follows` + nút Theo dõi chi tiết dự án, chỉ khi `operational_project_id`) |
| GĐ5 | Content/feed bootstrap (`GET resident/community/bootstrap`) | 🟡 **Backend XONG 01/08** (verify HTTP: tier/scopes/tabs/groups/follows/composer, một call) — `CommunityController::bootstrap()` + route. **App CHƯA wire** (vẫn gọi rời groups/feed; bootstrap là tối ưu, làm sau) |
| GĐ6 | Ảnh | 🟡 `POST resident/uploads` có; ảnh trong bài/bình luận dùng được |
| GĐ7 | **Tách module bình luận cộng đồng riêng** (phân trang, reply đa cấp, nhắc tên, cảm xúc trên bình luận, kiểm duyệt quy mô) | ❌ chưa (điều kiện tiên quyết: seed khối lượng lớn — xem Mass Seed) |
| GĐ8 | Kiểm duyệt tổng quát hơn | 🟡 Web BQL moderation (B6) xong; app moderation qua `can{}` |
| GĐ9 | Scale/performance | ❌ chưa — xem mục C |

## C. Mass Seed Handoff (26/07) — GĐ9 / doc 15

Bộ công cụ: command `community:seed-scale --profile={demo|ux|load|full}`, batch insert + resume checkpoint, index MySQL, k6 load test, test tenant-isolation + cursor-pagination + counter-consistency. Quy mô demo (2k bài) → full (1 triệu bài, ~25 triệu comment).

**Trạng thái: ❌ CHƯA tích hợp.** Repo hiện chỉ có seeder demo nhỏ (`CommunityFeedDemoSeeder`, `CommunityRefPostsSeeder`, `SecondProjectDemoSeeder`), không có `community:seed-scale`, không có index/k6/test scale của gói. → Đây là **điều kiện tiên quyết của GĐ7** (thiết kế phân trang/scale module bình luận cần dữ liệu lớn để đối chiếu) và của GĐ9.

## D. Mới xong trong phiên 2026-08-01

- **App consume thiết kế backend mới (P2)**: `group_type`(6)/`capabilities`/`scope`; gom nhóm theo `sectionKey` (fallback `kind`, R5); `effectiveCanPost`/`effectiveCanLeave`.
- **Theo dõi dự án (GĐ4) end-to-end (P1)**: backend `operational_project_id` trong card catalog + app feature `project_follows` + nút Theo dõi/Đang theo dõi (login-gate), nút cũ đổi "Lưu".
- **UX bình luận FB-style**: ô soạn **ghim đáy màn** (`CommentThread.pinnedComposer` + `header`), danh sách cuộn trên, ô nhập tự nâng trên bàn phím — thay vì trôi theo danh sách. **Vẫn trên module chung** (chưa phải GĐ7).
- Đã verify **trên điện thoại thật** (Samsung A05s): feed thật, thả cảm xúc, bình luận hiện ngay + reply, bộ lọc phạm vi — luồng cộng đồng thông suốt.

## E. Việc còn lại theo ưu tiên

1. **GĐ5 — `GET resident/community/bootstrap`** (ưu tiên cao nhất còn treo của kế hoạch).
2. **Mass Seed (GĐ9)** — tích hợp `community:seed-scale` + index + k6 + test scale. Là **chặn của GĐ7**.
3. **GĐ7 — tách module bình luận cộng đồng riêng** (sau khi có seed lớn): phân trang, reply đa cấp, nhắc tên, cảm xúc trên bình luận, kiểm duyệt quy mô. Hiện dùng chung `CommentThread`.
4. **GĐ2 hoàn tất** — backend expose `parent_group_id`/`post_count`/`lifecycle_state` → app dựng UI cây phân cấp nhóm.
5. **GĐ1 nền** — `CommunityAccessService` + capability resolver hợp nhất + error code + idempotency.
6. Hashtag (lớp khám phá — mục tiêu 5, phần còn lại).

> Nguồn theo dõi chính vẫn là `PROGRESS_TRACKER.md` (mục BQL-07 Community Domain) + `COMMUNITY_IMPLEMENTATION_PLAN.md`. File này là ảnh chụp đối chiếu 2 handoff tại 2026-08-01.
