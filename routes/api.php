<?php

use App\Http\Controllers\Platform\Billing\BillingAdjustmentController;
use App\Http\Controllers\Platform\Billing\BillingAuditLogController;
use App\Http\Controllers\Platform\Billing\BillingInvoiceController;
use App\Http\Controllers\Platform\Billing\PassThroughWalletController;
use App\Http\Controllers\Platform\Billing\QuotaAlertController;
use App\Http\Controllers\Platform\Billing\SaasRevenueController;
use App\Http\Controllers\Platform\Billing\TenantSubscriptionController;
use App\Http\Controllers\Platform\Billing\UsageMeteringController;
use App\Http\Controllers\Platform\Integration\IntegrationApiKeyController;
use App\Http\Controllers\Platform\Integration\IntegrationConnectionController;
use App\Http\Controllers\Platform\Integration\IntegrationEventController;
use App\Http\Controllers\Platform\Integration\IntegrationOverviewController;
use App\Http\Controllers\Platform\Integration\IntegrationRetryQueueController;
use App\Http\Controllers\Platform\Integration\IntegrationSecurityController;
use App\Http\Controllers\Platform\Integration\WebhookEndpointController;
use App\Http\Controllers\Platform\Support\DataCorrectionController;
use App\Http\Controllers\Platform\Support\SupportCenterController;
use App\Http\Controllers\Platform\Support\SupportTicketController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BootstrapController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\OtpController;
use Illuminate\Support\Facades\Route;

/*
 * Mobile API v1 — stateless Bearer-token API for the Resident & BQL Flutter apps.
 * See docs/ARCHITECTURE_X2_PLATFORM_V1.md. User is the single auth principal.
 */
Route::prefix('v1')->group(function () {
    // Public — no auth, cache-friendly.
    Route::middleware('throttle:public-read')->group(function () {
        Route::get('public/bootstrap', [BootstrapController::class, 'public']);
    });

    // Auth.
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:auth-login');
    Route::post('auth/otp/request', [OtpController::class, 'request'])->middleware('throttle:otp');
    Route::post('auth/otp/verify', [OtpController::class, 'verify'])->middleware('throttle:otp');
    // Refresh authenticates the refresh token itself (ability checked in controller).
    Route::post('auth/refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');

    // Authenticated (any valid access token).
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me/bootstrap', [BootstrapController::class, 'me']);
        Route::patch('me/profile', [\App\Http\Controllers\Api\V1\ProfileController::class, 'update']);
        Route::post('me/devices', [DeviceController::class, 'store']);
        Route::delete('me/devices/{installationId}', [DeviceController::class, 'destroy']);
    });

    // X2AI chat — auth OPTIONAL (web/app-authed = identified user; else anonymous by
    // X-Device-Id). Throttle keyed inside the service; a light route throttle guards abuse.
    Route::middleware('throttle:public-read')->group(function () {
        Route::post('ai/chat', [\App\Http\Controllers\Api\V1\Ai\ChatController::class, 'chat']);
        Route::get('ai/chat/sessions', [\App\Http\Controllers\Api\V1\Ai\ChatController::class, 'sessions']);
        Route::get('ai/chat/sessions/{session}', [\App\Http\Controllers\Api\V1\Ai\ChatController::class, 'session']);
    });

    // Resident business endpoints — require the `resident` token ability.
    Route::middleware(['auth:sanctum', 'ability:resident', 'throttle:api'])->prefix('resident')->group(function () {
        Route::get('statements', [\App\Http\Controllers\Api\V1\Resident\StatementController::class, 'index']);
        Route::get('statements/{statement}', [\App\Http\Controllers\Api\V1\Resident\StatementController::class, 'show']);

        // Công nợ tổng hợp (card Tiện ích) + xu hướng phí 6 tháng (CD-PAY-01).
        Route::get('billing/summary', [\App\Http\Controllers\Api\V1\Resident\BillingSummaryController::class, 'show']);
        Route::get('billing/summary/trend', [\App\Http\Controllers\Api\V1\Resident\BillingSummaryController::class, 'trend']);

        // Thông báo cư dân.
        Route::get('notifications', [\App\Http\Controllers\Api\V1\Resident\NotificationController::class, 'index']);
        Route::post('notifications/{notification}/read', [\App\Http\Controllers\Api\V1\Resident\NotificationController::class, 'read']);

        // Điểm thưởng & hạng (tab Ưu đãi — CD-LY-01).
        Route::get('loyalty', [\App\Http\Controllers\Api\V1\Resident\LoyaltyController::class, 'show']);
        Route::get('loyalty/activities', [\App\Http\Controllers\Api\V1\Resident\LoyaltyController::class, 'activities']);
        Route::get('loyalty/gifts', [\App\Http\Controllers\Api\V1\Resident\LoyaltyController::class, 'gifts']);

        // Ưu đãi — voucher không cần đổi điểm (CD-OF-01).
        Route::get('offers', [\App\Http\Controllers\Api\V1\Resident\OfferController::class, 'index']);

        // Cộng đồng (CD-CM-*) — scope theo dự án.
        Route::get('community/posts', [\App\Http\Controllers\Api\V1\Resident\CommunityController::class, 'posts']);
        Route::get('community/events', [\App\Http\Controllers\Api\V1\Resident\CommunityController::class, 'events']);
        Route::get('community/polls', [\App\Http\Controllers\Api\V1\Resident\CommunityController::class, 'polls']);
        Route::post('community/polls/{poll}/vote', [\App\Http\Controllers\Api\V1\Resident\CommunityController::class, 'vote']);
        Route::get('community/groups', [\App\Http\Controllers\Api\V1\Resident\CommunityController::class, 'groups']);

        // Chợ nội khu (CD-MK-*) — listings/services/categories scope dự án/tenant.
        Route::get('market/listings', [\App\Http\Controllers\Api\V1\Resident\MarketController::class, 'listings']);
        Route::get('market/services', [\App\Http\Controllers\Api\V1\Resident\MarketController::class, 'services']);
        Route::get('market/categories', [\App\Http\Controllers\Api\V1\Resident\MarketController::class, 'categories']);
        // BĐS nội khu — tách riêng khỏi market/*.
        Route::get('real-estate', [\App\Http\Controllers\Api\V1\Resident\MarketController::class, 'realEstate']);

        // Căn hộ đang chọn + thành viên hộ (Hồ sơ cư dân — P3).
        Route::get('apartment', [\App\Http\Controllers\Api\V1\Resident\ApartmentController::class, 'show']);
    });
});

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

/*
 * Batch 08 — Integration Center API (English business routes). SuperAdmin only
 * (middleware platform.admin). Secrets returned once on create/rotate; every
 * state-changing action writes integration_audit_logs.
 */
Route::middleware('platform.admin')->prefix('platform/integrations')->group(function () {
    Route::get('overview', [IntegrationOverviewController::class, 'index']);
    Route::get('audit-logs', [IntegrationOverviewController::class, 'auditLogs']);

    Route::get('connections', [IntegrationConnectionController::class, 'index']);
    Route::post('connections', [IntegrationConnectionController::class, 'store']);
    Route::get('connections/{connection}', [IntegrationConnectionController::class, 'show']);
    Route::post('connections/{connection}/test', [IntegrationConnectionController::class, 'test']);
    Route::post('connections/{connection}/enable', [IntegrationConnectionController::class, 'enable']);
    Route::post('connections/{connection}/disable', [IntegrationConnectionController::class, 'disable']);
    Route::post('connections/{connection}/rotate-secret', [IntegrationConnectionController::class, 'rotateSecret']);

    Route::get('api-keys', [IntegrationApiKeyController::class, 'index']);
    Route::post('api-keys', [IntegrationApiKeyController::class, 'store']);
    Route::get('api-keys/{apiKey}', [IntegrationApiKeyController::class, 'show']);
    Route::post('api-keys/{apiKey}/rotate', [IntegrationApiKeyController::class, 'rotate']);
    Route::post('api-keys/{apiKey}/revoke', [IntegrationApiKeyController::class, 'revoke']);
    Route::post('api-keys/{apiKey}/suspend', [IntegrationApiKeyController::class, 'suspend']);
    Route::post('api-keys/{apiKey}/resume', [IntegrationApiKeyController::class, 'resume']);

    Route::get('webhooks', [WebhookEndpointController::class, 'index']);
    Route::post('webhooks', [WebhookEndpointController::class, 'store']);
    Route::get('webhooks/{webhook}', [WebhookEndpointController::class, 'show']);
    Route::post('webhooks/{webhook}/test', [WebhookEndpointController::class, 'test']);
    Route::post('webhooks/{webhook}/enable', [WebhookEndpointController::class, 'enable']);
    Route::post('webhooks/{webhook}/disable', [WebhookEndpointController::class, 'disable']);
    Route::post('webhooks/{webhook}/rotate-secret', [WebhookEndpointController::class, 'rotateSecret']);
    Route::get('webhooks/{webhook}/deliveries', [WebhookEndpointController::class, 'deliveries']);

    Route::get('events', [IntegrationEventController::class, 'index']);
    Route::get('events/{event}', [IntegrationEventController::class, 'show']);
    Route::post('events/{event}/replay', [IntegrationEventController::class, 'replay']);

    Route::get('retry-queue', [IntegrationRetryQueueController::class, 'index']);
    Route::post('retry-queue/{job}/retry-now', [IntegrationRetryQueueController::class, 'retryNow']);
    Route::post('retry-queue/{job}/skip', [IntegrationRetryQueueController::class, 'skip']);
    Route::post('retry-queue/{job}/dead-letter', [IntegrationRetryQueueController::class, 'deadLetter']);

    Route::get('security-settings', [IntegrationSecurityController::class, 'show']);
    Route::put('security-settings', [IntegrationSecurityController::class, 'update']);
    Route::post('security-settings/enforce-hmac', [IntegrationSecurityController::class, 'enforceHmac']);
    Route::post('security-settings/emergency-disable', [IntegrationSecurityController::class, 'emergencyDisable']);
});

/*
 * Batch 10 — Support Center API (English business routes). SuperAdmin only
 * (platform.admin). Every sensitive action writes support_audit_logs.
 */
Route::middleware('platform.admin')->prefix('platform/support')->group(function () {
    Route::get('dashboard', [SupportCenterController::class, 'dashboard']);
    Route::get('reports/resolution', [SupportCenterController::class, 'report']);
    Route::get('audit-logs', [SupportCenterController::class, 'auditLogs']);

    Route::get('tickets', [SupportTicketController::class, 'index']);
    Route::post('tickets', [SupportTicketController::class, 'store']);
    Route::get('tickets/{ticket}', [SupportTicketController::class, 'show']);
    Route::post('tickets/{ticket}/assign', [SupportTicketController::class, 'assign']);
    Route::post('tickets/{ticket}/escalate', [SupportTicketController::class, 'escalate']);
    Route::post('tickets/{ticket}/close', [SupportTicketController::class, 'close']);
    Route::post('tickets/{ticket}/reopen', [SupportTicketController::class, 'reopen']);
    Route::post('tickets/{ticket}/messages', [SupportTicketController::class, 'addMessage']);

    Route::get('data-correction-requests', [DataCorrectionController::class, 'index']);
    Route::post('data-correction-requests', [DataCorrectionController::class, 'store']);
    Route::get('data-correction-requests/{dcr}', [DataCorrectionController::class, 'show']);
    Route::post('data-correction-requests/{dcr}/approve', [DataCorrectionController::class, 'approve']);
    Route::post('data-correction-requests/{dcr}/reject', [DataCorrectionController::class, 'reject']);
    Route::post('data-fix-wizard/{dcr}/create-snapshot', [DataCorrectionController::class, 'snapshot']);
    Route::post('data-fix-wizard/{dcr}/execute', [DataCorrectionController::class, 'execute']);
    Route::post('data-fix-wizard/{dcr}/rollback', [DataCorrectionController::class, 'rollback']);

    Route::get('knowledge-base/articles', [SupportCenterController::class, 'kbIndex']);
    Route::post('knowledge-base/articles', [SupportCenterController::class, 'kbStore']);
    Route::post('knowledge-base/articles/{article}/publish', [SupportCenterController::class, 'kbPublish']);
    Route::post('knowledge-base/articles/{article}/archive', [SupportCenterController::class, 'kbArchive']);
});
