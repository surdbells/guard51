<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;

$settings = require __DIR__ . '/settings.php';

$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: [__DIR__ . '/../src/Entity'],
    isDevMode: true, // Always true — metadata cache disabled for reliability
);

// Map camelCase PHP properties to snake_case DB columns
$config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));

// Register the tenant filter for multi-tenancy
$config->addFilter('tenant_filter', \Guard51\Filter\TenantFilter::class);

$connection = DriverManager::getConnection($settings['database'], $config);
$entityManager = new EntityManager($connection, $config);

// Register encryption lifecycle listener
$encryptionService = new \Guard51\Service\EncryptionService();
$encryptionListener = new \Guard51\EventListener\EncryptionListener($encryptionService);
$entityManager->getEventManager()->addEventListener(
    [\Doctrine\ORM\Events::prePersist, \Doctrine\ORM\Events::preUpdate, \Doctrine\ORM\Events::postLoad],
    $encryptionListener
);

return $entityManager;
