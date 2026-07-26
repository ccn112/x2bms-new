<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\ApartmentWalletResource;
use App\Http\Resources\Api\V1\ApartmentWalletTransactionResource;
use App\Models\Apartment;
use App\Models\ApartmentWallet;
use App\Models\ApartmentWalletTransaction;
use App\Services\Resident\ApartmentWalletService;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ví cư dân theo CĂN HỘ. Cư dân nhiều căn → chọn căn qua header X-Context-Id (apartment:{id}).
 * Mặc định lấy căn đầu tiên trong danh sách của cư dân.
 */
class WalletController extends ApiController
{
    public function __construct(
        private readonly ResidentContextService $context,
        private readonly ApartmentWalletService $wallets,
    ) {}

    /** GET /api/v1/resident/wallet — số dư tổng + các ngăn + tình trạng phí ưu tiên. */
    public function show(Request $request): JsonResponse
    {
        $wallet = $this->resolveWallet($request);
        if (! $wallet) {
            return ApiResponse::success(null);
        }
        $wallet->load('buckets.feeType');

        $debts = $this->wallets->debtByFeeType($wallet->apartment_id);

        return ApiResponse::success([
            'wallet' => ApartmentWalletResource::make($wallet)->resolve($request),
            'debts' => $debts,
        ]);
    }

    /** GET /api/v1/resident/wallet/transactions — sổ ví (phiếu thu / hạch toán), mới nhất trước. */
    public function transactions(Request $request): JsonResponse
    {
        $wallet = $this->resolveWallet($request);
        if (! $wallet) {
            return ApiResponse::paginated([], null);
        }

        $perPage = min((int) $request->integer('per_page', 20), 50);

        $paginator = ApartmentWalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->orderByDesc('posted_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $items = ApartmentWalletTransactionResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }

    private function resolveWallet(Request $request): ?ApartmentWallet
    {
        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));
        if (empty($apartmentIds)) {
            return null;
        }
        $apartment = Apartment::whereIn('id', $apartmentIds)->orderBy('id')->first();

        return $apartment ? $this->wallets->walletFor($apartment) : null;
    }
}
