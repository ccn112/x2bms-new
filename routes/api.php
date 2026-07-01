<?php

use App\Http\Controllers\Platform\Billing\BillingAdjustmentController;
use App\Http\Controllers\Platform\Billing\BillingAuditLogController;
use App\Http\Controllers\Platform\Billing\BillingInvoiceController;
use App\Http\Controllers\Platform\Billing\PassThroughWalletController;
use App\Http\Controllers\Platform\Billing\QuotaAlertController;
use App\Http\Controllers\Platform\Billing\SaasRevenueController;
use App\Http\Controllers\Platform\Billing\TenantSubscriptionController;
use App\Http\Controllers\Platform\Billing\UsageMeteringController;
use Illuminate\Support\Facades\Route;

/*
 * Batch 07 — SaaS Billing API (English business routes). Chỉ SuperAdmin/Billing admin
 * (middleware platform.admin). Xác thực qua phiên Filament (actingAs trong test).
 */
Route::middleware('platform.admin')->prefix('platform/billing')->group(function () {
    Route::get('revenue-dashboard', [SaasRevenueController::class, 'index']);

    // Subscriptions + lifecycle.
    Route::get('subscriptions', [TenantSubscriptionController::class, 'index']);
    Route::post('subscriptions', [TenantSubscriptionController::class, 'store']);
    Route::get('subscriptions/{subscription}', [TenantSubscriptionController::class, 'show']);
    Route::post('subscriptions/{subscription}/upgrade', [TenantSubscriptionController::class, 'upgrade']);
    Route::post('subscriptions/{subscription}/downgrade', [TenantSubscriptionController::class, 'downgrade']);
    Route::post('subscriptions/{subscription}/pause', [TenantSubscriptionController::class, 'pause']);
    Route::post('subscriptions/{subscription}/resume', [TenantSubscriptionController::class, 'resume']);
    Route::post('subscriptions/{subscription}/suspend', [TenantSubscriptionController::class, 'suspend']);
    Route::post('subscriptions/{subscription}/renew', [TenantSubscriptionController::class, 'renew']);
    Route::post('subscriptions/{subscription}/addons', [TenantSubscriptionController::class, 'addAddon']);
    Route::delete('subscriptions/{subscription}/addons/{addon}', [TenantSubscriptionController::class, 'removeAddon']);

    // Usage & metering.
    Route::get('usage', [UsageMeteringController::class, 'index']);
    Route::post('usage-periods/{period}/recalculate', [UsageMeteringController::class, 'recalculate']);
    Route::post('usage-periods/{period}/lock', [UsageMeteringController::class, 'lock']);
    Route::post('usage-periods/{period}/unlock', [UsageMeteringController::class, 'unlock']);
    Route::post('usage-periods/{period}/generate-alerts', [UsageMeteringController::class, 'generateAlerts']);

    // Quota alerts.
    Route::get('quota-alerts', [QuotaAlertController::class, 'index']);
    Route::post('quota-alerts/{alert}/resolve', [QuotaAlertController::class, 'resolve']);
    Route::post('quota-alerts/{alert}/convert-to-addon', [QuotaAlertController::class, 'convertToAddon']);
    Route::post('quota-alerts/{alert}/convert-to-upgrade', [QuotaAlertController::class, 'convertToUpgrade']);

    // Invoices + payments.
    Route::get('invoices', [BillingInvoiceController::class, 'index']);
    Route::get('invoices/{invoice}', [BillingInvoiceController::class, 'show']);
    Route::post('invoices/generate', [BillingInvoiceController::class, 'generate']);
    Route::post('invoices/{invoice}/approve', [BillingInvoiceController::class, 'approve']);
    Route::post('invoices/{invoice}/send', [BillingInvoiceController::class, 'send']);
    Route::post('invoices/{invoice}/void', [BillingInvoiceController::class, 'void']);
    Route::post('invoices/{invoice}/payments', [BillingInvoiceController::class, 'recordPayment']);
    Route::post('invoices/{invoice}/reconcile', [BillingInvoiceController::class, 'reconcile']);

    // Pass-through wallets.
    Route::get('wallets', [PassThroughWalletController::class, 'index']);
    Route::post('wallets/{wallet}/top-up', [PassThroughWalletController::class, 'topUp']);
    Route::post('wallets/{wallet}/deduct', [PassThroughWalletController::class, 'deduct']);
    Route::post('wallets/{wallet}/configure-auto-topup', [PassThroughWalletController::class, 'configureAutoTopup']);

    // Adjustments + credit notes.
    Route::get('adjustments', [BillingAdjustmentController::class, 'index']);
    Route::post('adjustments/{adjustment}/approve', [BillingAdjustmentController::class, 'approve']);
    Route::post('adjustments/{adjustment}/reject', [BillingAdjustmentController::class, 'reject']);
    Route::post('adjustments/{adjustment}/credit-note', [BillingAdjustmentController::class, 'issueCreditNote']);

    // Audit.
    Route::get('audit-logs', [BillingAuditLogController::class, 'index']);
});
