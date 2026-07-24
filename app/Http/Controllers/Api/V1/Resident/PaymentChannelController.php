<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\PaymentChannel;
use App\Models\Statement;
use App\Services\Resident\ResidentContextService;
use App\Services\Resident\VietQrService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cổng thanh toán cho cư dân: liệt kê cổng đang bật (theo tenant + dự án) và tạo
 * "intent" thanh toán cho 1 hoá đơn.
 *
 * - VietQR: sinh QR (số tiền + nội dung từ hoá đơn) + list app ngân hàng deeplink.
 *   KHÔNG cần credential (chỉ tài khoản người nhận trong config cổng).
 * - VNPay/MoMo: owner enable + cấu hình per tenant/dự án; khoá bí mật ở ENV. Chưa đủ
 *   cấu hình → trả `status=not_configured` (app ẩn/nút mờ).
 * Xem docs/api/RESIDENT_API_REFERENCE.md.
 */
class PaymentChannelController extends ApiController
{
    public function __construct(
        private readonly ResidentContextService $context,
        private readonly VietQrService $vietqr,
    ) {
    }

    /** Cổng đang bật cho ngữ cảnh cư dân (project riêng ∪ toàn tenant). */
    private function enabledChannels(Request $request)
    {
        $projectIds = $this->context->projectIds($request->user(), $request->header('X-Context-Id'));
        $tenantIds = $this->context->tenantIds($request->user(), $request->header('X-Context-Id'));

        if (empty($tenantIds)) {
            return collect();
        }

        return PaymentChannel::query()
            ->where('is_enabled', true)
            ->whereIn('tenant_id', $tenantIds)
            ->where(function ($q) use ($projectIds) {
                $q->whereNull('project_id');
                if (! empty($projectIds)) {
                    $q->orWhereIn('project_id', $projectIds);
                }
            })
            ->orderBy('sort')
            ->get()
            // Ưu tiên bản ghi riêng dự án hơn bản ghi toàn tenant khi trùng channel.
            ->groupBy('channel')
            ->map(fn ($grp) => $grp->sortByDesc('project_id')->first())
            ->values();
    }

    /** GET /resident/payment-methods — cổng đang bật (app dựng bộ chọn). */
    public function index(Request $request): JsonResponse
    {
        $channels = $this->enabledChannels($request)->map(function (PaymentChannel $c) {
            $out = [
                'channel' => $c->channel,
                'display_name' => $c->display_name ?? $this->defaultName($c->channel),
                'sort' => $c->sort,
            ];
            if ($c->channel === 'vietqr') {
                $cfg = $c->config ?? [];
                $out['bank'] = [
                    'code' => $cfg['bank_code'] ?? null,
                    'account_name' => $cfg['account_name'] ?? null,
                ];
            }

            return $out;
        });

        return ApiResponse::success($channels->all());
    }

    /** POST /resident/payments/intent { statement_id, channel } */
    public function intent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'statement_id' => ['required', 'integer'],
            'channel' => ['required', 'string', 'in:vietqr,vnpay,momo'],
        ]);

        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));
        $statement = Statement::query()
            ->whereIn('apartment_id', $apartmentIds)
            ->find($data['statement_id']);
        if ($statement === null) {
            return ApiResponse::error('not_found', 'Không tìm thấy hoá đơn.', 404);
        }

        $outstanding = bcsub((string) ($statement->total_amount ?? '0'), (string) ($statement->paid_amount ?? '0'), 2);
        if (bccomp($outstanding, '0', 2) <= 0) {
            return ApiResponse::error('already_paid', 'Hoá đơn đã thanh toán đủ.', 422);
        }

        $channel = $this->enabledChannels($request)->firstWhere('channel', $data['channel']);
        if ($channel === null) {
            return ApiResponse::error('channel_unavailable', 'Cổng thanh toán chưa được bật cho dự án này.', 422);
        }

        // Nội dung chuyển khoản = mã hoá đơn (fallback id nếu chưa có mã).
        $content = 'TT '.($statement->code ?: 'HD'.$statement->id);

        return match ($data['channel']) {
            'vietqr' => $this->vietqrIntent($channel, $outstanding, $content, $statement),
            'vnpay', 'momo' => $this->gatewayIntent($channel, $outstanding, $content, $statement),
        };
    }

    private function vietqrIntent(PaymentChannel $channel, string $amount, string $content, Statement $statement): JsonResponse
    {
        $cfg = $channel->config ?? [];
        if (empty($cfg['bank_bin']) || empty($cfg['account_no'])) {
            return ApiResponse::error('channel_not_configured', 'Cổng VietQR chưa cấu hình tài khoản nhận.', 422);
        }

        $payload = $this->vietqr->build($cfg, $amount, $content);
        $payload['channel'] = 'vietqr';
        $payload['statement_id'] = $statement->id;
        $payload['statement_code'] = $statement->code;
        $payload['amount'] = $amount; // giữ chuỗi decimal đầy đủ cho hiển thị

        return ApiResponse::success($payload);
    }

    private function gatewayIntent(PaymentChannel $channel, string $amount, string $content, Statement $statement): JsonResponse
    {
        // VNPay/MoMo: cần credential ở ENV (config/services.php) + xây signer riêng.
        // Chưa cấu hình → báo not_configured để app xử lý mềm; owner bật sau.
        $key = $channel->channel; // vnpay | momo
        $secretConfigured = ! empty(config("services.$key.secret")) || ! empty(config("services.$key.hash_secret"));

        if (! $secretConfigured) {
            return ApiResponse::success([
                'channel' => $key,
                'status' => 'not_configured',
                'message' => 'Cổng '.strtoupper($key).' đang chờ cấu hình. Vui lòng dùng VietQR.',
                'statement_id' => $statement->id,
                'amount' => $amount,
            ]);
        }

        // TODO(owner-enable): dựng redirect_url ký theo chuẩn VNPay/MoMo khi có credential.
        return ApiResponse::success([
            'channel' => $key,
            'status' => 'pending',
            'redirect_url' => null,
            'statement_id' => $statement->id,
            'amount' => $amount,
            'content' => $content,
        ]);
    }

    private function defaultName(string $channel): string
    {
        return match ($channel) {
            'vietqr' => 'Chuyển khoản VietQR',
            'vnpay' => 'VNPay',
            'momo' => 'Ví MoMo',
            default => ucfirst($channel),
        };
    }
}
