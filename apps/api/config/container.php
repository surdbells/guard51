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
use Guard51\Module\Feature\FeatureController;
use Guard51\Module\Subscription\SubscriptionController;
use Guard51\Module\Subscription\PlanController;
use Guard51\Module\Usage\UsageController;
use Guard51\Repository\AuditLogRepository;
use Guard51\Repository\FeatureModuleRepository;
use Guard51\Repository\RefreshTokenRepository;
use Guard51\Repository\SubscriptionInvoiceRepository;
use Guard51\Repository\SubscriptionPlanRepository;
use Guard51\Repository\SubscriptionRepository;
use Guard51\Repository\TenantFeatureModuleRepository;
use Guard51\Repository\TenantRepository;
use Guard51\Repository\TenantUsageMetricRepository;
use Guard51\Repository\UserRepository;
use Guard51\Service\FeatureService;
use Guard51\Service\FileStorageService;
use Guard51\Service\GpsService;
use Guard51\Service\JwtService;
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

    AuditLogRepository::class => function (ContainerInterface $c): AuditLogRepository {
        return new AuditLogRepository($c->get(EntityManagerInterface::class));
    },

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
]);

return $containerBuilder->build();
