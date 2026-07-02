<x-filament-panels::page>
<div x-data="{ fields: [{label:'Họ và tên', type:'text'}, {label:'Ngày', type:'date'}, {label:'Nội dung', type:'textarea'}] }" class="grid grid-cols-1 gap-6 xl:grid-cols-[240px_1fr_280px]">
    {{-- Field palette --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="font-title text-xs font-bold uppercase tracking-wide text-slate-400">Trường dữ liệu</h3>
        <div class="mt-3 space-y-2">
            @foreach (['text'=>'Văn bản','number'=>'Số','date'=>'Ngày','select'=>'Lựa chọn','checkbox'=>'Checkbox','file'=>'Tệp đính kèm','textarea'=>'Đoạn văn'] as $t=>$l)
                <button @click="fields.push({label:'Trường mới', type:'{{ $t }}'})" class="flex w-full items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-left text-sm text-slate-600 hover:border-blue-300 hover:bg-blue-50/40">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>{{ $l }}
                </button>
            @endforeach
        </div>
    </div>
    {{-- Canvas --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="font-title text-lg font-bold text-slate-900">Trình tạo biểu mẫu động</h1>
            <button class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white">Lưu biểu mẫu</button>
        </div>
        <input class="w-full rounded-lg border-slate-200 text-lg font-semibold" placeholder="Tên biểu mẫu...">
        <div class="mt-4 space-y-3">
            <template x-for="(f, i) in fields" :key="i">
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3">
                    <div class="flex items-center justify-between">
                        <input x-model="f.label" class="w-2/3 rounded border-slate-200 text-sm font-medium">
                        <div class="flex items-center gap-2">
                            <span class="rounded bg-white px-2 py-0.5 text-xs text-slate-500" x-text="f.type"></span>
                            <button @click="fields.splice(i,1)" class="text-slate-400 hover:text-rose-600">✕</button>
                        </div>
                    </div>
                    <div class="mt-2 h-8 rounded border border-dashed border-slate-200 bg-white"></div>
                </div>
            </template>
            <p x-show="!fields.length" class="py-8 text-center text-sm text-slate-400">Kéo/chọn trường từ bảng bên trái để bắt đầu.</p>
        </div>
    </div>
    {{-- Settings --}}
    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Thiết lập</h3>
            <div class="mt-3 space-y-3 text-sm">
                <label class="block"><span class="text-slate-500">Nhóm biểu mẫu</span><select class="mt-1 w-full rounded-lg border-slate-200 text-sm"><option>QA/QC</option><option>Kỹ thuật</option><option>Tài chính - Kế toán</option></select></label>
                <label class="block"><span class="text-slate-500">Phạm vi áp dụng</span><select class="mt-1 w-full rounded-lg border-slate-200 text-sm"><option>Toàn công ty</option><option>Theo dự án</option></select></label>
                <label class="flex items-center gap-2"><input type="checkbox" checked class="rounded border-slate-300 text-blue-600"><span class="text-slate-600">Bật quy trình phê duyệt</span></label>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
