<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AiChatSession;
use App\Services\Ai\ChatException;
use App\Services\Ai\ChatService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * X2AI chat. Auth is OPTIONAL: a valid Sanctum token → identified user (web is always
 * identified); otherwise anonymous by X-Device-Id (app public). Advanced actions still
 * require login — the app turns `register_for_more` into its Action Gate.
 * SSE contract mirrors xweb: data:{type:delta|done|error}.
 */
class ChatController extends ApiController
{
    public function __construct(private readonly ChatService $chat) {}

    public function chat(Request $request): StreamedResponse|JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string'],
            'session_id' => ['nullable', 'integer'],
            'surface' => ['nullable', 'string', 'max:120'],
        ]);

        $user = auth('sanctum')->user(); // resolved by optional auth:sanctum on the route group
        $deviceId = $request->header('X-Device-Id') ?: $request->input('device_id');

        try {
            $ctx = $this->chat->preflight($user, $deviceId, $data['message'], $data['session_id'] ?? null, $data['surface'] ?? null);
        } catch (ChatException $e) {
            return ApiResponse::error($e->errorCode, $e->getMessage(), $e->status, retryable: $e->status === 429);
        }

        return response()->stream(function () use ($ctx) {
            $emit = function (array $payload) {
                echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE)."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            try {
                $result = $this->chat->streamAnswer($ctx, fn (string $text) => $emit(['type' => 'delta', 'text' => $text]));
                $emit(['type' => 'done', 'session_id' => $result['session_id']]);
            } catch (\Throwable $e) {
                report($e);
                $emit(['type' => 'error', 'message' => 'Xin lỗi, đã có lỗi khi tạo câu trả lời.']);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /** GET /api/v1/ai/chat/sessions — list actor's sessions. */
    public function sessions(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();
        $deviceId = $request->header('X-Device-Id') ?: $request->input('device_id');

        $sessions = AiChatSession::query()
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->when(! $user && $deviceId, fn ($q) => $q->where('device_id', $deviceId))
            ->when(! $user && ! $deviceId, fn ($q) => $q->whereRaw('1=0'))
            ->orderByDesc('last_message_at')
            ->limit(50)
            ->get(['id', 'title', 'surface', 'message_count', 'last_message_at']);

        return ApiResponse::success($sessions);
    }

    /** GET /api/v1/ai/chat/sessions/{session} — messages to resume. */
    public function session(Request $request, AiChatSession $session): JsonResponse
    {
        $user = auth('sanctum')->user();
        $deviceId = $request->header('X-Device-Id') ?: $request->input('device_id');

        $owns = ($user && $session->user_id === $user->id)
            || (! $user && $deviceId && $session->device_id === $deviceId);
        if (! $owns) {
            return ApiResponse::error('FORBIDDEN', 'Không có quyền truy cập phiên chat.', 403);
        }

        $messages = $session->messages()->orderBy('id')->get(['role', 'content', 'created_at']);

        return ApiResponse::success(['id' => $session->id, 'title' => $session->title, 'messages' => $messages]);
    }
}
