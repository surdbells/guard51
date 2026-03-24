<?php

declare(strict_types=1);

/**
 * Database seeder CLI command.
 * Usage: php bin/seed.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Guard51\Database\Seeder;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$entityManager = require __DIR__ . '/../config/doctrine.php';

$seeder = new Seeder($entityManager);
$seeder->run();
