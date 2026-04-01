<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Guard51\Middleware\AuthMiddleware;
use Guard51\Middleware\CorsMiddleware;
use Guard51\Middleware\TenantMiddleware;
use Guard51\Module\Auth\AuthController;
use Guard51\Module\AppDistribution\AppReleaseController;
use Guard51\Module\AppDistribution\AppClientController;
use Guard51\Module\Client\ClientController;
use Guard51\Module\ClientPortal\ClientPortalController;
use Guard51\Module\Chat\ChatController;
use Guard51\Module\Analytics\AnalyticsController;
use Guard51\Module\License\LicenseController;
use Guard51\Module\Security\SecurityController;
use Guard51\Module\UserManagement\UserManagementController;
use Guard51\Module\Dashboard\DashboardController;
use Guard51\Module\Feature\FeatureController;
use Guard51\Module\Dispatch\DispatchController;
use Guard51\Module\Guard\GuardController;
use Guard51\Module\Incident\IncidentController;
use Guard51\Module\Notification\NotificationController;
use Guard51\Module\Parking\ParkingController;
use Guard51\Module\VehiclePatrol\VehiclePatrolController;
use Guard51\Module\Visitor\VisitorController;
use Guard51\Module\Invoice\InvoiceController;
use Guard51\Module\Passdown\PassdownController;
use Guard51\Module\Payroll\PayrollController;
use Guard51\Module\Panic\PanicController;
use Guard51\Module\Report\ReportController;
use Guard51\Module\Scheduling\ShiftController;
use Guard51\Module\Site\SiteController;
use Guard51\Module\Task\TaskController;
use Guard51\Module\TimeClock\TimeClockController;
use Guard51\Module\Tour\TourController;
use Guard51\Module\Tracking\TrackingController;
use Guard51\Module\Onboarding\InvitationController;
use Guard51\Module\Onboarding\OnboardingController;
use Guard51\Module\Subscription\SubscriptionController;
use Guard51\Module\Subscription\PlanController;
use Guard51\Module\Tenant\TenantController;
use Guard51\Module\Usage\UsageController;
use Guard51\Repository\AuditLogRepository;
use Guard51\Repository\AppReleaseRepository;
use Guard51\Repository\AppDownloadLogRepository;
use Guard51\Repository\AttendanceRecordRepository;
use Guard51\Repository\BreakConfigRepository;
use Guard51\Repository\BreakLogRepository;
use Guard51\Repository\ClientContactRepository;
use Guard51\Repository\ClientRepository;
use Guard51\Repository\ClientUserRepository;
use Guard51\Repository\ChatConversationRepository;
use Guard51\Repository\ChatMessageRepository;
use Guard51\Repository\ChatParticipantRepository;
use Guard51\Repository\CustomReportSubmissionRepository;
use Guard51\Repository\CustomReportTemplateRepository;
use Guard51\Repository\DailyActivityReportRepository;
use Guard51\Repository\DailySnapshotRepository;
use Guard51\Repository\GuardLicenseRepository;
use Guard51\Repository\GuardPerformanceIndexRepository;
use Guard51\Repository\TwoFactorSecretRepository;
use Guard51\Repository\PropertyRepository;
use Guard51\Repository\PermissionRepository;
use Guard51\Repository\DeviceTokenRepository;
use Guard51\Repository\DispatchAssignmentRepository;
use Guard51\Repository\DispatchCallRepository;
use Guard51\Repository\FeatureModuleRepository;
use Guard51\Repository\GeofenceAlertRepository;
use Guard51\Repository\GuardDocumentRepository;
use Guard51\Repository\GuardLocationRepository;
use Guard51\Repository\GuardRepository;
use Guard51\Repository\GuardSkillRepository;
use Guard51\Repository\IdleAlertRepository;
use Guard51\Repository\IncidentEscalationRepository;
use Guard51\Repository\IncidentReportRepository;
use Guard51\Repository\NotificationRepository;
use Guard51\Repository\ParkingAreaRepository;
use Guard51\Repository\ParkingIncidentRepository;
use Guard51\Repository\ParkingIncidentTypeRepository;
use Guard51\Repository\ParkingLotRepository;
use Guard51\Repository\ParkingVehicleRepository;
use Guard51\Repository\PatrolVehicleRepository;
use Guard51\Repository\InvoiceItemRepository;
use Guard51\Repository\InvoicePaymentRepository;
use Guard51\Repository\InvoiceRepository;
use Guard51\Repository\PanicAlertRepository;
use Guard51\Repository\PassdownLogRepository;
use Guard51\Repository\PayRateMultiplierRepository;
use Guard51\Repository\PayrollItemRepository;
use Guard51\Repository\PayrollPeriodRepository;
use Guard51\Repository\PayslipRepository;
use Guard51\Repository\RefreshTokenRepository;
use Guard51\Repository\PostOrderRepository;
use Guard51\Repository\ShiftRepository;
use Guard51\Repository\ShiftSwapRequestRepository;
use Guard51\Repository\ShiftTemplateRepository;
use Guard51\Repository\SiteRepository;
use Guard51\Repository\TaskRepository;
use Guard51\Repository\TimeClockRepository;
use Guard51\Repository\VehiclePatrolHitRepository;
use Guard51\Repository\VehiclePatrolRouteRepository;
use Guard51\Repository\VisitorRepository;
use Guard51\Repository\VisitorVehicleRepository;
use Guard51\Repository\TourCheckpointRepository;
use Guard51\Repository\TourCheckpointScanRepository;
use Guard51\Repository\TourSessionRepository;
use Guard51\Repository\WatchModeLogRepository;
use Guard51\Repository\SubscriptionInvoiceRepository;
use Guard51\Repository\SubscriptionPlanRepository;
use Guard51\Repository\SubscriptionRepository;
use Guard51\Repository\TenantFeatureModuleRepository;
use Guard51\Repository\TenantInvitationRepository;
use Guard51\Repository\TenantRepository;
use Guard51\Repository\TenantAppConfigRepository;
use Guard51\Repository\TenantUsageMetricRepository;
use Guard51\Repository\UserRepository;
use Guard51\Service\AppDistributionService;
use Guard51\Service\ClientService;
use Guard51\Service\ChatService;
use Guard51\Service\AuditService;
use Guard51\Service\TwoFactorService;
use Guard51\Service\LicenseService;
use Guard51\Service\AnalyticsService;
use Guard51\Service\UserManagementService;
use Guard51\Service\DispatchService;
use Guard51\Service\FeatureService;
use Guard51\Service\GeofenceService;
use Guard51\Service\GuardService;
use Guard51\Service\IncidentService;
use Guard51\Service\InvoiceService;
use Guard51\Service\LocationService;
use Guard51\Service\NotificationService;
use Guard51\Service\ParkingService;
use Guard51\Service\PanicService;
use Guard51\Service\PassdownService;
use Guard51\Service\PayrollService;
use Guard51\Service\ReportService;
use Guard51\Service\VehiclePatrolService;
use Guard51\Service\VisitorService;
use Guard51\Service\ShiftService;
use Guard51\Service\SiteService;
use Guard51\Service\TaskService;
use Guard51\Service\TimeClockService;
use Guard51\Service\TourService;
use Guard51\Service\FileStorageService;
use Guard51\Service\GpsService;
use Guard51\Service\InvitationService;
use Guard51\Service\JwtService;
use Guard51\Service\OnboardingService;
use Guard51\Service\PaystackService;
use Guard51\Service\PdfService;
use Guard51\Service\QueueService;
use Guard51\Service\SubscriptionService;
use Guard51\Service\TermiiService;
use Guard51\Service\ValidationService;
use Guard51\Service\ZeptoMailService;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Predis\Client as RedisClient;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

$settings = require __DIR__ . '/settings.php';

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    // Settings
    'settings' => $settings,

    // Logger
    LoggerInterface::class => function (ContainerInterface $c): LoggerInterface {
        $settings = $c->get('settings')['logging'];
        $logger = new Logger($settings['name']);
        $logger->pushProcessor(new UidProcessor());
        $logger->pushHandler(new StreamHandler($settings['path'], $settings['level']));
        return $logger;
    },

    // Redis
    RedisClient::class => function (ContainerInterface $c): RedisClient {
        $settings = $c->get('settings')['redis'];
        return new RedisClient([
            'scheme' => 'tcp',
            'host' => $settings['host'],
            'port' => $settings['port'],
        ]);
    },

    // Doctrine EntityManager
    EntityManagerInterface::class => function (ContainerInterface $c): EntityManagerInterface {
        $settings = $c->get('settings')['database'];

        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [__DIR__ . '/../src/Entity'],
            isDevMode: $c->get('settings')['app']['debug'],
        );

        // Register tenant filter for automatic multi-tenant scoping
        $config->addFilter('tenant_filter', \Guard51\Filter\TenantFilter::class);

        $connection = DriverManager::getConnection($settings, $config);
        $em = new EntityManager($connection, $config);

        // Filter is registered but NOT enabled by default.
        // TenantMiddleware enables it per-request with the resolved tenant_id.
        // Super admin requests leave it disabled to see all tenants.

        return $em;
    },

    // Services
    ZeptoMailService::class => function (ContainerInterface $c): ZeptoMailService {
        return new ZeptoMailService(
            $c->get('settings')['zeptomail'],
            $c->get(LoggerInterface::class),
        );
    },

    TermiiService::class => function (ContainerInterface $c): TermiiService {
        return new TermiiService(
            $c->get('settings')['termii'],
            $c->get(LoggerInterface::class),
        );
    },

    PaystackService::class => function (ContainerInterface $c): PaystackService {
        return new PaystackService(
            $c->get('settings')['paystack'],
            $c->get(LoggerInterface::class),
        );
    },

    PdfService::class => function (ContainerInterface $c): PdfService {
        return new PdfService(
            $c->get(LoggerInterface::class),
        );
    },

    FileStorageService::class => function (ContainerInterface $c): FileStorageService {
        return new FileStorageService(
            $c->get('settings')['storage'],
            $c->get(LoggerInterface::class),
        );
    },

    QueueService::class => function (ContainerInterface $c): QueueService {
        return new QueueService(
            $c->get(RedisClient::class),
            $c->get(LoggerInterface::class),
        );
    },

    GpsService::class => function (ContainerInterface $c): GpsService {
        return new GpsService(
            $c->get(RedisClient::class),
            $c->get(EntityManagerInterface::class),
            $c->get(LoggerInterface::class),
        );
    },

    // Repositories
    TenantRepository::class => function (ContainerInterface $c): TenantRepository {
        return new TenantRepository($c->get(EntityManagerInterface::class));
    },

    UserRepository::class => function (ContainerInterface $c): UserRepository {
        return new UserRepository($c->get(EntityManagerInterface::class));
    },

    RefreshTokenRepository::class => function (ContainerInterface $c): RefreshTokenRepository {
        return new RefreshTokenRepository($c->get(EntityManagerInterface::class));
    },

    // AuditLogRepository moved to Phase 8 block

    FeatureModuleRepository::class => function (ContainerInterface $c): FeatureModuleRepository {
        return new FeatureModuleRepository($c->get(EntityManagerInterface::class));
    },

    TenantFeatureModuleRepository::class => function (ContainerInterface $c): TenantFeatureModuleRepository {
        return new TenantFeatureModuleRepository($c->get(EntityManagerInterface::class));
    },

    SubscriptionPlanRepository::class => function (ContainerInterface $c): SubscriptionPlanRepository {
        return new SubscriptionPlanRepository($c->get(EntityManagerInterface::class));
    },

    SubscriptionRepository::class => function (ContainerInterface $c): SubscriptionRepository {
        return new SubscriptionRepository($c->get(EntityManagerInterface::class));
    },

    SubscriptionInvoiceRepository::class => function (ContainerInterface $c): SubscriptionInvoiceRepository {
        return new SubscriptionInvoiceRepository($c->get(EntityManagerInterface::class));
    },

    TenantUsageMetricRepository::class => function (ContainerInterface $c): TenantUsageMetricRepository {
        return new TenantUsageMetricRepository($c->get(EntityManagerInterface::class));
    },

    // Middleware
    CorsMiddleware::class => function (ContainerInterface $c): CorsMiddleware {
        return new CorsMiddleware($c->get('settings'));
    },

    TenantMiddleware::class => function (ContainerInterface $c): TenantMiddleware {
        return new TenantMiddleware($c->get(EntityManagerInterface::class));
    },

    AuthMiddleware::class => function (ContainerInterface $c): AuthMiddleware {
        return new AuthMiddleware($c->get(JwtService::class));
    },

    // Auth Services
    JwtService::class => function (ContainerInterface $c): JwtService {
        return new JwtService(
            $c->get('settings')['jwt'],
            $c->get(LoggerInterface::class),
        );
    },

    ValidationService::class => function (ContainerInterface $c): ValidationService {
        return new ValidationService();
    },

    // Controllers
    AuthController::class => function (ContainerInterface $c): AuthController {
        return new AuthController(
            $c->get(UserRepository::class),
            $c->get(TenantRepository::class),
            $c->get(RefreshTokenRepository::class),
            $c->get(JwtService::class),
            $c->get(ValidationService::class),
            $c->get(ZeptoMailService::class),
            $c->get(LoggerInterface::class),
        );
    },

    // Feature & Subscription Services
    FeatureService::class => function (ContainerInterface $c): FeatureService {
        return new FeatureService(
            $c->get(EntityManagerInterface::class),
            $c->get(LoggerInterface::class),
        );
    },

    SubscriptionService::class => function (ContainerInterface $c): SubscriptionService {
        return new SubscriptionService(
            $c->get(SubscriptionRepository::class),
            $c->get(SubscriptionPlanRepository::class),
            $c->get(SubscriptionInvoiceRepository::class),
            $c->get(PaystackService::class),
            $c->get(FeatureService::class),
            $c->get(ZeptoMailService::class),
            $c->get(LoggerInterface::class),
        );
    },

    // Phase 0D Controllers
    FeatureController::class => function (ContainerInterface $c): FeatureController {
        return new FeatureController(
            $c->get(FeatureModuleRepository::class),
            $c->get(FeatureService::class),
            $c->get(TenantRepository::class),
            $c->get(LoggerInterface::class),
        );
    },

    PlanController::class => function (ContainerInterface $c): PlanController {
        return new PlanController(
            $c->get(SubscriptionPlanRepository::class),
            $c->get(FeatureModuleRepository::class),
            $c->get(ValidationService::class),
            $c->get(LoggerInterface::class),
        );
    },

    SubscriptionController::class => function (ContainerInterface $c): SubscriptionController {
        return new SubscriptionController(
            $c->get(SubscriptionService::class),
            $c->get(SubscriptionRepository::class),
            $c->get(SubscriptionPlanRepository::class),
            $c->get(SubscriptionInvoiceRepository::class),
            $c->get(TenantRepository::class),
            $c->get(ValidationService::class),
            $c->get(LoggerInterface::class),
        );
    },

    UsageController::class => function (ContainerInterface $c): UsageController {
        return new UsageController(
            $c->get(TenantUsageMetricRepository::class),
            $c->get(SubscriptionRepository::class),
            $c->get(SubscriptionPlanRepository::class),
            $c->get(LoggerInterface::class),
        );
    },

    // Phase 0E: Onboarding & Tenant Management
    TenantInvitationRepository::class => function (ContainerInterface $c): TenantInvitationRepository {
        return new TenantInvitationRepository($c->get(EntityManagerInterface::class));
    },

    OnboardingService::class => function (ContainerInterface $c): OnboardingService {
        return new OnboardingService(
            $c->get(TenantRepository::class),
            $c->get(EntityManagerInterface::class),
            $c->get(LoggerInterface::class),
        );
    },

    InvitationService::class => function (ContainerInterface $c): InvitationService {
        return new InvitationService(
            $c->get(TenantInvitationRepository::class),
            $c->get(UserRepository::class),
            $c->get(TenantRepository::class),
            $c->get(ZeptoMailService::class),
            $c->get(LoggerInterface::class),
        );
    },

    OnboardingController::class => function (ContainerInterface $c): OnboardingController {
        return new OnboardingController(
            $c->get(OnboardingService::class),
            $c->get(LoggerInterface::class),
        );
    },

    InvitationController::class => function (ContainerInterface $c): InvitationController {
        return new InvitationController(
            $c->get(InvitationService::class),
            $c->get(JwtService::class),
            $c->get(ValidationService::class),
            $c->get(LoggerInterface::class),
        );
    },

    TenantController::class => function (ContainerInterface $c): TenantController {
        return new TenantController(
            $c->get(TenantRepository::class),
            $c->get(UserRepository::class),
            $c->get(SubscriptionRepository::class),
            $c->get(TenantUsageMetricRepository::class),
            $c->get(JwtService::class),
            $c->get(LoggerInterface::class),
        );
    },

    // Phase 0F: App Distribution Platform
    AppReleaseRepository::class => function (ContainerInterface $c): AppReleaseRepository {
        return new AppReleaseRepository($c->get(EntityManagerInterface::class));
    },

    AppDownloadLogRepository::class => function (ContainerInterface $c): AppDownloadLogRepository {
        return new AppDownloadLogRepository($c->get(EntityManagerInterface::class));
    },

    TenantAppConfigRepository::class => function (ContainerInterface $c): TenantAppConfigRepository {
        return new TenantAppConfigRepository($c->get(EntityManagerInterface::class));
    },

    AppDistributionService::class => function (ContainerInterface $c): AppDistributionService {
        return new AppDistributionService(
            $c->get(AppReleaseRepository::class),
            $c->get(AppDownloadLogRepository::class),
            $c->get(TenantAppConfigRepository::class),
            $c->get(FileStorageService::class),
            $c->get(LoggerInterface::class),
        );
    },

    AppReleaseController::class => function (ContainerInterface $c): AppReleaseController {
        return new AppReleaseController(
            $c->get(AppDistributionService::class),
            $c->get(AppReleaseRepository::class),
            $c->get(LoggerInterface::class),
        );
    },

    AppClientController::class => function (ContainerInterface $c): AppClientController {
        return new AppClientController(
            $c->get(AppDistributionService::class),
            $c->get(TenantAppConfigRepository::class),
            $c->get(LoggerInterface::class),
        );
    },

    // Phase 1: Core Operations
    SiteRepository::class => function (ContainerInterface $c): SiteRepository {
        return new SiteRepository($c->get(EntityManagerInterface::class));
    },
    PostOrderRepository::class => function (ContainerInterface $c): PostOrderRepository {
        return new PostOrderRepository($c->get(EntityManagerInterface::class));
    },
    GuardRepository::class => function (ContainerInterface $c): GuardRepository {
        return new GuardRepository($c->get(EntityManagerInterface::class));
    },
    GuardSkillRepository::class => function (ContainerInterface $c): GuardSkillRepository {
        return new GuardSkillRepository($c->get(EntityManagerInterface::class));
    },
    GuardDocumentRepository::class => function (ContainerInterface $c): GuardDocumentRepository {
        return new GuardDocumentRepository($c->get(EntityManagerInterface::class));
    },
    ClientRepository::class => function (ContainerInterface $c): ClientRepository {
        return new ClientRepository($c->get(EntityManagerInterface::class));
    },
    ClientContactRepository::class => function (ContainerInterface $c): ClientContactRepository {
        return new ClientContactRepository($c->get(EntityManagerInterface::class));
    },
    DailySnapshotRepository::class => function (ContainerInterface $c): DailySnapshotRepository {
        return new DailySnapshotRepository($c->get(EntityManagerInterface::class));
    },

    SiteService::class => function (ContainerInterface $c): SiteService {
        return new SiteService(
            $c->get(SiteRepository::class),
            $c->get(PostOrderRepository::class),
            $c->get(TenantUsageMetricRepository::class),
            $c->get(SubscriptionRepository::class),
            $c->get(SubscriptionPlanRepository::class),
            $c->get(LoggerInterface::class),
        );
    },
    GuardService::class => function (ContainerInterface $c): GuardService {
        return new GuardService(
            $c->get(GuardRepository::class),
            $c->get(GuardSkillRepository::class),
            $c->get(GuardDocumentRepository::class),
            $c->get(UserRepository::class),
            $c->get(TenantUsageMetricRepository::class),
            $c->get(SubscriptionRepository::class),
            $c->get(SubscriptionPlanRepository::class),
            $c->get(EntityManagerInterface::class),
            $c->get(LoggerInterface::class),
        );
    },
    ClientService::class => function (ContainerInterface $c): ClientService {
        return new ClientService(
            $c->get(ClientRepository::class),
            $c->get(ClientContactRepository::class),
            $c->get(TenantUsageMetricRepository::class),
            $c->get(SubscriptionRepository::class),
            $c->get(SubscriptionPlanRepository::class),
            $c->get(LoggerInterface::class),
        );
    },

    SiteController::class => function (ContainerInterface $c): SiteController {
        return new SiteController(
            $c->get(SiteService::class),
            $c->get(ValidationService::class),
            $c->get(LoggerInterface::class),
        );
    },
    GuardController::class => function (ContainerInterface $c): GuardController {
        return new GuardController(
            $c->get(GuardService::class),
            $c->get(FileStorageService::class),
            $c->get(LoggerInterface::class),
        );
    },
    ClientController::class => function (ContainerInterface $c): ClientController {
        return new ClientController(
            $c->get(ClientService::class),
            $c->get(LoggerInterface::class),
        );
    },

    DashboardController::class => function (ContainerInterface $c): DashboardController {
        return new DashboardController(
            $c->get(DailySnapshotRepository::class),
            $c->get(GuardRepository::class),
            $c->get(SiteRepository::class),
            $c->get(ClientRepository::class),
            $c->get(LoggerInterface::class),
        );
    },

    // Phase 2: Scheduling & Attendance
    GeofenceService::class => function (ContainerInterface $c): GeofenceService {
        return new GeofenceService(
            $c->get(EntityManagerInterface::class),
            $c->get(LoggerInterface::class),
        );
    },
    ShiftTemplateRepository::class => fn(ContainerInterface $c) => new ShiftTemplateRepository($c->get(EntityManagerInterface::class)),
    ShiftRepository::class => fn(ContainerInterface $c) => new ShiftRepository($c->get(EntityManagerInterface::class)),
    ShiftSwapRequestRepository::class => fn(ContainerInterface $c) => new ShiftSwapRequestRepository($c->get(EntityManagerInterface::class)),
    TimeClockRepository::class => fn(ContainerInterface $c) => new TimeClockRepository($c->get(EntityManagerInterface::class)),
    AttendanceRecordRepository::class => fn(ContainerInterface $c) => new AttendanceRecordRepository($c->get(EntityManagerInterface::class)),
    BreakConfigRepository::class => fn(ContainerInterface $c) => new BreakConfigRepository($c->get(EntityManagerInterface::class)),
    BreakLogRepository::class => fn(ContainerInterface $c) => new BreakLogRepository($c->get(EntityManagerInterface::class)),
    PassdownLogRepository::class => fn(ContainerInterface $c) => new PassdownLogRepository($c->get(EntityManagerInterface::class)),

    ShiftService::class => function (ContainerInterface $c): ShiftService {
        return new ShiftService(
            $c->get(ShiftTemplateRepository::class),
            $c->get(ShiftRepository::class),
            $c->get(ShiftSwapRequestRepository::class),
            $c->get(EntityManagerInterface::class),
            $c->get(LoggerInterface::class),
        );
    },
    TimeClockService::class => function (ContainerInterface $c): TimeClockService {
        return new TimeClockService(
            $c->get(TimeClockRepository::class),
            $c->get(AttendanceRecordRepository::class),
            $c->get(BreakConfigRepository::class),
            $c->get(BreakLogRepository::class),
            $c->get(ShiftRepository::class),
            $c->get(SiteRepository::class),
            $c->get(GeofenceService::class),
            $c->get(LoggerInterface::class),
        );
    },
    PassdownService::class => function (ContainerInterface $c): PassdownService {
        return new PassdownService(
            $c->get(PassdownLogRepository::class),
            $c->get(LoggerInterface::class),
        );
    },

    ShiftController::class => fn(ContainerInterface $c) => new ShiftController($c->get(ShiftService::class)),
    TimeClockController::class => fn(ContainerInterface $c) => new TimeClockController($c->get(TimeClockService::class)),
    PassdownController::class => fn(ContainerInterface $c) => new PassdownController($c->get(PassdownService::class)),

    // Phase 3: Tracking, Tours & Panic
    GuardLocationRepository::class => fn(ContainerInterface $c) => new GuardLocationRepository($c->get(EntityManagerInterface::class)),
    GeofenceAlertRepository::class => fn(ContainerInterface $c) => new GeofenceAlertRepository($c->get(EntityManagerInterface::class)),
    IdleAlertRepository::class => fn(ContainerInterface $c) => new IdleAlertRepository($c->get(EntityManagerInterface::class)),
    TourCheckpointRepository::class => fn(ContainerInterface $c) => new TourCheckpointRepository($c->get(EntityManagerInterface::class)),
    TourSessionRepository::class => fn(ContainerInterface $c) => new TourSessionRepository($c->get(EntityManagerInterface::class)),
    TourCheckpointScanRepository::class => fn(ContainerInterface $c) => new TourCheckpointScanRepository($c->get(EntityManagerInterface::class)),
    PanicAlertRepository::class => fn(ContainerInterface $c) => new PanicAlertRepository($c->get(EntityManagerInterface::class)),

    LocationService::class => function (ContainerInterface $c): LocationService {
        return new LocationService(
            $c->get(GuardLocationRepository::class),
            $c->get(GeofenceAlertRepository::class),
            $c->get(IdleAlertRepository::class),
            $c->get(GeofenceService::class),
            $c->get(LoggerInterface::class),
        );
    },
    TourService::class => function (ContainerInterface $c): TourService {
        return new TourService(
            $c->get(TourCheckpointRepository::class),
            $c->get(TourSessionRepository::class),
            $c->get(TourCheckpointScanRepository::class),
            $c->get(LoggerInterface::class),
        );
    },
    PanicService::class => function (ContainerInterface $c): PanicService {
        return new PanicService(
            $c->get(PanicAlertRepository::class),
            $c->get(LoggerInterface::class),
        );
    },

    TrackingController::class => fn(ContainerInterface $c) => new TrackingController($c->get(LocationService::class)),
    TourController::class => fn(ContainerInterface $c) => new TourController($c->get(TourService::class)),
    PanicController::class => fn(ContainerInterface $c) => new PanicController($c->get(PanicService::class)),

    // Phase 4: Reporting, Incidents, Dispatch, Tasks
    DailyActivityReportRepository::class => fn(ContainerInterface $c) => new DailyActivityReportRepository($c->get(EntityManagerInterface::class)),
    CustomReportTemplateRepository::class => fn(ContainerInterface $c) => new CustomReportTemplateRepository($c->get(EntityManagerInterface::class)),
    CustomReportSubmissionRepository::class => fn(ContainerInterface $c) => new CustomReportSubmissionRepository($c->get(EntityManagerInterface::class)),
    WatchModeLogRepository::class => fn(ContainerInterface $c) => new WatchModeLogRepository($c->get(EntityManagerInterface::class)),
    IncidentReportRepository::class => fn(ContainerInterface $c) => new IncidentReportRepository($c->get(EntityManagerInterface::class)),
    IncidentEscalationRepository::class => fn(ContainerInterface $c) => new IncidentEscalationRepository($c->get(EntityManagerInterface::class)),
    DispatchCallRepository::class => fn(ContainerInterface $c) => new DispatchCallRepository($c->get(EntityManagerInterface::class)),
    DispatchAssignmentRepository::class => fn(ContainerInterface $c) => new DispatchAssignmentRepository($c->get(EntityManagerInterface::class)),
    TaskRepository::class => fn(ContainerInterface $c) => new TaskRepository($c->get(EntityManagerInterface::class)),

    ReportService::class => function (ContainerInterface $c): ReportService {
        return new ReportService(
            $c->get(DailyActivityReportRepository::class), $c->get(CustomReportTemplateRepository::class),
            $c->get(CustomReportSubmissionRepository::class), $c->get(WatchModeLogRepository::class),
            $c->get(LoggerInterface::class),
        );
    },
    IncidentService::class => function (ContainerInterface $c): IncidentService {
        return new IncidentService(
            $c->get(IncidentReportRepository::class), $c->get(IncidentEscalationRepository::class),
            $c->get(LoggerInterface::class),
        );
    },
    DispatchService::class => function (ContainerInterface $c): DispatchService {
        return new DispatchService(
            $c->get(DispatchCallRepository::class), $c->get(DispatchAssignmentRepository::class),
            $c->get(GeofenceService::class), $c->get(LoggerInterface::class),
        );
    },
    TaskService::class => function (ContainerInterface $c): TaskService {
        return new TaskService($c->get(TaskRepository::class), $c->get(LoggerInterface::class));
    },

    ReportController::class => fn(ContainerInterface $c) => new ReportController($c->get(ReportService::class)),
    IncidentController::class => fn(ContainerInterface $c) => new IncidentController($c->get(IncidentService::class), $c->get(FileStorageService::class)),
    DispatchController::class => fn(ContainerInterface $c) => new DispatchController($c->get(DispatchService::class)),
    TaskController::class => fn(ContainerInterface $c) => new TaskController($c->get(TaskService::class)),

    // Phase 5: Finance & Billing
    InvoiceRepository::class => fn(ContainerInterface $c) => new InvoiceRepository($c->get(EntityManagerInterface::class)),
    InvoiceItemRepository::class => fn(ContainerInterface $c) => new InvoiceItemRepository($c->get(EntityManagerInterface::class)),
    InvoicePaymentRepository::class => fn(ContainerInterface $c) => new InvoicePaymentRepository($c->get(EntityManagerInterface::class)),
    PayrollPeriodRepository::class => fn(ContainerInterface $c) => new PayrollPeriodRepository($c->get(EntityManagerInterface::class)),
    PayrollItemRepository::class => fn(ContainerInterface $c) => new PayrollItemRepository($c->get(EntityManagerInterface::class)),
    PayRateMultiplierRepository::class => fn(ContainerInterface $c) => new PayRateMultiplierRepository($c->get(EntityManagerInterface::class)),
    PayslipRepository::class => fn(ContainerInterface $c) => new PayslipRepository($c->get(EntityManagerInterface::class)),

    InvoiceService::class => function (ContainerInterface $c): InvoiceService {
        return new InvoiceService(
            $c->get(InvoiceRepository::class), $c->get(InvoiceItemRepository::class),
            $c->get(InvoicePaymentRepository::class), $c->get(LoggerInterface::class),
        );
    },
    PayrollService::class => function (ContainerInterface $c): PayrollService {
        return new PayrollService(
            $c->get(PayrollPeriodRepository::class), $c->get(PayrollItemRepository::class),
            $c->get(PayRateMultiplierRepository::class), $c->get(PayslipRepository::class),
            $c->get(TimeClockRepository::class), $c->get(LoggerInterface::class),
        );
    },

    InvoiceController::class => fn(ContainerInterface $c) => new InvoiceController($c->get(InvoiceService::class)),
    PayrollController::class => fn(ContainerInterface $c) => new PayrollController($c->get(PayrollService::class)),

    // Phase 6: Client Portal, Chat, Notifications
    ClientUserRepository::class => fn(ContainerInterface $c) => new ClientUserRepository($c->get(EntityManagerInterface::class)),
    ChatConversationRepository::class => fn(ContainerInterface $c) => new ChatConversationRepository($c->get(EntityManagerInterface::class)),
    ChatParticipantRepository::class => fn(ContainerInterface $c) => new ChatParticipantRepository($c->get(EntityManagerInterface::class)),
    ChatMessageRepository::class => fn(ContainerInterface $c) => new ChatMessageRepository($c->get(EntityManagerInterface::class)),
    NotificationRepository::class => fn(ContainerInterface $c) => new NotificationRepository($c->get(EntityManagerInterface::class)),
    DeviceTokenRepository::class => fn(ContainerInterface $c) => new DeviceTokenRepository($c->get(EntityManagerInterface::class)),

    ChatService::class => function (ContainerInterface $c): ChatService {
        return new ChatService(
            $c->get(ChatConversationRepository::class), $c->get(ChatParticipantRepository::class),
            $c->get(ChatMessageRepository::class), $c->get(LoggerInterface::class),
        );
    },
    NotificationService::class => function (ContainerInterface $c): NotificationService {
        return new NotificationService(
            $c->get(NotificationRepository::class), $c->get(DeviceTokenRepository::class),
            $c->get(LoggerInterface::class),
        );
    },

    ClientPortalController::class => fn(ContainerInterface $c) => new ClientPortalController($c->get(ClientUserRepository::class), $c->get(ReportService::class), $c->get(InvoiceService::class), $c->get(IncidentService::class)),
    ChatController::class => fn(ContainerInterface $c) => new ChatController($c->get(ChatService::class)),
    NotificationController::class => fn(ContainerInterface $c) => new NotificationController($c->get(NotificationService::class)),

    // Phase 7: Operations & Extended Apps
    PatrolVehicleRepository::class => fn(ContainerInterface $c) => new PatrolVehicleRepository($c->get(EntityManagerInterface::class)),
    VehiclePatrolRouteRepository::class => fn(ContainerInterface $c) => new VehiclePatrolRouteRepository($c->get(EntityManagerInterface::class)),
    VehiclePatrolHitRepository::class => fn(ContainerInterface $c) => new VehiclePatrolHitRepository($c->get(EntityManagerInterface::class)),
    VisitorRepository::class => fn(ContainerInterface $c) => new VisitorRepository($c->get(EntityManagerInterface::class)),
    VisitorVehicleRepository::class => fn(ContainerInterface $c) => new VisitorVehicleRepository($c->get(EntityManagerInterface::class)),
    ParkingAreaRepository::class => fn(ContainerInterface $c) => new ParkingAreaRepository($c->get(EntityManagerInterface::class)),
    ParkingLotRepository::class => fn(ContainerInterface $c) => new ParkingLotRepository($c->get(EntityManagerInterface::class)),
    ParkingVehicleRepository::class => fn(ContainerInterface $c) => new ParkingVehicleRepository($c->get(EntityManagerInterface::class)),
    ParkingIncidentTypeRepository::class => fn(ContainerInterface $c) => new ParkingIncidentTypeRepository($c->get(EntityManagerInterface::class)),
    ParkingIncidentRepository::class => fn(ContainerInterface $c) => new ParkingIncidentRepository($c->get(EntityManagerInterface::class)),

    VehiclePatrolService::class => fn(ContainerInterface $c) => new VehiclePatrolService($c->get(PatrolVehicleRepository::class), $c->get(VehiclePatrolRouteRepository::class), $c->get(VehiclePatrolHitRepository::class), $c->get(LoggerInterface::class)),
    VisitorService::class => fn(ContainerInterface $c) => new VisitorService($c->get(VisitorRepository::class), $c->get(VisitorVehicleRepository::class), $c->get(LoggerInterface::class)),
    ParkingService::class => fn(ContainerInterface $c) => new ParkingService($c->get(ParkingAreaRepository::class), $c->get(ParkingLotRepository::class), $c->get(ParkingVehicleRepository::class), $c->get(ParkingIncidentTypeRepository::class), $c->get(ParkingIncidentRepository::class), $c->get(LoggerInterface::class)),

    VehiclePatrolController::class => fn(ContainerInterface $c) => new VehiclePatrolController($c->get(VehiclePatrolService::class)),
    VisitorController::class => fn(ContainerInterface $c) => new VisitorController($c->get(VisitorService::class)),
    ParkingController::class => fn(ContainerInterface $c) => new ParkingController($c->get(ParkingService::class)),

    // Phase 8: Advanced Features
    GuardLicenseRepository::class => fn(ContainerInterface $c) => new GuardLicenseRepository($c->get(EntityManagerInterface::class)),
    TwoFactorSecretRepository::class => fn(ContainerInterface $c) => new TwoFactorSecretRepository($c->get(EntityManagerInterface::class)),
    AuditLogRepository::class => fn(ContainerInterface $c) => new AuditLogRepository($c->get(EntityManagerInterface::class)),
    GuardPerformanceIndexRepository::class => fn(ContainerInterface $c) => new GuardPerformanceIndexRepository($c->get(EntityManagerInterface::class)),
    PropertyRepository::class => fn(ContainerInterface $c) => new PropertyRepository($c->get(EntityManagerInterface::class)),
    AuditService::class => fn(ContainerInterface $c) => new AuditService($c->get(AuditLogRepository::class)),
    TwoFactorService::class => fn(ContainerInterface $c) => new TwoFactorService($c->get(TwoFactorSecretRepository::class)),
    LicenseService::class => fn(ContainerInterface $c) => new LicenseService($c->get(GuardLicenseRepository::class), $c->get(LoggerInterface::class)),
    AnalyticsService::class => fn(ContainerInterface $c) => new AnalyticsService($c->get(GuardPerformanceIndexRepository::class), $c->get(LoggerInterface::class)),
    SecurityController::class => fn(ContainerInterface $c) => new SecurityController($c->get(TwoFactorService::class), $c->get(AuditService::class)),
    LicenseController::class => fn(ContainerInterface $c) => new LicenseController($c->get(LicenseService::class)),
    AnalyticsController::class => fn(ContainerInterface $c) => new AnalyticsController($c->get(AnalyticsService::class)),

    PermissionRepository::class => fn(ContainerInterface $c) => new PermissionRepository($c->get(EntityManagerInterface::class)),
    UserManagementService::class => fn(ContainerInterface $c) => new UserManagementService($c->get(UserRepository::class), $c->get(PermissionRepository::class), $c->get(LoggerInterface::class)),
    UserManagementController::class => fn(ContainerInterface $c) => new UserManagementController($c->get(UserManagementService::class)),
    \Guard51\Module\Upload\FileUploadController::class => fn(ContainerInterface $c) => new \Guard51\Module\Upload\FileUploadController($c->get(FileStorageService::class)),
]);

return $containerBuilder->build();
