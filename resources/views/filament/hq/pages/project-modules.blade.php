<x-filament-panels::page>
@php
    $modLabel = ['x2ai'=>'X2 AI','contractor_library'=>'Thư viện nhà thầu','report_library'=>'Thư viện báo cáo','rag'=>'Tri thức RAG','supplier_library'=>'Thư viện nhà cung cấp','kb_inheritance'=>'Kế thừa KB','public_project'=>'Dự án công khai','prompt_guardrail'=>'Guardrail','tenant_core'=>'Lõi hệ thống','subscription'=>'Thuê bao','global_account'=>'Tài khoản toàn cục','resident_binding'=>'Liên kết cư dân','rbac'=>'Phân quyền','master_data'=>'Dữ liệu nền','platform_content'=>'Nội dung','notification'=>'Thông báo','feedback'=>'Phản ánh','forms'=>'Biểu mẫu','fee_setup'=>'Thiết lập phí','billing'=>'Hóa đơn','payment'=>'Thanh toán','work_order'=>'Công việc','maintenance'=>'Bảo trì','sla'=>'SLA','asset'=>'Tài sản','security'=>'An ninh','patrol'=>'Tuần tra','dashboard'=>'Dashboard','document_template'=>'Biểu mẫu tài liệu'];
    $statusMeta = ['enabled'=>['Đang hoạt động','bg-emerald-50 text-emerald-700'],'disabled'=>['Đã tắt','bg-slate-100 text-slate-500'],'pending'=>['Chờ duyệt','bg-amber-50 text-amber-700'],'locked'=>['Khóa (nâng gói)','bg-slate-100 text-slate-500']];
    $srcMeta = ['package'=>'Theo gói','addon'=>'Add-on','inherited'=>'Kế thừa','manual_override'=>'Ghi đè'];
    $metricCards = [['Tổng module',$metrics['total'],'blue'],['Đang bật',$metrics['enabled'],'green'],['Add-on',$metrics['addon'],'violet'],['Chờ duyệt',$metrics['pending'],'amber'],['Khóa',$metrics['locked'],'slate']];
    $mc = ['blue'=>'text-blue-600','green'=>'text-emerald-600','violet'=>'text-violet-600','amber'=>'text-amber-600','slate'=>'text-slate-500'];
@endphp
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-title text-2xl font-bold text-slate-900">Trạng thái module — {{ $project->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">Gói hiện tại: <span class="font-medium text-slate-700">{{ $planName ?? '—' }}</span></p>
        </div>
        <a href="{{ url('/hq/projects/'.$project->id) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">← Chi tiết dự án</a>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
        @foreach ($metricCards as [$label, $value, $c])
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-sm text-slate-500">{{ $label }}</div>
                <div class="mt-1 text-2xl font-bold {{ $mc[$c] }}">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_300px]">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr><th class="px-4 py-3">Module</th><th class="px-4 py-3">Nguồn</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3">Người duyệt</th><th class="px-4 py-3">Hiệu lực từ</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($rows as $r)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $modLabel[$r['key']] ?? $r['key'] }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $srcMeta[$r['source']] ?? $r['source'] }}</td>
                                <td class="px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $statusMeta[$r['status']][1] ?? '' }}">{{ $statusMeta[$r['status']][0] ?? $r['status'] }}</span></td>
                                <td class="px-4 py-3 text-slate-500">{{ $r['approver'] }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $r['from'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Chưa có module.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Yêu cầu chờ duyệt</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($pending as $p)
                        <li class="flex items-center justify-between">
                            <span class="text-slate-600">{{ $modLabel[$p['key']] ?? $p['key'] }}</span>
                            <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Chờ duyệt</span>
                        </li>
                    @empty
                        <li class="text-slate-400">Không có yêu cầu chờ duyệt.</li>
                    @endforelse
                </ul>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Nguồn gói</h3>
                <p class="mt-2 text-sm text-slate-500">Module bật theo gói <span class="font-semibold text-slate-700">{{ $planName ?? '—' }}</span>; add-on/ghi đè được duyệt riêng và ghi audit.</p>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
