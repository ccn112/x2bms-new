<x-filament-panels::page>
    <div class="x2-bql-page space-y-4">
        {{-- Action bar (BQL-NOTI header: title + Lưu nháp + Gửi duyệt) --}}
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm text-slate-500">
                Soạn thông báo · tin tức · sự kiện · bình chọn theo 5 bước. Gửi ngay không bỏ qua duyệt.
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <x-filament::button color="gray" icon="heroicon-o-document-arrow-down" wire:click="saveDraft">
                    Lưu nháp
                </x-filament::button>
                <x-filament::button color="success" icon="heroicon-o-paper-airplane" wire:click="submitForApproval">
                    Gửi duyệt
                </x-filament::button>
                <x-filament::button color="gray" tag="a" icon="heroicon-o-x-mark"
                    :href="\App\Filament\Pages\NotificationCenter::getUrl()">
                    Hủy
                </x-filament::button>
            </div>
        </div>

        {{ $this->form }}
    </div>
</x-filament-panels::page>
