# Tài liệu phát triển X2-BMS backend (Phần A của GitBook)

Đây là **Phần A (nội bộ dev)** của GitBook thống nhất — book root `docs/guide/`, điều hướng ở `docs/guide/SUMMARY.md`.
Nguồn phạm vi chuẩn: `D:/Code/handoff/x2bms/_BUSINESS_MAP_20260725/` (`00_MASTER_INDEX.md`).
Quy trình cập nhật: skill **`cap-nhat-tai-lieu`**.

## Bố cục
```
docs/
├── dev/                     # Phần A — tài liệu phát triển (GitBook, quyền dev)
│   ├── 01_ui_ux/            #  Track 1 — giao diện & trải nghiệm (theo module)
│   ├── 02_features/         #  Track 2 — tính năng & hành vi người dùng
│   └── 03_data_arch/        #  Track 3 — DB / kiến trúc / seeding
├── guide/                   # book root: Phần B/C/D/E — vận hành + hướng dẫn sử dụng
│   ├── SUMMARY.md           #  điều hướng toàn GitBook
│   ├── bql/ hq/ sa/         #  hướng dẫn theo tầng (track 4, chỉ khi ✅)
│   └── images/              #  ảnh giao diện thật
├── PROGRESS_TRACKER.md      # trạng thái từng module
└── DEV_JOURNAL.md           # 📓 nhật ký phát triển — ĐỘC LẬP, ngoài GitBook
```

## Trạng thái dùng chung
✅ xong&verify · 🟢 xong chưa verify · 🟡 đang làm · ⬜ chưa làm · ❓ chưa rõ scope

## Đặt tên file theo module
- Web BQL: `BQL-00` … `BQL-09` · SuperAdmin: `WEB-UX-21` … `WEB-UX-30` · HQ: `HQ-01` … `HQ-05`
- Ví dụ: `docs/dev/02_features/BQL-03.md`, `docs/dev/03_data_arch/BQL-03.md`.

## Nguyên tắc thời điểm
- Track 1–3 (Phần A): cập nhật **liên tục trong lúc dev**.
- Track 4 (hướng dẫn sử dụng, Phần C/D/E): chỉ viết **sau khi module đã ✅ (test đạt)** + kèm ảnh.
- Nhật ký `DEV_JOURNAL.md`: ghi **mỗi phiên**, độc lập.
