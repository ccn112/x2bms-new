# COMMUNITY_IMPLEMENTATION_PLAN

> Bước 2 của handoff Community Domain. Kế hoạch triển khai theo Stage 1–6 của
> `docs/09_EXISTING_REPO_MIGRATION.md`. Lập 2026-07-29.
>
> Đọc kèm: `COMMUNITY_EXISTING_AUDIT.md` · `COMMUNITY_DB_MAPPING.md` ·
> `COMMUNITY_API_DIFF.md` · `COMMUNITY_RISK_ROLLBACK.md`.

---

## 0. Nguyên tắc xếp thứ tự

Ba tiêu chí, theo thứ tự:

1. **Việc không rủi ro làm trước** — đổi thứ chưa ai dùng thì không hỏng được gì.
2. **Việc mở khoá cho việc khác làm sớm** — grants mở khoá cho auto-join; bootstrap mở
   khoá cho mọi thứ ở client.
3. **Việc rủi ro cao làm sau cùng và làm chậm** — tách bình luận.

Điều này nghĩa là **tách bình luận nằm cuối**, dù nó là thứ chủ dự án nêu đầu tiên. Không
phải vì kém quan trọng mà vì nó là thứ duy nhất có thể **mất dữ liệu người dùng** nếu
sai, và hiện chưa có đủ dữ liệu để chứng minh migration đúng.

---

## Giai đoạn 1 — Nền, không đụng bảng

**Đầu ra:** enums, access service, resource/capability, error code, idempotency, audit.

| Việc | Chi tiết |
|---|---|
| Enums | `GroupType` (6), `VerificationLevel` (3), `JoinPolicy`, `PostPolicy`, `ContentType` (7), `LifecycleState`, `ModerationState`, `IdentityTier` (3) |
| `CommunityAccessService` | Một chỗ duy nhất trả lời "tier X, context Y có thấy/đăng/bình luận/mời/kiểm duyệt ở nhóm Z không". Mọi controller gọi vào đây |
| Capability resolver | Sinh `capabilities{}` cho group/post/comment |
| Error codes | Bảng ở API_DIFF §5. `group_not_visible` **trả 404 ra ngoài** |
| Idempotency middleware | Header `Idempotency-Key`, lưu 24h |
| Audit events | Ghi actor + reason + before/after + request_id |

**Song song, bắt buộc:** snapshot test JSON cho 11 endpoint community hiện có. Đây là
lưới an toàn cho mọi giai đoạn sau — không có nó thì không biết mình đã làm vỡ cái gì.

**Rủi ro:** thấp. Không đổi bảng, không đổi payload.

---

## Giai đoạn 2 — Nhóm và verification

**Đầu ra:** `community_groups` mở rộng + backfill + payload mới.

1. Migration additive (DB_MAPPING §2). **Giữ nguyên** `kind`/`post_policy`/`status`.
2. Backfill `group_type`/`verification_level`/`join_policy` theo bảng ánh xạ.
3. `CommunityGroupResource` trả **cả trường cũ lẫn mới**.
4. Bảng `community_group_verification_history` + service nâng tích vàng → xanh, **giữ
   nguyên group id, thành viên, bài viết** (docs 09 Stage 5).
5. Màn Filament cho BQL sửa `group_type` của 11 nhóm `private` — xem cảnh báo dưới.

⚠️ **Chỗ không tự đoán được:** 11 nhóm `private` gộp cả câu lạc bộ sở thích lẫn nhóm cư
dân tự lập, không cột nào phân biệt. Backfill mặc định `resident_interest_group` rồi để
BQL sửa tay. Đoán sai là **gán sai quyền**, không phải sai nhãn.

**Rủi ro:** thấp–vừa. Additive, app đọc trường cũ vẫn chạy.

---

## Giai đoạn 3 — Grants và membership

**Đầu ra:** multi-apartment đúng.

1. Bảng `community_membership_grants`.
2. Backfill: mỗi membership hiện có → một grant (`system_enrollment` nếu nhóm mặc định,
   `manual_join` nếu không).
3. `MembershipService`: cấp/thu hồi theo grant. **Chỉ revoke membership khi không còn
   grant active nào.**
4. Hook vào vòng đời quan hệ căn hộ: thêm quan hệ → cấp grant vào nhóm cư dân dự án đó;
   quan hệ hết hiệu lực → revoke đúng grant đó thôi.
5. Auto-enroll X2Living cho mọi `member`.
6. `left_at` (đã có) dùng cho nhãn "cư dân cũ" — **giữ bài viết**, chỉ đổi nhãn.

**Kiểm chứng bắt buộc:** tài khoản demo #6 có 2 căn ở 2 dự án. Test phải chứng minh: gỡ
một quan hệ thì mất quyền đúng một nhóm, nhóm kia còn nguyên.

**Rủi ro:** vừa. Sai ở đây là **cho người không còn quyền tiếp tục đọc nội dung nội bộ**.

---

## Giai đoạn 4 — Follow dự án

1. Bảng `user_project_follows`.
2. Backfill 5 dự án đã nối qua `projects.public_project_id`.
3. Màn Filament nối tay 22 dự án còn lại.
4. `GET/POST/DELETE me/project-follows`.
5. Kênh Quan tâm dự án hiện theo follow — **follow KHÔNG cấp quyền, không cho vào nhóm**
   (chốt 29/07). Nó chỉ là tín hiệu **ưu tiên hiển thị**.
6. Hashtag là **đầu vào**, `community_post_project_links` là **sự thật**: server phân
   giải hashtag lúc GHI bài rồi validate. Feed xếp hạng theo link, không quét chuỗi lúc
   đọc — quét lúc đọc thì vừa không index được vừa không chặn được ai gõ bừa
   `#TenDuAn` để chen vào feed vài nghìn người.

**Không dùng khớp mờ tên** — nối nhầm là cho người lạ vào kênh dự án khác. Xem
DB_MAPPING §4.

**Rủi ro:** vừa, chủ yếu ở backfill.

---

## Giai đoạn 5 — Content type và feed

1. Cột `content_type`/`source_*`/`visibility`/`published_at` + backfill `status` cho 40
   bài.
2. `community_post_project_links` + backfill từ `project_id`.
3. Source reference cho announcement/news/event/poll — **tham chiếu, không copy**.
4. `sort=ranked|latest`. Ranked giai đoạn đầu **rule-based**: ghim → mới → tương tác.
5. Tab theo `content_type`.
6. Cursor pagination — **không deep offset** (quy tắc 9).
7. `GET resident/community/bootstrap`.

**Ưu tiên bootstrap sớm trong giai đoạn này**: hiện app tự sắp bậc thang nhóm và tự đoán
tab ở `communityGroupLadderProvider` — logic nghiệp vụ nằm ở client, đúng thứ quy tắc 3
cấm.

**Rủi ro:** vừa. Payload đổi nhiều nhưng có snapshot test đỡ.

---

## Giai đoạn 6 — Ảnh về một đường

1. Backfill 37 bài từ `image_paths` sang `attachments`.
2. Đọc song song một release.
3. Drop `image_paths`.

⚠️ Bẫy đã ghi trong audit: bài **seed** đi đường json, bài **tạo qua API** đi đường
attachments. Test bằng dữ liệu seed sẽ không bao giờ chạm nhánh attachments — phải tạo
bài qua API thật trong test.

**Rủi ro:** thấp, nhưng dễ bị bỏ sót vì "trông như đã chạy".

---

## Giai đoạn 7 — Tách bình luận cộng đồng

**Làm sau cùng. Làm chậm.**

Điều kiện tiên quyết trước khi viết dòng migration nào:

- [ ] Seed **vài chục nghìn** bình luận, cây sâu 3–4 cấp, rải nhiều post/tenant.
- [ ] Test đóng băng ngữ nghĩa bảng `comments` hiện tại.
- [ ] Snapshot test payload comment.

Rồi mới: tạo bảng → dual-write → migrate chunk → đối chiếu (số lượng theo post, độ sâu
cây, thứ tự thời gian) → chuyển đọc → soak → tắt dual-write.

Bình luận **phiếu và thông báo ở lại** bảng cũ.

`community_comments.tenant_id` **bắt buộc từ migration đầu** — đây là dịp duy nhất đóng
lỗ hổng đó mà không phải migrate lần nữa.

**Rủi ro:** cao. Đây là chỗ duy nhất có thể mất dữ liệu người dùng.

---

## Giai đoạn 8 — Kiểm duyệt, thông báo, sự kiện

`community_reports` + `moderation_actions` + hàng đợi; RSVP; vote theo
`vote_identity_mode`; notification theo docs 13.

Kèm bảng `community_group_creation_policies` + màn cấu hình: **BQL và SuperAdmin thiết
lập** ai được tạo nhóm (chốt 29/07) — đây là tính năng sản phẩm, không phải cờ kỹ thuật.
Mặc định `staff_only`; BQL nào sẵn sàng trực kiểm duyệt thì tự mở.

---

## Giai đoạn 9 — Scale

`community_feed_items` **chỉ bật khi load test chứng minh cần** (docs 09 Stage 6).
Interface sẵn từ giai đoạn 5 để không phải sửa lại lần nữa.

---

## Bảng phụ thuộc

```
GĐ1 nền ──┬─→ GĐ2 nhóm ──→ GĐ3 grants ──→ GĐ8 kiểm duyệt
          ├─→ GĐ4 follow ─────────────────┘
          └─→ GĐ5 content/feed ──→ GĐ9 scale
GĐ6 ảnh   (độc lập, chen vào lúc nào cũng được)
GĐ7 bình luận (độc lập, nhưng CUỐI)
```

GĐ4 phụ thuộc GĐ2 ở chỗ kênh Quan tâm cần `group_type`.

---

## Chưa làm trong đợt này

- Chat 1-1, kết bạn — ngoài phạm vi handoff.
- Recommendation ML — giai đoạn đầu rule-based.
- Marketplace, hội nghị nhà chung cư.
- Đổi prefix `resident/community/*` → `community/*`, xem API_DIFF §1.

---

## Định nghĩa hoàn thành mỗi giai đoạn

1. Test pass, phân tích tĩnh sạch.
2. **Test cô lập tenant và access matrix** cho phần vừa làm — hiện backend chưa có test
   nào loại này, đây là khoảng trống lớn nhất.
3. Snapshot payload không đổi ngoài phần cố ý.
4. App chạy được ở cả ba tier, đổi context không rò dữ liệu.
5. Cập nhật `COMMUNITY_API_DIFF` và `COMMUNITY_DB_MAPPING` theo thực tế đã làm.
6. Ghi `DEV_JOURNAL`.
