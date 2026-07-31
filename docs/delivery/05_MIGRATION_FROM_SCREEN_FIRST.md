# Migration Guide — Existing Screen-first Work

## Không xóa hàng loạt màn hiện có

Phân loại từng màn:

1. **Connected** — đã có query/API/model thật.
2. **Partially connected** — có model nhưng thiếu contract/scope/seed.
3. **Prototype** — hardcode/fixture không dùng production.
4. **Duplicate** — trùng route hoặc domain.
5. **Wrong surface** — dùng Filament cho consumer journey hoặc ngược lại.

## Cách xử lý

- Connected: bổ sung tests, seed và evidence.
- Partially connected: tạo domain contract và hoàn thiện slice.
- Prototype: giữ làm visual reference, gắn cờ không production.
- Duplicate: chọn source of truth, lập migration/deprecation plan.
- Wrong surface: giữ backend contract, thay surface theo decision matrix.

## Dashboard cleanup

Với mỗi card/widget:

```text
Metric name
→ business definition
→ query class
→ scope
→ refresh/caching
→ seed expected value
→ test
```

Card không truy vết được chuỗi này phải bị đánh dấu prototype.
