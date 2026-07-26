<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\PlatformArticleResource;
use App\Models\PlatformContent;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Bài viết cư dân đọc — PlatformContent đã published (quy định/cẩm nang/tin tức).
 * `publish_scope` là chỉ báo tầng quản lý (platform=SuperAdmin, tenant=Công ty,
 * building=BQL). Bản demo trả về mọi scope published cho cư dân; siết theo
 * tenant/project của cư dân là bước sau (khi có cơ chế target rõ ràng).
 */
class ArticleController extends ApiController
{
    /** GET /api/v1/resident/articles?type=policy|guide|news&per_page=&cursor= */
    public function index(Request $request): JsonResponse
    {
        $paginator = PlatformContent::query()
            ->with('category')
            ->where('status', 'published')
            ->when($request->filled('type'),
                fn ($q) => $q->where('content_type', (string) $request->string('type')))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->cursorPaginate(min((int) $request->integer('per_page', 20), 50));

        $items = PlatformArticleResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }

    /** GET /api/v1/resident/articles/{article} — id hoặc slug. */
    public function show(Request $request, string $article): JsonResponse
    {
        $content = PlatformContent::query()
            ->with('category')
            ->where('status', 'published')
            ->where(is_numeric($article) ? 'id' : 'slug', $article)
            ->first();

        if ($content === null) {
            throw new NotFoundHttpException;
        }

        return ApiResponse::success(PlatformArticleResource::make($content)->resolve($request));
    }
}
