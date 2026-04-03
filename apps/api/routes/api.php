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
use Guard51\Module\ClientPortal\ClientPortalController;
use Guard51\Module\Chat\ChatController;
use Guard51\Module\Dashboard\DashboardController;
use Guard51\Module\Feature\FeatureController;
use Guard51\Module\Upload\FileUploadController;
use Guard51\Module\Dispatch\DispatchController;
use Guard51\Module\Guard\GuardController;
use Guard51\Module\Incident\IncidentController;
use Guard51\Module\Notification\NotificationController;
use Guard51\Module\Analytics\AnalyticsController;
use Guard51\Module\License\LicenseController;
use Guard51\Module\Security\SecurityController;
use Guard51\Module\UserManagement\UserManagementController;
use Guard51\Module\Parking\ParkingController;
use Guard51\Module\VehiclePatrol\VehiclePatrolController;
use Guard51\Module\Visitor\VisitorController;
use Guard51\Module\Invoice\InvoiceController;
use Guard51\Module\Panic\PanicController;
use Guard51\Module\Passdown\PassdownController;
use Guard51\Module\Payroll\PayrollController;
use Guard51\Module\Report\ReportController;
use Guard51\Module\Scheduling\ShiftController;
use Guard51\Module\Site\SiteController;
use Guard51\Module\Task\TaskController;
use Guard51\Module\TimeClock\TimeClockController;
use Guard51\Module\Tour\TourController;
use Guard51\Module\Tracking\TrackingController;
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
            $auth->put('/profile', [AuthController::class, 'updateProfile']);
            $auth->post('/change-password', [AuthController::class, 'changePassword']);
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
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR, UserRole::GUARD, UserRole::DISPATCHER))
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
            $tenants->post('', [TenantController::class, 'createTenant']);
            $tenants->get('/{id}', [TenantController::class, 'show']);
            $tenants->delete('/{id}', [TenantController::class, 'deleteTenant']);
            $tenants->post('/{id}/suspend', [TenantController::class, 'suspend']);
            $tenants->post('/{id}/reactivate', [TenantController::class, 'reactivate']);
            $tenants->post('/{id}/activate', [TenantController::class, 'reactivate']);
            $tenants->post('/{id}/subscription', [TenantController::class, 'updateSubscription']);
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
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR))
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
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR))
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
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Dashboard ────────────────────────────────
        $group->group('/dashboard', function (RouteCollectorProxy $dash): void {
            $dash->get('/stats', [DashboardController::class, 'stats']);
            $dash->get('/snapshots', [DashboardController::class, 'snapshots']);
            $dash->get('/today', [DashboardController::class, 'today']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR, UserRole::DISPATCHER, UserRole::GUARD))
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
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR))
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
            $shifts->get('/available-guards', [ShiftController::class, 'availableGuards']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Shift Swap Requests ──────────────────────
        $group->group('/swap-requests', function (RouteCollectorProxy $swaps): void {
            $swaps->get('', [ShiftController::class, 'listSwapRequests']);
            $swaps->post('', [ShiftController::class, 'createSwapRequest']);
            $swaps->post('/{id}/approve', [ShiftController::class, 'approveSwap']);
            $swaps->post('/{id}/reject', [ShiftController::class, 'rejectSwap']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR, UserRole::GUARD))
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
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR, UserRole::GUARD))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Attendance ───────────────────────────────
        $group->group('/attendance', function (RouteCollectorProxy $att): void {
            $att->get('', [TimeClockController::class, 'attendanceByDate']);
            $att->get('/guard/{guardId}', [TimeClockController::class, 'attendanceByGuard']);
            $att->get('/unreconciled', [TimeClockController::class, 'unreconciled']);
            $att->post('/bulk-reconcile', [TimeClockController::class, 'bulkReconcile']);
            $att->post('/{id}/reconcile', [TimeClockController::class, 'reconcile']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Passdown Logs ────────────────────────────
        $group->group('/passdowns', function (RouteCollectorProxy $pd): void {
            $pd->post('', [PassdownController::class, 'create']);
            $pd->get('/site/{siteId}', [PassdownController::class, 'listBySite']);
            $pd->get('/unacknowledged', [PassdownController::class, 'unacknowledged']);
            $pd->post('/{id}/acknowledge', [PassdownController::class, 'acknowledge']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR, UserRole::GUARD))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ══════════════════════════════════════════════
        // PHASE 3: Tracking, Tours & Panic
        // ══════════════════════════════════════════════

        // ── Live Tracking ────────────────────────────
        $group->group('/tracking', function (RouteCollectorProxy $tr): void {
            $tr->post('/location', [TrackingController::class, 'recordLocation']);
            $tr->post('/batch', [TrackingController::class, 'recordBatch']);
            $tr->get('/live', [TrackingController::class, 'liveLocations']);
            $tr->get('/guard/{guardId}/latest', [TrackingController::class, 'latestLocation']);
            $tr->get('/guard/{guardId}/path', [TrackingController::class, 'guardPath']);
            // Geofence alerts
            $tr->get('/geofence-alerts', [TrackingController::class, 'geofenceAlerts']);
            $tr->get('/geofence-alerts/recent', [TrackingController::class, 'recentGeofenceAlerts']);
            $tr->post('/geofence-alerts/{id}/acknowledge', [TrackingController::class, 'acknowledgeGeofenceAlert']);
            // Idle alerts
            $tr->get('/idle-alerts', [TrackingController::class, 'idleAlerts']);
            $tr->post('/idle-alerts/{id}/acknowledge', [TrackingController::class, 'acknowledgeIdleAlert']);
            $tr->post('/detect-idle', [TrackingController::class, 'detectIdle']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR, UserRole::DISPATCHER))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Site Tours ───────────────────────────────
        $group->group('/tours', function (RouteCollectorProxy $tours): void {
            // Checkpoints
            $tours->get('/site/{siteId}/checkpoints', [TourController::class, 'listCheckpoints']);
            $tours->post('/site/{siteId}/checkpoints', [TourController::class, 'createCheckpoint']);
            $tours->put('/checkpoints/{id}', [TourController::class, 'updateCheckpoint']);
            // Sessions
            $tours->post('/start', [TourController::class, 'startTour']);
            $tours->post('/sessions/{sessionId}/scan', [TourController::class, 'recordScan']);
            $tours->post('/sessions/{sessionId}/complete', [TourController::class, 'completeTour']);
            $tours->get('/sessions/{sessionId}', [TourController::class, 'sessionDetail']);
            $tours->get('/site/{siteId}/sessions', [TourController::class, 'sessionsBySite']);
            $tours->get('/guard/{guardId}/sessions', [TourController::class, 'sessionsByGuard']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR, UserRole::GUARD))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Panic Alerts ─────────────────────────────
        $group->group('/panic', function (RouteCollectorProxy $panic): void {
            $panic->post('/trigger', [PanicController::class, 'trigger']);
            $panic->get('/active', [PanicController::class, 'active']);
            $panic->get('/recent', [PanicController::class, 'recent']);
            $panic->post('/{id}/acknowledge', [PanicController::class, 'acknowledge']);
            $panic->post('/{id}/responding', [PanicController::class, 'responding']);
            $panic->post('/{id}/resolve', [PanicController::class, 'resolve']);
            $panic->post('/{id}/false-alarm', [PanicController::class, 'falseAlarm']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR, UserRole::DISPATCHER))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ══════════════════════════════════════════════
        // PHASE 4: Reporting, Incidents, Dispatch, Tasks
        // ══════════════════════════════════════════════

        // ── Reports (DAR, Custom, Watch Mode) ────────
        $group->group('/reports', function (RouteCollectorProxy $rpt): void {
            // DARs
            $rpt->get('/dar', [ReportController::class, 'listDARs']);
            $rpt->post('/dar', [ReportController::class, 'createDAR']);
            $rpt->post('/dar/{id}/submit', [ReportController::class, 'submitDAR']);
            $rpt->post('/dar/{id}/review', [ReportController::class, 'reviewDAR']);
            // Custom templates
            $rpt->get('/templates', [ReportController::class, 'listTemplates']);
            $rpt->post('/templates', [ReportController::class, 'createTemplate']);
            // Custom submissions
            $rpt->post('/custom', [ReportController::class, 'submitCustomReport']);
            $rpt->get('/custom/template/{templateId}', [ReportController::class, 'listSubmissions']);
            // Watch mode
            $rpt->post('/watch', [ReportController::class, 'logWatch']);
            $rpt->get('/watch/site/{siteId}', [ReportController::class, 'watchFeed']);
            $rpt->get('/watch-feed', [ReportController::class, 'recentWatchFeed']);
            $rpt->get('/watch/recent', [ReportController::class, 'recentWatchFeed']);
            // Export & sharing
            $rpt->get('/dar/{id}/export', [ReportController::class, 'exportDAR']);
            $rpt->get('/client/site/{siteId}', [ReportController::class, 'clientReports']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR, UserRole::GUARD))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Incidents ────────────────────────────────
        $group->group('/incidents', function (RouteCollectorProxy $inc): void {
            $inc->get('', [IncidentController::class, 'list']);
            $inc->get('/active', [IncidentController::class, 'active']);
            $inc->post('', [IncidentController::class, 'create']);
            $inc->post('/{id}/status', [IncidentController::class, 'updateStatus']);
            $inc->post('/{id}/resolve', [IncidentController::class, 'resolve']);
            $inc->post('/{id}/escalate', [IncidentController::class, 'escalate']);
            $inc->get('/{id}/escalations', [IncidentController::class, 'escalations']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR, UserRole::DISPATCHER, UserRole::GUARD))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Dispatch ─────────────────────────────────
        $group->group('/dispatch', function (RouteCollectorProxy $dsp): void {
            $dsp->get('/active', [DispatchController::class, 'activeCalls']);
            $dsp->get('/recent', [DispatchController::class, 'recentCalls']);
            $dsp->post('', [DispatchController::class, 'createCall']);
            $dsp->post('/{id}/assign', [DispatchController::class, 'assignGuard']);
            $dsp->post('/assignments/{assignmentId}/status', [DispatchController::class, 'updateAssignment']);
            $dsp->post('/{id}/resolve', [DispatchController::class, 'resolveCall']);
            $dsp->get('/nearest-guards', [DispatchController::class, 'nearestGuards']);
            $dsp->get('/{id}/assignments', [DispatchController::class, 'assignments']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR, UserRole::DISPATCHER))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Tasks ────────────────────────────────────
        $group->group('/tasks', function (RouteCollectorProxy $tsk): void {
            $tsk->get('', [TaskController::class, 'list']);
            $tsk->post('', [TaskController::class, 'create']);
            $tsk->post('/{id}/status', [TaskController::class, 'updateStatus']);
            $tsk->get('/guard/{guardId}', [TaskController::class, 'byGuard']);
            $tsk->get('/site/{siteId}', [TaskController::class, 'bySite']);
            $tsk->get('/overdue', [TaskController::class, 'overdue']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ══════════════════════════════════════════════
        // PHASE 5: Finance & Billing
        // ══════════════════════════════════════════════

        // ── Invoices ─────────────────────────────────
        $group->group('/invoices', function (RouteCollectorProxy $inv): void {
            $inv->get('', [InvoiceController::class, 'list']);
            $inv->post('', [InvoiceController::class, 'create']);
            $inv->get('/overdue', [InvoiceController::class, 'overdue']);
            $inv->get('/{id}', [InvoiceController::class, 'detail']);
            $inv->post('/{id}/payment', [InvoiceController::class, 'recordPayment']);
            $inv->post('/{id}/send', [InvoiceController::class, 'send']);
            $inv->post('/{id}/convert', [InvoiceController::class, 'convertEstimate']);
            $inv->get('/{id}/export', [InvoiceController::class, 'export']);
            $inv->get('/{id}/pdf', [InvoiceController::class, 'downloadPdf']);
            $inv->post('/generate', [InvoiceController::class, 'generateFromTimeClock']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Payroll ──────────────────────────────────
        $group->group('/payroll', function (RouteCollectorProxy $pay): void {
            $pay->get('/periods', [PayrollController::class, 'listPeriods']);
            $pay->post('/periods', [PayrollController::class, 'createPeriod']);
            $pay->get('/periods/{id}', [PayrollController::class, 'periodDetail']);
            $pay->post('/periods/{id}/items', [PayrollController::class, 'addItem']);
            $pay->post('/periods/{id}/calculate', [PayrollController::class, 'calculateFromTimeClock']);
            $pay->post('/periods/{id}/approve', [PayrollController::class, 'approve']);
            $pay->get('/periods/{id}/export', [PayrollController::class, 'exportCsv']);
            $pay->get('/guard/{guardId}/payslips', [PayrollController::class, 'guardPayslips']);
            $pay->get('/rates', [PayrollController::class, 'listRates']);
            $pay->post('/rates', [PayrollController::class, 'createRate']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ══════════════════════════════════════════════
        // PHASE 6: Client Portal, Chat, Notifications
        // ══════════════════════════════════════════════

        // ── Client Portal ────────────────────────────
        $group->group('/client-portal', function (RouteCollectorProxy $cp): void {
            $cp->get('/profile', [ClientPortalController::class, 'profile']);
            $cp->get('/reports', [ClientPortalController::class, 'reports']);
            $cp->get('/invoices', [ClientPortalController::class, 'invoices']);
            $cp->get('/incidents', [ClientPortalController::class, 'incidents']);
            $cp->get('/tracking', [ClientPortalController::class, 'tracking']);
            $cp->get('/attendance', [ClientPortalController::class, 'attendance']);
            // Employee onboarding
            $cp->get('/employees', [ClientPortalController::class, 'listEmployees']);
            $cp->post('/employees', [ClientPortalController::class, 'addEmployee']);
            $cp->delete('/employees/{id}', [ClientPortalController::class, 'removeEmployee']);
        })
            ->add(new RoleMiddleware(UserRole::CLIENT))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Chat ─────────────────────────────────────
        $group->group('/chat', function (RouteCollectorProxy $ch): void {
            $ch->get('/conversations', [ChatController::class, 'listConversations']);
            $ch->post('/conversations', [ChatController::class, 'createConversation']);
            $ch->get('/conversations/{id}/messages', [ChatController::class, 'messages']);
            $ch->post('/conversations/{id}/messages', [ChatController::class, 'sendMessage']);
            $ch->post('/conversations/{id}/read', [ChatController::class, 'markRead']);
            $ch->get('/unread-count', [ChatController::class, 'unreadCount']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── File Uploads ─────────────────────────────
        $group->group("/uploads", function (RouteCollectorProxy $up) {
            $up->post("", [FileUploadController::class, "upload"]);
            $up->get("/{path:.*}", [FileUploadController::class, "serve"]);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Notifications ────────────────────────────
        $group->group('/notifications', function (RouteCollectorProxy $nt): void {
            $nt->get('', [NotificationController::class, 'list']);
            $nt->post('/{id}/read', [NotificationController::class, 'markRead']);
            $nt->post('/read-all', [NotificationController::class, 'markAllRead']);
            $nt->post('/device', [NotificationController::class, 'registerDevice']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ══════════════════════════════════════════════
        // ══════════════════════════════════════════════

        // PHASE 8: Advanced Features


        // ── User Management ────────────────────────────

        $group->group('/users', function (RouteCollectorProxy $um): void {

            $um->get(''  , [UserManagementController::class, 'list']);

            $um->put('/{id}/role', [UserManagementController::class, 'changeRole']);

            $um->get('/{id}/permissions', [UserManagementController::class, 'permissions']);

            $um->post('/{id}/permissions', [UserManagementController::class, 'setPermission']);

            $um->delete('/{id}/permissions/{moduleKey}', [UserManagementController::class, 'revokePermission']);

            $um->get('/modules', [UserManagementController::class, 'modules']);

        })

            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));


        // ══════════════════════════════════════════════



        // ── Security (2FA + Audit Log) ─────────────────

        $group->group('/security', function (RouteCollectorProxy $sec): void {

            $sec->post('/2fa/setup', [SecurityController::class, 'setup2FA']);

            $sec->post('/2fa/verify', [SecurityController::class, 'verify2FA']);

            $sec->post('/2fa/disable', [SecurityController::class, 'disable2FA']);

            $sec->get('/2fa/status', [SecurityController::class, 'status2FA']);

            $sec->get('/audit-log', [SecurityController::class, 'auditLog']);

        })

            ->add($container->get(TenantMiddleware::class))

            ->add($container->get(AuthMiddleware::class));

        // ── Support Tickets ────────────────────────────
        $group->group('/support', function (RouteCollectorProxy $sup): void {
            $sup->post('/tickets', [\Guard51\Module\Support\SupportController::class, 'createTicket']);
            $sup->get('/tickets', [\Guard51\Module\Support\SupportController::class, 'listTickets']);
            $sup->post('/tickets/{id}/resolve', [\Guard51\Module\Support\SupportController::class, 'resolveTicket']);
            $sup->post('/tickets/{id}/close', [\Guard51\Module\Support\SupportController::class, 'closeTicket']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Help Center (public for authenticated users) ──
        $group->get('/help/articles', [\Guard51\Module\Support\SupportController::class, 'listArticles'])
            ->add($container->get(AuthMiddleware::class));
        $group->get('/help/articles/{id}', [\Guard51\Module\Support\SupportController::class, 'getArticle'])
            ->add($container->get(AuthMiddleware::class));

        // ── Admin: Support tickets across all tenants ──
        $group->get('/admin/stats', [TenantController::class, 'stats'])
            ->add(new RoleMiddleware(UserRole::SUPER_ADMIN))
            ->add($container->get(AuthMiddleware::class));
        $group->get('/admin/support/tickets', [\Guard51\Module\Support\SupportController::class, 'adminListTickets'])
            ->add(new RoleMiddleware(UserRole::SUPER_ADMIN))
            ->add($container->get(AuthMiddleware::class));
        $group->post('/admin/support/tickets/{id}/assign', [\Guard51\Module\Support\SupportController::class, 'assignTicket'])
            ->add(new RoleMiddleware(UserRole::SUPER_ADMIN))
            ->add($container->get(AuthMiddleware::class));

        // ── Top-level aliases for frontend convenience ──
        $group->get('/audit-log', [SecurityController::class, 'auditLog'])
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));
        $group->get('/auth/2fa/status', [SecurityController::class, 'status2FA'])
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));
        $group->post('/auth/2fa/enable', [SecurityController::class, 'setup2FA'])
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));
        $group->post('/auth/2fa/disable', [SecurityController::class, 'disable2FA'])
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));



        // ── Guard Licenses ─────────────────────────────

        $group->group('/licenses', function (RouteCollectorProxy $lic): void {

            $lic->post(''  , [LicenseController::class, 'create']);

            $lic->get('/guard/{guardId}', [LicenseController::class, 'byGuard']);

            $lic->get('/expiring', [LicenseController::class, 'expiringSoon']);

            $lic->get('/expired', [LicenseController::class, 'expired']);

        })

            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));



        // ── Analytics ──────────────────────────────────

        $group->group('/analytics', function (RouteCollectorProxy $an): void {

            $an->get('/kpis', [AnalyticsController::class, 'kpis']);

            $an->get('/guard/{guardId}/performance', [AnalyticsController::class, 'guardPerformance']);

        })

            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // PHASE 7: Operations & Extended Apps
        // ══════════════════════════════════════════════

        // ── Vehicle Patrol ───────────────────────────
        $group->group('/vehicle-patrol', function (RouteCollectorProxy $vp): void {
            $vp->get('/vehicles', [VehiclePatrolController::class, 'listVehicles']);
            $vp->post('/vehicles', [VehiclePatrolController::class, 'createVehicle']);
            $vp->get('/routes', [VehiclePatrolController::class, 'listRoutes']);
            $vp->post('/routes', [VehiclePatrolController::class, 'createRoute']);
            $vp->post('/hits', [VehiclePatrolController::class, 'recordHit']);
            $vp->get('/routes/{routeId}/hits', [VehiclePatrolController::class, 'routeHits']);
            $vp->get('/missed', [VehiclePatrolController::class, 'missedPatrols']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Visitors ─────────────────────────────────
        $group->group('/visitors', function (RouteCollectorProxy $vis): void {
            $vis->post('/check-in', [VisitorController::class, 'checkIn']);
            $vis->post('/{id}/check-out', [VisitorController::class, 'checkOut']);
            $vis->get('/site/{siteId}', [VisitorController::class, 'listBySite']);
            $vis->get('/site/{siteId}/checked-in', [VisitorController::class, 'listCheckedIn']);
            $vis->get('/search', [VisitorController::class, 'search']);
            // Appointments
            $vis->post('/appointments', [VisitorController::class, 'createAppointment']);
            $vis->get('/appointments', [VisitorController::class, 'listAppointments']);
            $vis->get('/appointments/{id}', [VisitorController::class, 'getAppointment']);
            $vis->post('/appointments/verify', [VisitorController::class, 'verifyCode']);
            $vis->post('/appointments/{id}/check-in', [VisitorController::class, 'appointmentCheckIn']);
            $vis->post('/appointments/{id}/check-out', [VisitorController::class, 'appointmentCheckOut']);
            $vis->post('/appointments/{id}/cancel', [VisitorController::class, 'cancelAppointment']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR, UserRole::GUARD))
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

        // ── Parking ──────────────────────────────────
        $group->group('/parking', function (RouteCollectorProxy $pk): void {
            $pk->get('/areas', [ParkingController::class, 'listAreas']);
            $pk->post('/areas', [ParkingController::class, 'createArea']);
            $pk->post('/areas/{areaId}/lots', [ParkingController::class, 'createLot']);
            $pk->get('/vehicles', [ParkingController::class, 'listParkedAll']);
            $pk->post('/vehicles', [ParkingController::class, 'logEntry']);
            $pk->post('/vehicles/{id}/exit', [ParkingController::class, 'logExit']);
            $pk->get('/site/{siteId}/parked', [ParkingController::class, 'listParked']);
            $pk->post('/incidents', [ParkingController::class, 'reportIncident']);
            $pk->get('/incident-types', [ParkingController::class, 'listIncidentTypes']);
            $pk->post('/incident-types', [ParkingController::class, 'createIncidentType']);
        })
            ->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR, UserRole::GUARD))
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
