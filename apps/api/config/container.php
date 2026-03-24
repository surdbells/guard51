<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Guard51\Service\FileStorageService;
use Guard51\Service\GpsService;
use Guard51\Service\PaystackService;
use Guard51\Service\PdfService;
use Guard51\Service\QueueService;
use Guard51\Service\TermiiService;
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

        $connection = DriverManager::getConnection($settings, $config);

        return new EntityManager($connection, $config);
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
]);

return $containerBuilder->build();
