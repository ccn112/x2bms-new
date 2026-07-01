<?php

namespace App\Http\Controllers\Platform\Billing;

use App\Filament\Concerns\WritesBillingAudit;
use App\Http\Controllers\Controller;
use App\Models\PassThroughTransaction;
use App\Models\PassThroughWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Batch 07 — API vi pass-through. */
class PassThroughWalletController extends Controller
{
    use WritesBillingAudit;

    public function index(Request $request): JsonResponse
    {
        return response()->json(PassThroughWallet::with('tenant')
            ->when($request->tenant_id, fn ($q, $t) => $q->where('tenant_id', $t))
            ->paginate((int) $request->get('per_page', 20)));
    }

    public function topUp(Request $request, PassThroughWallet $wallet): JsonResponse
    {
        $data = $request->validate(['amount' => 'required|numeric|min:0']);

        return response()->json($this->move($wallet, 'top_up', (float) $data['amount'], 'wallet.topup'));
    }

    public function deduct(Request $request, PassThroughWallet $wallet): JsonResponse
    {
        $data = $request->validate(['amount' => 'required|numeric|min:0']);

        return response()->json($this->move($wallet, 'deduct', (float) $data['amount'], 'wallet.deduct'));
    }

    public function configureAutoTopup(Request $request, PassThroughWallet $wallet): JsonResponse
    {
        $data = $request->validate(['auto_topup_enabled' => 'boolean', 'auto_topup_amount' => 'nullable|numeric', 'low_balance_threshold' => 'nullable|numeric']);
        $wallet->update($data);
        $this->billingAudit('wallet.configure_autotopup', $wallet, null, $data);

        return response()->json($wallet->fresh());
    }

    private function move(PassThroughWallet $wallet, string $type, float $amount, string $action): PassThroughWallet
    {
        $before = ['balance' => $wallet->balance];
        $type === 'top_up' ? $wallet->increment('balance', $amount) : $wallet->decrement('balance', $amount);
        PassThroughTransaction::create([
            'wallet_id' => $wallet->id, 'tenant_id' => $wallet->tenant_id, 'transaction_type' => $type,
            'amount' => $amount, 'balance_after' => $wallet->fresh()->balance, 'status' => 'confirmed',
        ]);
        $this->billingAudit($action, $wallet, $before, ['balance' => $wallet->fresh()->balance]);

        return $wallet->fresh();
    }
}
