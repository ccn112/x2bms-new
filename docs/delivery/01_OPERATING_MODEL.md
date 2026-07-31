# Operating Model — Từ Screen-first sang Vertical Slice

## Cách làm cũ dễ gây chậm

```text
Ảnh UI
→ dựng màn
→ hardcode dữ liệu
→ phát hiện thiếu model
→ sửa migration
→ sửa API
→ sửa mobile
→ sửa lại màn
```

## Cách làm mới

```text
Một user job
→ domain contract
→ scope/quyền
→ seed scenario
→ API contract
→ Filament/mobile surface
→ test/evidence
```

## Quy tắc WIP

- Tối đa một vertical slice đang triển khai chính.
- Mỗi slice nên demo được trong 1–3 màn hoặc một hành trình ngắn.
- Không mở thêm slice khi gate tenancy, seed và API của slice hiện tại chưa đạt.
- Mỗi phase phải để lại dữ liệu seed thật và menu/route sử dụng được.

## Vai trò của Filament

Filament là productivity framework cho admin/back-office, không phải kiến trúc domain. Tốc độ đạt được bằng cách để Filament tiêu thụ model, query và action đã chuẩn hóa, thay vì đặt toàn bộ logic trong Resource.
