---
name: x2bms-admin-listing-page
description: Use when building, refactoring, standardizing, or fixing ANY /admin (BQL) or /hq listing/index page in X2-BMS Filament — title, breadcrumb, header actions, KPI row, filter bar, advanced filters, active-filter chips, column show/hide, table columns/format, frozen columns, record/bulk actions, mobile cards. Also use when a listing page has default/filter bugs (stale rows after changing filter, tabs not resetting, wrong KPI, missing breadcrumb/header). Canonical reference: app/Filament/Pages/ResidentDirectory.php + resources/views/filament/pages/resident-directory.blade.php.
---

# X2-BMS Admin Listing Page Standard (LISTING_PAGE_STANDARD)

Chuẩn hóa MỌI trang danh sách /admin (và /hq) về đúng form của **ResidentDirectory** (`/admin/residents`).
Trang mẫu chuẩn = **nguồn sự thật**; khi lệch, sửa trang khác cho khớp, KHÔNG sửa trang mẫu.

## 0. Khi nào dùng
Tạo mới / refactor / sửa lỗi trang listing. Triệu chứng "cần sửa": đổi filter mà bảng KHÔNG đổi (còn hàng cũ),
tab không reset trang, KPI sai theo filter, thiếu breadcrumb/header/nút, filter mặc định lỗi, cột không bật/tắt được.

## 1. Kiểu trang (KHÔNG dùng Filament Resource List cho /admin)
`/admin` chỉ hiển thị **custom Page** (raw CRUD nằm ở `/fila`). Trang listing chuẩn:

```php
class XxxDirectory extends \Filament\Pages\Page implements \Filament\Tables\Contracts\HasTable
{
    use \Filament\Tables\Concerns\InteractsWithTable;
    // + concern nghiệp vụ: ExportsCsv, ImportsXxxFromExcel, WritesAudit...

    protected static ?string $navigationGroup = '<nhóm theo handoff>';
    protected static ?string $navigationLabel = '<nhãn ngắn>';
    protected static ?int    $navigationSort  = <duy nhất trong nhóm>;
    protected static ?string $title = 'Danh sách <…>';
    protected static ?string $slug  = '<kebab>';
    protected string $view = 'filament.pages.xxx-directory';
}
```

## 2. BẤT BIẾN — 2 lỗi hay gặp nhất phải sửa

### 2.1. Đổi filter phải FLUSH cache bảng (Filament v4)
Filament v4 **cache record của Table**. Đổi filter mà không flush → bảng giữ hàng cũ (lỗi #1). BẮT BUỘC:
```php
private function refreshTable(): void
{
    $this->resetPage($this->getTablePaginationPageName()); // về trang 1
    $this->flushCachedTableRecords();                      // XÓA cache → query lại
    $this->resetTableSearch();
}
public function updated(string $property): void
{
    if (str_starts_with($property, 'f')) { $this->refreshTable(); } // mọi prop filter f*
}
```

### 2.2. Table query là CLOSURE (đọc filter hiện tại)
```php
->query(fn (): \Illuminate\Database\Eloquent\Builder => $this->filteredQuery())
```
KHÔNG truyền query đã eval sẵn (bị cache theo lần dựng đầu → filter không ăn).

## 3. Filter bar (KHÔNG dùng tab)
- Mỗi filter là 1 property `public ?string $fXxx` (tiền tố `f`). Search: `fSearch` (wire:model.live.debounce.400ms).
- `FILTER_LABELS` (key→nhãn) + `ADVANCED_KEYS` (filter nâng cao đưa vào panel slide).
- `applyFilters(Builder $q): Builder` DÙNG CHUNG cho **bảng + KPI + export** (một nguồn logic).
- Query scope tenant/tòa: `->whereIn('building_id', app(CurrentContext::class)->buildingIds() ?: [0])`.
- **Active chips**: `activeChips()` sinh danh sách filter đang bật → `<x-x2.filter.chip … clearFilter('fXxx')>`.
- Clear: `clearFilter($key)`, `clearAllFilters()`, `clearAdvanced()` — đều gọi `refreshTable()`.

## 4. Cột bật/tắt (column toggle)
```php
private const COLS = ['code' => 'Mã', 'full_name' => 'Họ tên', /* … */];
public array $cols = [];               // mount(): array_fill_keys(array_keys(self::COLS), true)
private function colShown(string $k): bool { return $this->cols[$k] ?? true; }
```
Mỗi TextColumn: `->visible(fn (): bool => $this->colShown('<key>'))`. Dropdown "Cột" ở `<x-slot:trailing>` của filter bar,
checkbox `wire:model="cols.<key>"` + nút Áp dụng/Đặt lại.

## 5. Breadcrumb + Header actions
```php
public function getBreadcrumbs(): array {
    return [ url('/admin') => $this->crumb('heroicon-m-home', 'Tổng quan'),
             $this->crumb('heroicon-m-<icon>', '<Trang>') ];
}
// crumb(): HtmlString icon + label (dùng svg())
protected function getHeaderActions(): array {
    return [ $this->xxxImportAction(),                                   // nếu có import Excel
        Action::make('export')->label('Xuất dữ liệu')->icon('heroicon-m-arrow-down-tray')->color('gray')->action('export'),
        Action::make('create')->label('Thêm <…>')->icon('heroicon-m-plus')->color('gold')->url(url('/fila/<res>/create')) ];
}
```
Màu chuẩn: export = `gray`, create/hành-động-chính = `gold`.

## 6. Cột bảng — quy ước format
- `code`: `->color('gray')`. Tên chính: `->color('primary')->url(detail)->description(email)`.
- Badge (tòa/loại/trạng thái): `->badge()`. Trạng thái: `->formatStateUsing(map[0])->color(map[1])`.
- Ngày: `->date('d/m/Y')->sortable()`. Ảnh: `ImageColumn::circular()->size(36)`.
- `->defaultSort('created_at', 'desc')`.
- **Freeze cột**: cột định danh đầu (mã/tên) giữ dính khi cuộn ngang — theo CSS theme bảng (x2 table), không tự thêm sticky rời rạc.

## 7. Actions
- **recordActions**: `->iconButton()` cho hành động lẻ (Xem nhanh slideOver, Sửa → /fila edit, …) + gom hành động phụ vào `ActionGroup::make([...])->icon('heroicon-m-ellipsis-vertical')`.
- **toolbarActions** (bulk): hiện THẲNG thành nút (không gom "Tác vụ hàng loạt"); dùng `->steps([...])` cho bulk phức tạp.
- Mọi action mutating: gọi Application Service/`$this->audit(...)` + `Notification::make()`.

## 8. Blade view — dùng design-system x-x2 (KHÔNG tự chế HTML)
```blade
<x-filament-panels::page>
  <x-x2.kpi-row :cols="5">
    @foreach ($kpis as $kpi)
      <x-x2.card.kpi :label="$kpi['label']" :value="$kpi['value']" :sub="$kpi['sub'] ?? null"
                     :accent="$kpi['accent']" :icon="$kpi['icon'] ?? 'heroicon-o-chart-bar'" />
    @endforeach
  </x-x2.kpi-row>

  <x-x2.filter.bar :advanced-count="$advancedCount" advanced-click="adv = true">
    <x-slot:inline>  <x-x2.filter.select field="fXxx" placeholder="Tất cả …" :options="$filterOptions['xxx']" /> …
    <x-slot:search>  <input type="search" wire:model.live.debounce.400ms="fSearch" …>
    <x-slot:trailing> {{-- dropdown "Cột" --}} </x-slot:trailing>
  </x-x2.filter.bar>

  {{-- active chips --}} <x-x2.filter.chip … :remove-wire="'clearFilter(\''.$chip['key'].'\')'" />

  {{-- Desktop: bảng Filament trong card --}}
  <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">{{ $this->table }}</div>
  {{-- Mobile: card list dùng $this->cardMeta($r) + x-x2.status-badge --}}
</x-filament-panels::page>
```
KPI phải **tính lại theo filter** (dùng `applyFilters` trên baseQuery). Component sẵn có: `x-x2.kpi-row`, `x-x2.card.kpi`,
`x-x2.filter.bar/select/chip`, `x-x2.status-badge`.

## 9. Checklist chuẩn hóa một trang (đối chiếu ResidentDirectory)
- [ ] Page HasTable + InteractsWithTable + slug + view riêng (không phải Resource List trên /admin)
- [ ] `getBreadcrumbs()` (Tổng quan → Trang) + `getHeaderActions()` (import?/export gray/create gold)
- [ ] Filter `f*` + `applyFilters()` dùng chung bảng/KPI/export + scope tòa (buildingIds)
- [ ] `updated()` → `refreshTable()` = resetPage + **flushCachedTableRecords** + resetTableSearch
- [ ] `->query(fn () => $this->filteredQuery())` (closure) + defaultSort
- [ ] Column toggle: COLS + `$cols` + `colShown` + `->visible(...)` mỗi cột
- [ ] KPI tính theo filter · active chips + clearFilter/clearAll/clearAdvanced
- [ ] recordActions iconButton + ActionGroup phụ · bulk hiện thẳng · audit + Notification
- [ ] Blade dùng x-x2.* (kpi-row/filter.bar/select/chip/status-badge) + mobile card · freeze cột định danh
- [ ] Nhóm + navigationSort DUY NHẤT trong nhóm (không trùng)

## 10. Lỗi thường gặp cần sửa khi rà
1. Đổi filter bảng không đổi → thiếu `flushCachedTableRecords()` (mục 2.1).
2. Query truyền sẵn thay vì closure → filter không ăn (mục 2.2).
3. Còn dùng tab Filament thay filter bar → chuyển sang `f*` + x-x2.filter.bar.
4. Thiếu breadcrumb/header/nút hoặc màu nút sai → mục 5.
5. Không có column toggle / KPI không đổi theo filter → mục 4/8.
6. navigationSort trùng trong nhóm → đánh lại số duy nhất.
7. Bulk gom dropdown / action không audit → mục 7.
