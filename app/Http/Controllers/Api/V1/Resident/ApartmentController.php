<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\ApartmentResource;
use App\Models\Apartment;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/resident/apartment — căn hộ đang chọn + thành viên hộ (household).
 *
 * Căn "đang chọn" = theo header `X-Context-Id: apartment:{relationId}`; nếu không
 * có thì lấy quan hệ `is_primary`, cuối cùng là quan hệ đầu tiên. Cư dân
 * tenant_id=NULL → giải quyết qua `residentMemberships()` (đã withoutGlobalScope
 * 'tenant'), KHÔNG dựa tenant global scope. Xem docs/contracts/RESIDENT_API_DOMAIN.md.
 */
class ApartmentController extends ApiController
{
    /** GET /api/v1/resident/apartment */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $relations = $user->residentMemberships()
            ->with('apartmentRelations')
            ->get()
            ->flatMap
            ->apartmentRelations;

        $ctx = $request->header('X-Context-Id');
        if ($ctx && str_starts_with($ctx, 'apartment:')) {
            $relationId = (int) substr($ctx, strlen('apartment:'));
            $relations = $relations->where('id', $relationId);
        }

        $active = $relations->firstWhere('is_primary', true) ?? $relations->first();

        if ($active === null) {
            return ApiResponse::error('no_apartment', 'Tài khoản chưa gắn với căn hộ nào.', 404);
        }

        $apartment = Apartment::query()
            ->with(['building.project', 'apartmentRelations.resident'])
            ->find($active->apartment_id);

        if ($apartment === null) {
            return ApiResponse::error('no_apartment', 'Không tìm thấy căn hộ.', 404);
        }

        return ApiResponse::success(ApartmentResource::make($apartment)->resolve($request));
    }
}
