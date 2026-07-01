# X2-BMS — Kế hoạch xây giao diện `/admin` (bespoke)

> Lập 2026-07-01, sau khi CSDL đã phủ 100% entity (185 bảng) + `/fila` CRUD thô đã có.
> `/admin` = màn UX sản phẩm (bespoke Pages), `/fila` = CRUD kỹ thuật mặc định.
> **Quy trình mỗi màn:** đọc kỹ contract + ảnh trong gói UX/UI-form tương ứng
> (WEB-UX-* ở `..._WEB_UX_SCREEN_DESIGN...20260629`, WEB-FORM-* / WEB-* ở
> `packages/..._WEB_ADMIN[_WEB_ACTION]_...`) → dựng theo đúng thiết kế, tái dùng
> component `x-x2.*` + pattern `StatementApprovalQueue`. Phân quyền 3 lớp bắt buộc.

## 0. Nguồn màn (tài liệu)
- **WEB-* (01–17)** = dashboard/list/detail Web Admin (gói WEB_ADMIN).
- **WEB-FORM-* (01–24)** = form tạo/sửa + luồng duyệt (gói WEB_ADMIN_WEB_ACTION).
- **WEB-UX-* (00–16)** = shell + pattern tương tác dùng chung (gói WEB_UX 20260629).

## 1. Đã xong (không làm lại)
OperationalDashboard (WEB-01), ResidentDirectory (WEB-02), ResidentDetail, ResidentCreate (WEB-FORM-02), ResidentApprovalQueue, ApartmentDirectory/Profile, VehiclesAndCards, StatementApprovalQueue (WEB-FORM-07-04), AI Engine ×4 (WEB-UX-09), Workspace switcher (WEB-UX-03), X2AI FAB, Cơ sở tri thức (≈WEB-FORM-22).

## 2. Component/pattern dùng chung cần chuẩn hoá TRƯỚC (làm 1 lần, tái dùng)
- **Kanban board** (dùng cho WEB-05 công việc, feedback queue).
- **Approval queue pattern** — đã có ở `StatementApprovalQueue`, tách thành base tái dùng (duyệt phiếu chi, data-fix, nghiệm thu…).
- **Detail + timeline pane** (WEB-UX-10 audit/activity) — panel lịch sử dùng lại nhiều màn.
- **Chart cơ bản** (donut/line/bar bằng SVG như AiCenter) — cho các dashboard tài chính/vận hành.
- **Drawer/Wizard framework** (WEB-UX-12) — form nhiều bước.

---

## 3. Kế hoạch theo GIAI ĐOẠN (ưu tiên giá trị nghiệp vụ + data đã sẵn)

### Phase 1 — Tài chính (giá trị cao nhất, data đủ) ⭐ đề xuất làm trước
| Màn | Mã | Nội dung |
|---|---|---|
| **Công nợ & thanh toán** | WEB-FORM-08 | KPI + bảng công nợ theo căn + donut kênh thu + ghi nhận thanh toán + nhắc nợ |
| **Tổng quan tài chính** | WEB-03 | Dashboard: thu/chi, tỉ lệ thu, top công nợ, biểu đồ dòng tiền |
| **Minh bạch quỹ** | WEB-11 | funds + fund_transactions: số dư, thu/chi, báo cáo minh bạch |
| **Phiếu thu/chi + Đề nghị chi** | WEB-FORM-09 | cash_vouchers + payment_requests + duyệt (reuse approval pattern) |
| **Loại phí / Kỳ phí (bespoke)** | WEB-FORM-06/07 | nâng từ /fila lên form giàu nếu cần |

### Phase 2 — Truyền thông & Phản ánh (vận hành lõi, data + 3 lớp đã sẵn)
| Màn | Mã | Nội dung |
|---|---|---|
| **Notification Center + Soạn thông báo** | WEB-04 + WEB-UX-08 + WEB-FORM-10 | soạn/lên lịch/phát hành theo 3 lớp (audiences), theo dõi đã đọc/gửi |
| **Hàng đợi phản ánh** | WEB-FORM-11 | queue + phân loại + giao việc + SLA (feedback_* đã đủ con) |
| **Chi tiết & xử lý phản ánh** | (WEB-FORM-11) | timeline comment/attachment/assignment/status history + đánh giá |

### Phase 3 — Vận hành kỹ thuật & An ninh
| Màn | Mã | Nội dung |
|---|---|---|
| **Kanban công việc** | WEB-05 + WEB-FORM-13 | work_orders kéo-thả trạng thái + checklist/attachment/signature |
| **Thiết bị & tài sản** | WEB-06 + WEB-FORM-15 | assets + maintenance_plans + meters + IoT |
| **Trung tâm an ninh** | (App-12 tương đương web) | patrol_sessions + security_incidents + sos_alerts + cameras/access_devices |

### Phase 4 — Quản trị & Dashboard đa cấp (RBAC 3 lớp thể hiện rõ)
| Màn | Mã | Nội dung |
|---|---|---|
| **Người dùng & vai trò** | WEB-08 | RBAC UI: user + gán vai trò theo scope tenant/project/building |
| **Dashboard đa cấp / đa tòa / danh mục dự án** | WEB-07/09/10 | tổng hợp theo tenant→project→building |
| **Hồ sơ / 2FA / Phiên** | WEB-UX-02 | profile, đổi mật khẩu, 2FA, phiên đăng nhập (đang link `#`) |
| **Audit UI + Command palette** | WEB-UX-10 + WEB-UX-07 | tra cứu audit_logs/activity_logs + tìm kiếm nhanh |

### Phase 5 — Nền tảng / SaaS (superadmin) & Đối tác
| Màn | Mã | Nội dung |
|---|---|---|
| **Dashboard nền tảng + MRR/ARR** | WEB-15/16 | tenants, subscriptions, doanh thu |
| **Tổng quan hỗ trợ** | WEB-17 | support_tickets |
| **Tạo công ty / Tenant + gói SaaS** | WEB-FORM-01/20 | onboarding tenant + subscription |
| **Cổng thanh toán / ngân hàng** | WEB-FORM-21 | payment_gateway_configs + integration_connections |
| **Dashboard nhà thầu / NCC** | WEB-12/13 | contractors/contracts/kpi + service_providers |

### Phase 6 — Hệ sinh thái & builder nâng cao
| Màn | Mã | Nội dung |
|---|---|---|
| **Cổng CĐT (KPI/bàn giao/bảo hành)** | WEB-FORM-19 | handover_batches + warranty_requests + KPI |
| **Đợt bàn giao / Tuần tra QR** | WEB-FORM-23/24 | handover + patrol_routes/checkpoints |
| **Form Builder UI** | WEB-UX-14 + WEB-FORM-12 | dynamic_forms kéo-thả field |
| **Report Builder / Widget** | WEB-UX-15 | báo cáo tùy biến |
| **Import/Export monitor** | WEB-UX-13 | import_jobs/export_jobs theo dõi tiến độ |
| **Trang chủ cư dân web** | WEB-14 | portal cư dân trên web |
| **Chính sách công ty / IoT config / NCC** | WEB-FORM-18/16/17 | cấu hình |

---

## 4. Ghi chú thực thi
- Mỗi màn: **đọc contract + ảnh cụ thể trước khi code** (tài liệu mô tả rất kỹ layout/section/action/enum/RBAC).
- Verify từng màn: `migrate:fresh --seed` → render HTTP 200 (headless kernel) → hành động thật (nếu có).
- Cập nhật `docs/DEV_JOURNAL.md` sau mỗi màn.
- `/fila` giữ nguyên làm CRUD kỹ thuật; `/admin` chỉ các màn UX sản phẩm.
