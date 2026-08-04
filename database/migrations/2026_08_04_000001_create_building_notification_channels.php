<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-002 — CẤU HÌNH KÊNH GỬI THEO TÒA (per-building channel provisioning).
 *
 * Mỗi tòa tự khai tham số cho từng KÊNH ngoài (email/zalo/whatsapp/telegram/xspace).
 * Tách khỏi `notification_channels` (kênh của MỘT thông báo): bảng này là NƠI KHAI
 * BÁO nhà cung cấp + tham số ở cấp TÒA, để `MultiChannelNotifier` biết gửi qua đâu.
 *
 *  - status = active  : kênh đã đấu nối provider thật, gửi được (hiện chỉ email).
 *  - status = pending : "CỔNG CHỜ" — đã lưu tham số nhưng chưa đấu nối/đi live;
 *    notifier ghi 'queued'+'provider_pending' vào sổ gửi (BQL thấy ý định, chưa gửi).
 *  - enabled = false  : tòa TẮT kênh → 'suppressed'+'channel_disabled'.
 *
 * `config` (json) giữ tham số riêng từng kênh, KHÔNG cột cứng để mỗi provider tự do:
 *  - email    : {from_name, from_address, reply_to}
 *  - zalo     : {oa_id, access_token, template_id}
 *  - whatsapp : {phone_number_id, access_token, template_namespace}
 *  - telegram : {bot_token, default_chat_id}
 *  - xspace   : {workspace_id, webhook_url, api_key}   (hệ sinh thái xhub / X.Space)
 *
 * unique(building_id, channel): mỗi tòa mỗi kênh một dòng. Additive, reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_notification_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('channel');                       // email|zalo|whatsapp|telegram|xspace
            $table->boolean('enabled')->default(true);
            $table->string('status')->default('pending');    // pending (cổng chờ) | active
            $table->json('config')->nullable();              // tham số riêng từng kênh
            $table->string('note')->nullable();
            $table->timestamp('verified_at')->nullable();    // mốc đấu nối/kiểm tra provider gần nhất
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['building_id', 'channel']);
            $table->index(['tenant_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_notification_channels');
    }
};
