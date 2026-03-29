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

$connection = DriverManager::getConnection($settings['database'], $config);
$entityManager = new EntityManager($connection, $config);

return $entityManager;
