<x-filament-panels::page>
    {{-- Ô soạn HTML nội dung cao tối thiểu 500px (chốt owner). Scope trong .x2-bql-page
         để không đụng rich editor ở panel/màn khác (spec 05 §3 CSS scoped). --}}
    <style>
        .x2-bql-page .fi-fo-rich-editor .tiptap,
        .x2-bql-page .fi-fo-rich-editor [contenteditable="true"],
        .x2-bql-page .fi-fo-rich-editor .fi-fo-rich-editor-editor,
        .x2-bql-page .fi-fo-rich-editor .prose { min-height: 500px; }
    </style>

    <div class="x2-bql-page space-y-4">
        {{-- Action bar (BQL-NOTI header: title + Lưu nháp + Gửi duyệt) --}}
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm text-slate-500">
                Soạn thông báo · tin tức · sự kiện · bình chọn theo 5 bước. Gửi ngay không bỏ qua duyệt.
            </p>
            {{-- DS-03 thứ bậc nút: Phụ (Lưu nháp) → CTA gold (Gửi duyệt) → Hủy (ghost). --}}
            <div class="flex flex-wrap items-center gap-1.5">
                <x-x2.btn variant="outline" size="sm" icon="heroicon-m-document-arrow-down" wire:click="saveDraft" wire:loading.attr="disabled">
                    Lưu nháp
                </x-x2.btn>
                <x-x2.btn variant="gold" size="sm" icon="heroicon-m-paper-airplane" wire:click="submitForApproval" wire:loading.attr="disabled">
                    Gửi duyệt
                </x-x2.btn>
                <x-x2.btn variant="ghost" size="sm" as="a" icon="heroicon-m-x-mark"
                    :href="\App\Filament\Pages\NotificationCenter::getUrl()">
                    Hủy
                </x-x2.btn>
            </div>
        </div>

        {{ $this->form }}
    </div>
</x-filament-panels::page>
