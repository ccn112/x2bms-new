# CHUẨN TRANG LISTING (Web BQL `/admin`)

> Chốt 2026-07-17 (commit `78d934c`). **Áp cho MỌI trang listing** trên `/admin`.
> Màn tham chiếu (reference): **Hồ sơ căn hộ** — `app/Filament/Pages/ApartmentDirectory.php`
> + `resources/views/filament/pages/apartment-directory.blade.php`.
> Tài liệu này là "chuẩn sống" — khi dựng/nâng màn listing mới, bám đúng khuôn dưới đây.

---

## 1. Nguyên tắc (bất di bất dịch)

1. **Điều hướng = submenu sidebar, KHÔNG dùng tab điều hướng.** Trang listing KHÔNG đặt hàng tab để nhảy sang trang khác (trùng lặp với sidebar, phá cấu trúc header Filament). Các trang liên quan (vd Cây căn hộ, Duyệt gắn) là **mục sidebar** trong cùng nav group.
   - Ngoại lệ: tab **lọc-bảng** (scope cùng 1 bảng, vd Tất cả/Chủ sở hữu/Người thuê) vẫn được phép — dùng `x-x2.page.tabs` với `wire="setTab"`. Đó KHÔNG phải tab điều hướng.
2. **Tiêu đề trang chỉ ở topbar.** Không lặp title trong body (đã ẩn bằng CSS — xem §5).
3. **Breadcrumb** ở header Filament: mỗi mục có **icon**, các mục cha **click được**, mục cuối = trang hiện tại.
4. **Action trang** (Nhập/Xuất/Thêm…) đặt ở **header Filament chuẩn** (`getHeaderActions()`), canh phải ngang hàng breadcrumb. Nút tạo mới nghiệp vụ = **nền vàng** (`->color('gold')`).
5. **KPI card ĐỘNG theo bộ lọc.** Khi filter ở bảng thay đổi, các số KPI tính lại theo tập đã lọc. *(Đảo quy tắc "KPI bất biến theo context" cũ — owner chốt 2026-07-17.)*
6. **Filter nghiệp vụ = X2FilterBar** (luôn hiện) + chip + drawer nâng cao, thay filter mặc định Filament; filter tác động **thật** vào query / phân trang / export.
7. **Responsive:** desktop = bảng đầy đủ; `<768px` = **card/list**, giữ phân trang Filament.

---

## 2. Cấu trúc (anatomy)

```
Topbar (Filament):      [☰] Tiêu đề trang        [ô tìm kiếm]        [context] [🔔] [avatar]
── Header Filament ─────────────────────────────────────────────────────────────────────
Breadcrumb (icon + click)  🏠 Tổng quan › 👥 Nhóm › 🏢 Trang hiện tại      [Nhập][Xuất][+ Tạo(vàng)]
── Body (.x2-bql-page) ───────────────────────────────────────────────────────────────────
KPI row (5 card, ĐỘNG theo filter)
Filter bar:  [select][select]… [🔍 search…………]  [Bộ lọc nâng cao]
Chip:  Tòa A ×   Đã ở ×                                   Xóa tất cả
Bảng Filament (desktop)  /  Card list (mobile <768px)  — cùng nguồn getTableRecords()
```

Thứ tự trong slot body: **KPI → filter bar → chip → bảng/card**. Bọc toàn bộ trong `<div class="x2-bql-page">` (lớp density scoped).

---

## 3. Khung code (bám khuôn ApartmentDirectory)

### 3.1 Page class

```php
class XyzDirectory extends Page implements HasTable
{
    use InteractsWithTable;

    // --- filter state (public prop) ---
    public ?string $fA = null;      // các select inline
    public ?string $fSearch = null;
    // ... prop filter nâng cao ...

    /** Đổi filter → về trang 1 + XÓA cache records (resetPage KHÔNG xoá cache). */
    private function refreshTable(): void
    {
        $this->resetPage($this->getTablePaginationPageName()); // KHÔNG dùng resetTablePage() — không tồn tại
        $this->flushCachedTableRecords();
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'f')) {
            $this->refreshTable();
        }
    }

    /** Query đã áp toàn bộ filter — dùng CHUNG cho bảng + KPI + export. */
    private function filteredQuery(): Builder
    {
        return Model::query()
            ->whereIn('building_id', $this->buildingIds())   // scope context bắt buộc
            ->when($this->fA, fn ($q, $v) => $q->where('col', $v))
            ->when($this->fSearch, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('code', 'like', "%{$v}%")->orWhereHas(...)));
    }

    /** Breadcrumb: icon từng mục, 2 mục đầu click được. */
    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => $this->crumb('heroicon-m-home', 'Tổng quan'),
            url('/admin/...') => $this->crumb('heroicon-m-...', 'Nhóm'),
            $this->crumb('heroicon-m-...', 'Trang hiện tại'),   // key int → không link
        ];
    }

    private function crumb(string $icon, string $label): HtmlString
    {
        return new HtmlString('<span class="inline-flex items-center gap-1">'
            .svg($icon, 'h-4 w-4 shrink-0 opacity-70')->toHtml()
            .'<span>'.e($label).'</span></span>');
    }

    /** Action trang ở header Filament. Nút tạo = nền vàng. */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')->label('Nhập dữ liệu')->icon('heroicon-m-arrow-up-tray')->color('gray')->action('notifyImport'),
            Action::make('export')->label('Xuất dữ liệu')->icon('heroicon-m-arrow-down-tray')->color('gray')->action('export'),
            Action::make('create')->label('Thêm …')->icon('heroicon-m-plus')->color('gold')->url(url('/fila/.../create')),
        ];
    }

    protected function getViewData(): array
    {
        $base = fn () => $this->filteredQuery();   // KPI ĐỘNG theo filter (dùng chung filteredQuery)
        $total = $base()->count();
        // ... các KPI khác tính từ (clone $base()) ...
        return ['kpis' => [...], 'filterOptions' => ..., 'activeChips' => ...];
    }

    public function table(Table $table): Table
    {
        return $table
            // BẮT BUỘC closure — KHÔNG truyền Builder tĩnh (Filament cache Table → filter đóng băng).
            ->query(fn (): Builder => $this->filteredQuery())
            ->columns([...])
            // KHÔNG dùng ->filters([...]) mặc định (X2FilterBar tự lo) + KHÔNG ->searchable() (search riêng).
            ->paginated([10, 25, 50, 100]);
    }
}
```

### 3.2 Blade (slot body)

```blade
<x-filament-panels::page>
    <div class="x2-bql-page">
        {{-- KPI --}}
        <x-x2.kpi-row :cols="5"> @foreach ($kpis as $kpi) <x-x2.card.kpi class="x2-kpi" ... /> @endforeach </x-x2.kpi-row>

        <div x-data="{ adv: false }" class="mt-4">
            {{-- Filter bar: inline select (ẩn trên mobile) + search + nút drawer --}}
            <x-x2.filter.bar :advanced-count="$advancedCount" advanced-click="adv = true">
                <x-slot:inline> <x-x2.filter.select field="fA" :options="$filterOptions['a']" /> … </x-slot:inline>
                <x-slot:search> <input type="search" wire:model.live.debounce.400ms="fSearch" … /> </x-slot:search>
            </x-x2.filter.bar>

            {{-- Chip + Xóa tất cả (chỉ hiện khi có filter) --}}
            @if (count($activeChips)) … <button wire:click="clearAllFilters">Xóa tất cả</button> @endif

            {{-- Bảng desktop + card mobile (cùng getTableRecords()) --}}
            <div class="mt-3 overflow-hidden rounded-xl border …">
                {{ $this->table }}
                <div class="x2-mobile-cards …"> @forelse ($this->getTableRecords() as $r) … @endforelse </div>
            </div>

            {{-- Drawer bộ lọc nâng cao (mobile chứa cả select chính qua md:hidden) --}}
            <div x-show="adv" x-cloak class="fixed inset-0 z-40" …> … </div>
        </div>
    </div>
</x-filament-panels::page>
```

### 3.3 Component dùng chung
- `x-x2.filter.bar` (slot `inline`, `search`; prop `advancedClick` mở drawer Alpine, `advancedCount`).
- `x-x2.filter.select` (field + options + placeholder, `wire:model.live`).
- `x-x2.filter.chip` (label + value + `removeWire`).
- `x-x2.kpi-row` / `x-x2.card.kpi`.
- `x-x2.page.tabs` — **chỉ dùng cho tab lọc-bảng**, không phải tab điều hướng.

### 3.4 Toggle cột (dropdown "Cột") — chốt 2026-07-17
Đặt nút **"Cột"** trong slot `trailing` của `x-x2.filter.bar` (cạnh "Bộ lọc nâng cao"), KHÔNG dùng `->toggleable()` của Filament (để không hiện trigger gốc trong toolbar).
```php
private const COLS = ['code' => 'Mã căn', ... ]; // key => nhãn
public array $cols = [];
public function mount(): void { $this->cols = array_fill_keys(array_keys(self::COLS), true); } // BẮT BUỘC init true → checkbox khớp cột đang hiện
public function applyCols(): void {}                 // nút "Áp dụng" (deferred wire:model commit khi bấm)
public function resetCols(): void { $this->cols = array_fill_keys(array_keys(self::COLS), true); }
private function colShown(string $k): bool { return $this->cols[$k] ?? true; }
// mỗi cột: ->visible(fn () => $this->colShown('code'))
// getViewData: 'columnToggle' => self::COLS
```
Blade: checkbox `wire:model="cols.KEY"` (**deferred, KHÔNG .live**) + nút "Áp dụng" (`wire:click="applyCols"`) + "Đặt lại" (`wire:click="resetCols"`). Init `cols=true` nên checkbox phản ánh đúng cột đang bật.

### 3.5 Bulk action = nút inline (không dropdown)
`->toolbarActions([...])` truyền **thẳng `BulkAction`**, KHÔNG bọc `BulkActionGroup` → các tác vụ hàng loạt hiện thành nút cạnh nhau trong thanh chọn (không gom dropdown "Tác vụ hàng loạt").

---

## 4. CSS chuẩn (đã có trong `resources/css/filament/admin/theme.css`)

- **Header 1 hàng + căn giữa** (title đã lên topbar): `.fi-main .fi-header-has-breadcrumbs { flex-row; items-center; justify-between }` + bỏ `margin-top` của `.fi-header-actions-ctn` (md+).
- **Ẩn title body** (bền qua Livewire morph, KHÔNG dựa JS): `.fi-main .fi-header-has-breadcrumbs .fi-header-heading { display:none }`.
- **Giảm margin header ~2/3**: `.fi-page-header-main-ctn { row-gap/padding-block: 12px }` (mặc định 32px).
- **Density BQL**: bọc `.x2-bql-page` (KPI compact, table dense th44/td8) — chỉ sửa `.fi-*` **trong** `.x2-bql-page`.
- **Mobile card**: `@media(max-width:767px)` ẩn `.fi-ta-content`+`.fi-ta-header-ctn`, hiện `.x2-mobile-cards`; GIỮ `.fi-pagination`.
- Màu **gold** đăng ký ở `AdminPanelProvider::colors(['gold' => Color::hex('#d5a331')])`.
- **Freeze cột (MẶC ĐỊNH mọi bảng listing, chốt 2026-07-17):** ô chọn + cột `code` sticky trái, cột thao tác (`td:last-child` + `.fi-ta-actions-header-cell`) sticky phải — khi cuộn ngang thì mã + thao tác đứng yên. Scoped `.x2-bql-page`; nền trắng nên **bỏ `->striped()`**. **Quy ước: cột đầu bảng listing đặt tên `code`** để được freeze-trái; bảng không có `code` thì thêm selector nhắm cột đó. (KHÔNG làm toggle per-user — freeze là default; muốn loại trừ 1 bảng thì override riêng.)

Các rule header/spacing áp cho **mọi trang có breadcrumb** → dùng `getBreadcrumbs()` là kế thừa đồng bộ.

---

## 5. BẪY BẮT BUỘC NHỚ (đã trả giá)

| Bẫy | Hậu quả | Đúng |
|---|---|---|
| `->query($this->filteredQuery())` (Builder tĩnh) | Filament cache Table → filter **đóng băng**, bảng "vẫn hiện mặc định" | `->query(fn () => $this->filteredQuery())` (closure) |
| Chỉ `resetPage()` khi đổi filter | Cache records không xoá → bảng cũ | `resetPage(...)` **+ `flushCachedTableRecords()`** |
| `resetTablePage()` | **Không tồn tại** ở Filament bản này → 500 | `resetPage($this->getTablePaginationPageName())` |
| Ẩn title bằng JS | Livewire morph sau mỗi lần lọc → title hiện lại | Ẩn bằng **CSS** (§4) |
| Column closure đặt tên khác `$state` | 500 "unresolvable" | Param scalar column PHẢI tên `$state` |
| Method Page trùng tên Livewire (`mount/render/updated`…) | Fatal | Đặt tên khác |

---

## 6. Checklist khi dựng màn listing mới

1. Sidebar: đăng ký page trong nav group đúng (KHÔNG thêm tab điều hướng).
2. `getBreadcrumbs()` có icon + click được (mục cha), title chỉ topbar.
3. `getHeaderActions()` — action trang, nút tạo `->color('gold')`.
4. `filteredQuery()` scope `building_id` (context) + tất cả filter; `table()` dùng **closure**.
5. `updated()` + các nút clear gọi `refreshTable()` (resetPage + flush cache).
6. KPI trong `getViewData()` tính từ `filteredQuery()` (động theo filter).
7. Filter bar (inline select + search + chip + drawer) + card mobile (`.x2-mobile-cards`).
8. Bọc `.x2-bql-page`; export dùng `filteredQuery()` + ghi audit.
9. Verify: `php -l` → `npm run build` → render 200 → **Livewire::test set filter, kiểm số dòng/KPI đổi đúng** (đường Livewire, KHÔNG chỉ gọi query trực tiếp — bẫy §5 chỉ lộ qua đường Livewire).

---

## 7. Con trỏ
- Reference: `ApartmentDirectory.php` + `apartment-directory.blade.php` (commit `78d934c`).
- Component: `resources/views/components/x2/filter/*`, `x2/page/tabs.blade.php`, `x2/kpi-row`, `x2/card/kpi`.
- CSS: `resources/css/filament/admin/theme.css` (block `.x2-bql-page`, `.fi-header-has-breadcrumbs`, `.x2-mobile-cards`).
- Liên quan: `BQL_MASTER_BUILD_PLAN_20260703.md`, `DS01_EXECUTION_PLAN.md`.
