<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Apartment;
use App\Models\AppScreenEvent;
use App\Models\AppScreenReport;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Nhận nhật ký màn hình (theo lô) và báo lỗi từ app.
 *
 * ## Auth là TUỲ CHỌN — có chủ ý
 * Chủ dự án chốt 30/07: người dùng ẩn danh cũng phải đếm được, giữ `device_id` để
 * ghép với người dùng định danh sau này. Nếu bắt buộc đăng nhập thì mất sạch dữ
 * liệu của nhóm chưa đăng nhập — đúng nhóm cần biết nhất khi hỏi "vì sao tải app
 * rồi không dùng". Cùng cơ chế với `POST ai/chat` (auth optional + X-Device-Id).
 *
 * ## Ghi bằng insert THÔ, không dùng Eloquent từng dòng
 * Một lô có thể vài trăm sự kiện; tạo vài trăm model + vài trăm INSERT là vô nghĩa.
 * Dùng một `insert()` nhiều dòng.
 */
class TelemetryController extends ApiController
{
    /**
     * POST /api/v1/telemetry/screen-views — nhận MỘT LÔ sự kiện.
     *
     * Luôn trả 202 kèm số dòng đã nhận/bỏ. KHÔNG trả lỗi khi vài sự kiện trong lô
     * bị hỏng: nhật ký là dữ liệu phụ, làm app phải retry cả lô vì một dòng xấu chỉ
     * tốn pin và 4G của cư dân.
     */
    public function screenViews(Request $request): JsonResponse
    {
        $deviceId = trim((string) $request->header('X-Device-Id'));
        if ($deviceId === '') {
            return ApiResponse::error('device_id_required',
                'Thiếu X-Device-Id.', 422);
        }

        $max = (int) config('telemetry.max_batch_size', 200);
        $events = $request->input('events');
        if (! is_array($events)) {
            return ApiResponse::error('invalid_batch', 'Trường `events` phải là mảng.', 422);
        }
        $events = array_slice($events, 0, $max);

        $user = $request->user();
        [$tenantId, $projectId] = $this->contextIds($request);
        $now = now();
        $oldest = $now->copy()->subDays((int) config('telemetry.max_event_age_days', 7));

        $rows = [];
        $skipped = 0;
        foreach ($events as $e) {
            if (! is_array($e)) {
                $skipped++;

                continue;
            }

            $screen = trim((string) ($e['screen_key'] ?? ''));
            if ($screen === '' || mb_strlen($screen) > 100) {
                $skipped++;

                continue;
            }

            $at = $this->parseMoment($e['occurred_at'] ?? null);
            // Sự kiện quá cũ (app bị kill rồi mở lại sau nhiều ngày) hoặc ở tương
            // lai (đồng hồ máy sai) thì bỏ — ghi vào là làm lệch số của hôm nay.
            if ($at === null || $at->lt($oldest) || $at->gt($now->copy()->addHour())) {
                $skipped++;

                continue;
            }

            $rows[] = [
                'device_id' => mb_substr($deviceId, 0, 64),
                'user_id' => $user?->id,
                'tenant_id' => $tenantId,
                'project_id' => $projectId,
                'screen_key' => $screen,
                'route' => $this->str($e['route'] ?? null, 200),
                'kind' => ($e['kind'] ?? 'view') === 'action' ? 'action' : 'view',
                'action' => $this->str($e['action'] ?? null, 60),
                'occurred_at' => $at,
                'duration_ms' => isset($e['duration_ms']) ? max(0, (int) $e['duration_ms']) : null,
                'session_id' => $this->str($e['session_id'] ?? null, 64),
                'app_version' => $this->str($e['app_version'] ?? null, 40),
                'platform' => $this->str($e['platform'] ?? null, 20),
                'os_version' => $this->str($e['os_version'] ?? null, 40),
                'created_at' => $now,
            ];
        }

        if ($rows !== []) {
            AppScreenEvent::insert($rows);
        }

        return ApiResponse::success([
            'accepted' => count($rows),
            'skipped' => $skipped,
        ], [], 202);
    }

    /**
     * POST /api/v1/telemetry/screen-reports — cư dân bấm nút báo lỗi trên màn.
     *
     * `screen_key` là giá trị chính của tính năng: biết lỗi Ở ĐÂU mà không phải hỏi
     * lại người báo.
     */
    public function screenReport(Request $request): JsonResponse
    {
        $deviceId = trim((string) $request->header('X-Device-Id'));
        if ($deviceId === '') {
            return ApiResponse::error('device_id_required', 'Thiếu X-Device-Id.', 422);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:2000'],
            'kind' => ['nullable', 'in:bug,idea,other'],
            'screen_key' => ['nullable', 'string', 'max:100'],
            'route' => ['nullable', 'string', 'max:200'],
            'app_version' => ['nullable', 'string', 'max:40'],
            'platform' => ['nullable', 'string', 'max:20'],
            'os_version' => ['nullable', 'string', 'max:40'],
            'locale' => ['nullable', 'string', 'max:20'],
            'attachment_ids' => ['nullable', 'array', 'max:3'],
            'attachment_ids.*' => ['integer'],
        ], [
            'message.min' => 'Vui lòng mô tả rõ hơn để đội phát triển hiểu vấn đề.',
        ]);

        $user = $request->user();
        [$tenantId, $projectId] = $this->contextIds($request);

        $report = AppScreenReport::create([
            'device_id' => mb_substr($deviceId, 0, 64),
            'user_id' => $user?->id,
            'tenant_id' => $tenantId,
            'project_id' => $projectId,
            'screen_key' => $data['screen_key'] ?? null,
            'route' => $data['route'] ?? null,
            'kind' => $data['kind'] ?? 'bug',
            'message' => $data['message'],
            'app_version' => $data['app_version'] ?? null,
            'platform' => $data['platform'] ?? null,
            'os_version' => $data['os_version'] ?? null,
            'locale' => $data['locale'] ?? null,
            'status' => 'new',
        ]);

        // Ảnh chỉ gắn được khi đã đăng nhập: `POST resident/uploads` yêu cầu auth,
        // nên người ẩn danh gửi được chữ nhưng không kèm ảnh. Không chặn họ vì thế.
        if ($user !== null && ! empty($data['attachment_ids'])) {
            $report->linkAttachments($data['attachment_ids'], $user->id);
        }

        return ApiResponse::success([
            'id' => (string) $report->id,
            'status' => $report->status,
            'message' => 'Đã gửi. Cảm ơn bạn đã báo — đội phát triển sẽ xem.',
        ], [], 201);
    }

    /**
     * Tenant/project suy từ `X-Context-Id: apartment:{relationId}` nếu có.
     *
     * Thiếu (người ẩn danh, chưa chọn căn) thì để null — KHÔNG đoán, vì số liệu gán
     * sai dự án còn tệ hơn số liệu không gán.
     *
     * @return array{0: ?int, 1: ?int}
     */
    private function contextIds(Request $request): array
    {
        $user = $request->user();
        if ($user === null) {
            return [null, null];
        }

        try {
            $service = app(ResidentContextService::class);
            $apartmentIds = $service->apartmentIds($user, $request->header('X-Context-Id'));
            if ($apartmentIds === []) {
                return [$user->tenant_id, null];
            }
            $apartment = Apartment::query()
                ->whereIn('id', $apartmentIds)->orderBy('id')->first();
            if ($apartment === null) {
                return [$user->tenant_id, null];
            }

            return [
                $apartment->tenant_id,
                $apartment->building?->project_id,
            ];
        } catch (\Throwable) {
            // Nhật ký không được làm vỡ request của người dùng vì bất cứ lý do gì.
            return [$user->tenant_id, null];
        }
    }

    private function parseMoment(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            $raw = trim($value);

            // Cùng luật với `paid_at`: thiếu múi giờ thì hiểu theo múi giờ nghiệp vụ
            // (UTC+7 — chủ dự án chốt 30/07), không phải UTC.
            return preg_match('/(Z|z|[+-]\d{2}:?\d{2})$/', $raw)
                ? Carbon::parse($raw)
                : Carbon::parse($raw, config('x2.timezone'))->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    private function str(mixed $v, int $max): ?string
    {
        if (! is_string($v)) {
            return null;
        }
        $v = trim($v);

        return $v === '' ? null : mb_substr($v, 0, $max);
    }
}
