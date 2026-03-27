<?php

declare(strict_types=1);

use Guard51\Entity\UserRole;
use Guard51\Middleware\AuthMiddleware;
use Guard51\Middleware\RateLimitMiddleware;
use Guard51\Middleware\RoleMiddleware;
use Guard51\Middleware\TenantMiddleware;
use Guard51\Module\Auth\AuthController;
use Guard51\Module\AppDistribution\AppReleaseController;
use Guard51\Module\AppDistribution\AppClientController;
use Guard51\Module\Client\ClientController;
use Guard51\Module\Dashboard\DashboardController;
use Guard51\Module\Feature\FeatureController;
use Guard51\Module\Guard\GuardController;
use Guard51\Module\Passdown\PassdownController;
use Guard51\Module\Scheduling\ShiftController;
use Guard51\Module\Site\SiteController;
use Guard51\Module\TimeClock\TimeClockController;
use Guard51\Module\Onboarding\InvitationController;
use Guard51\Module\Onboarding\OnboardingController;
use Guard51\Module\Subscription\PlanController;
use Guard51\Module\Subscription\SubscriptionController;
use Guard51\Module\Tenant\TenantController;
use Guard51\Module\Usage\UsageController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {

    // ── Health Check ────────────────────────────────
    $app->get('/api/health', function (Request $request, Response $response): Response {
        $payload = json_encode([
            'status' => 'ok',
            'service' => 'guard51-api',
            'version' => '0.1.0',
            'timestamp' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            'php' => PHP_VERSION,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // ── API v1 Routes ───────────────────────────────
    $app->group('/api/v1', function (RouteCollectorProxy $group) use ($app): void {
        $container = $app->getContainer();

        // ── Auth: Public routes (no auth required) ──
        $group->group('/auth', function (RouteCollectorProxy $auth) use ($container): void {
            $auth->post('/login', [AuthController::class, 'login'])
                ->add(new RateLimitMiddleware($container->get(\Predis\Client::class), maxAttempts: 5, windowSeconds: 60, prefix: 'login'));

            $auth->post('/register', [AuthController::class, 'register'])
                ->add(new RateLimitMiddleware($container->get(\Predis\Client::class), maxAttempts: 3, windowSeconds: 60, prefix: 'register'));

            $auth->post('/refresh', [AuthController::class, 'refresh'])
                ->add(new RateLimitMiddleware($container->get(\Predis\Client::class), maxAttempts: 10, windowSeconds: 60, prefix: 'refresh'));

            $auth->post('/forgot-password', [AuthController::class, 'forgotPassword'])
                ->add(new RateLimitMiddleware($container->get(\Predis\Client::class), maxAttempts: 3, windowSeconds: 300, prefix: 'forgot'));

            $auth->post('/reset-password', [AuthController::class, 'resetPassword'])
                ->add(new RateLimitMiddleware($container->get(\Predis\Client::class), maxAttempts: 5, windowSeconds: 300, prefix: 'reset'));
        });

        // ── Auth: Protected routes (JWT required) ───
        $group->group('/auth', function (RouteCollectorProxy $auth): void {
            $auth->get('/me', [AuthController::class, 'me']);
            $auth->post('/logout', [AuthController::class, 'logout']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Subscription Plans: Public ───────────────
        $group->get('/subscriptions/plans', [PlanController::class, 'publicPlans']);

        // ── Subscription Plans: Super Admin CRUD ─────
        $group->group('/admin/plans', function (RouteCollectorProxy $plans): void {
            $plans->get('', [PlanController::class, 'index']);
            $plans->post('', [PlanController::class, 'create']);
            $plans->put('/{id}', [PlanController::class, 'update']);
            $plans->delete('/{id}', [PlanController::class, 'delete']);
            $plans->post('/{id}/duplicate', [PlanController::class, 'duplicate']);
        })
            ->add(new RoleMiddleware(UserRole::SUPER_ADMIN))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Subscriptions: Tenant (auth required) ────
        $group->group('/subscriptions', function (RouteCollectorProxy $subs): void {
            $subs->get('/current', [SubscriptionController::class, 'current']);
            $subs->post('/initialize', [SubscriptionController::class, 'initialize']);
            $subs->post('/verify', [SubscriptionController::class, 'verify']);
            $subs->post('/bank-transfer', [SubscriptionController::class, 'bankTransfer']);
            $subs->post('/cancel', [SubscriptionController::class, 'cancel']);
            $subs->post('/upgrade', [SubscriptionController::class, 'upgrade']);
            $subs->get('/invoices', [SubscriptionController::class, 'invoices']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Subscriptions: Paystack webhook (no auth) ─
        $group->post('/subscriptions/webhook', [SubscriptionController::class, 'webhook']);

        // ── Subscriptions: Super Admin ────────────────
        $group->group('/admin/subscriptions', function (RouteCollectorProxy $adminSubs): void {
            $adminSubs->get('/pending', [SubscriptionController::class, 'pendingTransfers']);
            $adminSubs->post('/{id}/confirm-payment', [SubscriptionController::class, 'confirmPayment']);
        })
            ->add(new RoleMiddleware(UserRole::SUPER_ADMIN))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Feature Modules: Super Admin ─────────────
        $group->get('/features/modules', [FeatureController::class, 'listModules'])
            ->add(new RoleMiddleware(UserRole::SUPER_ADMIN))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Feature Modules: Tenant ──────────────────
        $group->group('/features/tenant', function (RouteCollectorProxy $feat): void {
            $feat->get('', [FeatureController::class, 'tenantModules']);
            $feat->post('/enable/{moduleKey}', [FeatureController::class, 'enableModule']);
            $feat->post('/disable/{moduleKey}', [FeatureController::class, 'disableModule']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Usage Metrics: Tenant ────────────────────
        $group->group('/usage', function (RouteCollectorProxy $usage): void {
            $usage->get('/current', [UsageController::class, 'current']);
            $usage->get('/limits', [UsageController::class, 'limits']);
            $usage->get('/history', [UsageController::class, 'history']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Onboarding: Tenant (auth required) ───────
        $group->group('/onboarding', function (RouteCollectorProxy $onb): void {
            $onb->get('/status', [OnboardingController::class, 'status']);
            $onb->put('/company', [OnboardingController::class, 'updateCompany']);
            $onb->put('/branding', [OnboardingController::class, 'updateBranding']);
            $onb->post('/bank-account', [OnboardingController::class, 'saveBankAccount']);
            $onb->get('/platform-bank-accounts', [OnboardingController::class, 'platformBankAccounts']);
            $onb->post('/complete', [OnboardingController::class, 'complete']);
            $onb->post('/skip', [OnboardingController::class, 'skip']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Invitations: Public (accept — no auth) ───
        $group->post('/invitations/accept', [InvitationController::class, 'accept'])
            ->add(new RateLimitMiddleware($container->get(\Predis\Client::class), maxAttempts: 5, windowSeconds: 300, prefix: 'invite_accept'));

        // ── Invitations: Tenant (auth required) ──────
        $group->group('/invitations', function (RouteCollectorProxy $inv): void {
            $inv->get('', [InvitationController::class, 'index']);
            $inv->post('', [InvitationController::class, 'invite']);
            $inv->post('/{id}/resend', [InvitationController::class, 'resend']);
            $inv->delete('/{id}', [InvitationController::class, 'revoke']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Tenant Management: Super Admin ────────────
        $group->group('/admin/tenants', function (RouteCollectorProxy $tenants): void {
            $tenants->get('', [TenantController::class, 'index']);
            $tenants->get('/stats', [TenantController::class, 'stats']);
            $tenants->get('/{id}', [TenantController::class, 'show']);
            $tenants->post('/{id}/suspend', [TenantController::class, 'suspend']);
            $tenants->post('/{id}/reactivate', [TenantController::class, 'reactivate']);
            $tenants->post('/{id}/impersonate', [TenantController::class, 'impersonate']);
        })
            ->add(new RoleMiddleware(UserRole::SUPER_ADMIN))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── App Distribution: Public (no auth — called by apps) ──
        $group->get('/apps/check-update', [AppClientController::class, 'checkUpdate']);
        $group->post('/apps/heartbeat', [AppClientController::class, 'heartbeat']);

        // ── App Distribution: Tenant (auth required) ─
        $group->group('/apps', function (RouteCollectorProxy $apps): void {
            $apps->get('/available', [AppClientController::class, 'available']);
            $apps->get('/download/{releaseId}', [AppClientController::class, 'download']);
            $apps->get('/config', [AppClientController::class, 'getConfig']);
            $apps->put('/config/{appKey}', [AppClientController::class, 'updateConfig']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── App Distribution: Super Admin ─────────────
        $group->group('/admin/apps', function (RouteCollectorProxy $adminApps): void {
            $adminApps->get('/dashboard', [AppReleaseController::class, 'dashboard']);
            $adminApps->get('/releases', [AppReleaseController::class, 'listReleases']);
            $adminApps->post('/releases', [AppReleaseController::class, 'upload']);
            $adminApps->get('/releases/{id}', [AppReleaseController::class, 'show']);
            $adminApps->put('/releases/{id}', [AppReleaseController::class, 'update']);
            $adminApps->delete('/releases/{id}', [AppReleaseController::class, 'deactivate']);
            $adminApps->get('/analytics', [AppReleaseController::class, 'analytics']);
        })
            ->add(new RoleMiddleware(UserRole::SUPER_ADMIN))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ══════════════════════════════════════════════
        // PHASE 1: Core Operations
        // ══════════════════════════════════════════════

        // ── Sites ────────────────────────────────────
        $group->group('/sites', function (RouteCollectorProxy $sites): void {
            $sites->get('', [SiteController::class, 'index']);
            $sites->get('/map', [SiteController::class, 'map']);
            $sites->post('', [SiteController::class, 'create']);
            $sites->get('/{id}', [SiteController::class, 'show']);
            $sites->put('/{id}', [SiteController::class, 'update']);
            $sites->delete('/{id}', [SiteController::class, 'delete']);
            $sites->post('/{id}/suspend', [SiteController::class, 'suspend']);
            $sites->post('/{id}/activate', [SiteController::class, 'activate']);
            // Post Orders nested under sites
            $sites->get('/{siteId}/post-orders', [SiteController::class, 'listPostOrders']);
            $sites->post('/{siteId}/post-orders', [SiteController::class, 'createPostOrder']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // Post Orders (top-level for update/delete by ID)
        $group->put('/post-orders/{id}', [SiteController::class, 'updatePostOrder'])
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));
        $group->delete('/post-orders/{id}', [SiteController::class, 'deletePostOrder'])
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Guards ───────────────────────────────────
        $group->group('/guards', function (RouteCollectorProxy $guards): void {
            $guards->get('', [GuardController::class, 'index']);
            $guards->post('', [GuardController::class, 'create']);
            $guards->get('/skills', [GuardController::class, 'listSkills']);
            $guards->post('/skills', [GuardController::class, 'createSkill']);
            $guards->get('/documents/expiring', [GuardController::class, 'expiringDocuments']);
            $guards->get('/{id}', [GuardController::class, 'show']);
            $guards->put('/{id}', [GuardController::class, 'update']);
            $guards->delete('/{id}', [GuardController::class, 'delete']);
            $guards->post('/{id}/suspend', [GuardController::class, 'suspend']);
            $guards->post('/{id}/activate', [GuardController::class, 'activate']);
            $guards->post('/{id}/skills', [GuardController::class, 'assignSkill']);
            $guards->delete('/{guardId}/skills/{skillId}', [GuardController::class, 'removeSkill']);
            $guards->get('/{id}/documents', [GuardController::class, 'listDocuments']);
            $guards->post('/{id}/documents', [GuardController::class, 'addDocument']);
            $guards->post('/documents/{docId}/verify', [GuardController::class, 'verifyDocument']);
            $guards->post('/bulk-import', [GuardController::class, 'bulkImport']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Clients ──────────────────────────────────
        $group->group('/clients', function (RouteCollectorProxy $clients): void {
            $clients->get('', [ClientController::class, 'index']);
            $clients->post('', [ClientController::class, 'create']);
            $clients->get('/{id}', [ClientController::class, 'show']);
            $clients->put('/{id}', [ClientController::class, 'update']);
            $clients->delete('/{id}', [ClientController::class, 'delete']);
            $clients->get('/{id}/contacts', [ClientController::class, 'listContacts']);
            $clients->post('/{id}/contacts', [ClientController::class, 'addContact']);
            $clients->put('/contacts/{contactId}', [ClientController::class, 'updateContact']);
            $clients->delete('/contacts/{contactId}', [ClientController::class, 'deleteContact']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Dashboard ────────────────────────────────
        $group->group('/dashboard', function (RouteCollectorProxy $dash): void {
            $dash->get('/stats', [DashboardController::class, 'stats']);
            $dash->get('/snapshots', [DashboardController::class, 'snapshots']);
            $dash->get('/today', [DashboardController::class, 'today']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ══════════════════════════════════════════════
        // PHASE 2: Scheduling & Attendance
        // ══════════════════════════════════════════════

        // ── Shift Templates ──────────────────────────
        $group->group('/shift-templates', function (RouteCollectorProxy $st): void {
            $st->get('', [ShiftController::class, 'listTemplates']);
            $st->post('', [ShiftController::class, 'createTemplate']);
            $st->put('/{id}', [ShiftController::class, 'updateTemplate']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Shifts ───────────────────────────────────
        $group->group('/shifts', function (RouteCollectorProxy $shifts): void {
            $shifts->get('', [ShiftController::class, 'listShifts']);
            $shifts->post('', [ShiftController::class, 'createShift']);
            $shifts->post('/bulk-generate', [ShiftController::class, 'bulkGenerate']);
            $shifts->post('/publish', [ShiftController::class, 'publishShifts']);
            $shifts->get('/open', [ShiftController::class, 'openShifts']);
            $shifts->put('/{id}', [ShiftController::class, 'updateShift']);
            $shifts->post('/{id}/cancel', [ShiftController::class, 'cancelShift']);
            $shifts->post('/{id}/confirm', [ShiftController::class, 'confirmShift']);
            $shifts->post('/{id}/claim', [ShiftController::class, 'claimShift']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Shift Swap Requests ──────────────────────
        $group->group('/swap-requests', function (RouteCollectorProxy $swaps): void {
            $swaps->get('', [ShiftController::class, 'listSwapRequests']);
            $swaps->post('', [ShiftController::class, 'createSwapRequest']);
            $swaps->post('/{id}/approve', [ShiftController::class, 'approveSwap']);
            $swaps->post('/{id}/reject', [ShiftController::class, 'rejectSwap']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Time Clock ───────────────────────────────
        $group->group('/time-clock', function (RouteCollectorProxy $tc): void {
            $tc->post('/clock-in', [TimeClockController::class, 'clockIn']);
            $tc->post('/clock-out', [TimeClockController::class, 'clockOut']);
            $tc->get('/status', [TimeClockController::class, 'status']);
            $tc->get('/site/{siteId}/active', [TimeClockController::class, 'activeBySite']);
            $tc->get('/history', [TimeClockController::class, 'history']);
            // Breaks
            $tc->get('/breaks/configs', [TimeClockController::class, 'listBreakConfigs']);
            $tc->post('/breaks/configs', [TimeClockController::class, 'createBreakConfig']);
            $tc->post('/breaks/start', [TimeClockController::class, 'startBreak']);
            $tc->post('/breaks/{id}/end', [TimeClockController::class, 'endBreak']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Attendance ───────────────────────────────
        $group->group('/attendance', function (RouteCollectorProxy $att): void {
            $att->get('', [TimeClockController::class, 'attendanceByDate']);
            $att->get('/guard/{guardId}', [TimeClockController::class, 'attendanceByGuard']);
            $att->get('/unreconciled', [TimeClockController::class, 'unreconciled']);
            $att->post('/{id}/reconcile', [TimeClockController::class, 'reconcile']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Passdown Logs ────────────────────────────
        $group->group('/passdowns', function (RouteCollectorProxy $pd): void {
            $pd->post('', [PassdownController::class, 'create']);
            $pd->get('/site/{siteId}', [PassdownController::class, 'listBySite']);
            $pd->get('/unacknowledged', [PassdownController::class, 'unacknowledged']);
            $pd->post('/{id}/acknowledge', [PassdownController::class, 'acknowledge']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

    });

    // ── CORS Preflight ──────────────────────────────
    $app->options('/{routes:.+}', function (Request $request, Response $response): Response {
        return $response;
    });

    // ── 404 Catch-all ───────────────────────────────
    $app->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], '/{routes:.+}', function (Request $request, Response $response): Response {
        $payload = json_encode([
            'error' => 'Not Found',
            'message' => 'The requested endpoint does not exist.',
            'status' => 404,
        ]);

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(404);
    });
};
