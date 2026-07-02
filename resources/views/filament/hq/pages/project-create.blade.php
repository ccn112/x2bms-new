<x-filament-panels::page>
<div x-data="{ step: 1, steps: ['Thông tin cơ bản','Quy mô & cấu trúc','Liên hệ & BQL','Gói dịch vụ','Xác nhận'] }" class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_320px]">
    <div class="space-y-5">
        {{-- Stepper --}}
        <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <template x-for="(s, i) in steps" :key="i">
                <div class="flex items-center gap-2">
                    <span class="grid h-7 w-7 place-items-center rounded-full text-xs font-bold"
                          :class="step >= i+1 ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-400'" x-text="i+1"></span>
                    <span class="text-sm font-medium" :class="step >= i+1 ? 'text-slate-800' : 'text-slate-400'" x-text="s"></span>
                    <span x-show="i < steps.length-1" class="mx-1 h-px w-6 bg-slate-200"></span>
                </div>
            </template>
        </div>

        <form wire:submit="save" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            {{-- Step 1 --}}
            <div x-show="step === 1" class="space-y-4">
                <h3 class="font-title text-base font-bold text-slate-900">Thông tin cơ bản</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label class="block"><span class="text-sm font-medium text-slate-600">Mã dự án *</span>
                        <input wire:model="code" class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="VD: SG-025">
                        @error('code') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror</label>
                    <label class="block"><span class="text-sm font-medium text-slate-600">Tên dự án *</span>
                        <input wire:model="name" class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="VD: Sunshine Riverside">
                        @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror</label>
                    <label class="block"><span class="text-sm font-medium text-slate-600">Loại hình</span>
                        <select wire:model="type" class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach (['Chung cư cao cấp','Chung cư','Tổ hợp TM & VP','Văn phòng','Biệt thự','Khu đô thị'] as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select></label>
                    <label class="block"><span class="text-sm font-medium text-slate-600">Thành phố</span>
                        <input wire:model="city" class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"></label>
                </div>
                <label class="block"><span class="text-sm font-medium text-slate-600">Địa chỉ</span>
                    <input wire:model="address" class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"></label>
            </div>

            {{-- Step 2 --}}
            <div x-show="step === 2" x-cloak class="space-y-4">
                <h3 class="font-title text-base font-bold text-slate-900">Quy mô & cấu trúc</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <label class="block"><span class="text-sm font-medium text-slate-600">Số tòa</span>
                        <input type="number" wire:model="building_count" class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"></label>
                    <label class="block"><span class="text-sm font-medium text-slate-600">Số căn hộ</span>
                        <input type="number" wire:model="apartment_count" class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"></label>
                    <label class="block"><span class="text-sm font-medium text-slate-600">Diện tích (m²)</span>
                        <input type="number" wire:model="land_area_sqm" class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"></label>
                </div>
            </div>

            {{-- Step 3 --}}
            <div x-show="step === 3" x-cloak class="space-y-4">
                <h3 class="font-title text-base font-bold text-slate-900">Liên hệ & BQL</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label class="block"><span class="text-sm font-medium text-slate-600">Trưởng BQL</span>
                        <input wire:model="manager_name" class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"></label>
                    <label class="block"><span class="text-sm font-medium text-slate-600">Hotline</span>
                        <input wire:model="hotline" class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"></label>
                </div>
            </div>

            {{-- Step 4 --}}
            <div x-show="step === 4" x-cloak class="space-y-4">
                <h3 class="font-title text-base font-bold text-slate-900">Gói dịch vụ</h3>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach ($this->getPlans() as $plan)
                        <label class="cursor-pointer rounded-xl border-2 p-4 transition"
                               :class="$wire.plan_code === '{{ $plan->code }}' ? 'border-blue-500 bg-blue-50/50' : 'border-slate-200'">
                            <input type="radio" wire:model.live="plan_code" value="{{ $plan->code }}" class="sr-only">
                            <div class="font-title font-bold text-slate-900">{{ $plan->name }}</div>
                            <div class="mt-1 text-sm text-slate-500">{{ number_format((int) ($plan->monthly_base_price ?? 0)) }} đ/tháng</div>
                        </label>
                    @endforeach
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label class="block"><span class="text-sm font-medium text-slate-600">Ngày bắt đầu gói *</span>
                        <input type="date" wire:model="started_at" class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"></label>
                    <label class="mt-6 flex items-center gap-2"><input type="checkbox" wire:model="auto_renew" class="rounded border-slate-300 text-blue-600"><span class="text-sm text-slate-600">Tự động gia hạn</span></label>
                </div>
            </div>

            {{-- Step 5 --}}
            <div x-show="step === 5" x-cloak class="space-y-3">
                <h3 class="font-title text-base font-bold text-slate-900">Xác nhận</h3>
                <p class="text-sm text-slate-500">Kiểm tra thông tin ở bảng tóm tắt bên phải rồi bấm "Tạo dự án".</p>
            </div>

            {{-- Nav buttons --}}
            <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4">
                <button type="button" x-show="step > 1" @click="step--" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Quay lại</button>
                <span x-show="step === 1"></span>
                <button type="button" x-show="step < 5" @click="step++" class="ml-auto rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Tiếp tục</button>
                <button type="submit" x-show="step === 5" x-cloak class="ml-auto rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Tạo dự án</button>
            </div>
        </form>
    </div>

    {{-- Live summary --}}
    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Tóm tắt dự án</h3>
            <dl class="mt-3 space-y-2.5 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Mã</dt><dd class="font-medium text-slate-800" x-text="$wire.code || '—'"></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Tên</dt><dd class="font-medium text-slate-800" x-text="$wire.name || '—'"></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Loại hình</dt><dd class="font-medium text-slate-800" x-text="$wire.type"></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Quy mô</dt><dd class="font-medium text-slate-800"><span x-text="$wire.building_count"></span> tòa · <span x-text="$wire.apartment_count"></span> căn</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Trưởng BQL</dt><dd class="font-medium text-slate-800" x-text="$wire.manager_name || '—'"></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Gói</dt><dd class="font-medium text-slate-800" x-text="$wire.plan_code"></dd></div>
            </dl>
        </div>
    </div>
</div>
</x-filament-panels::page>
