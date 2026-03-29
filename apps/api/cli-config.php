<?php

declare(strict_types=1);

/**
 * Doctrine CLI configuration for Doctrine ORM 3.x + Migrations 3.x.
 *
 * Usage:
 *   php vendor/bin/doctrine-migrations migrate
 *   php vendor/bin/doctrine-migrations status
 *   php vendor/bin/doctrine-migrations diff
 */

use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManager;
use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

/** @var EntityManager $entityManager */
$entityManager = require __DIR__ . '/config/doctrine.php';

$config = new PhpFile(__DIR__ . '/config/migrations.php');

return DependencyFactory::fromEntityManager($config, new ExistingEntityManager($entityManager));
