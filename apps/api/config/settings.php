<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'Guard51',
        'env' => $_ENV['APP_ENV'] ?? 'development',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'url' => $_ENV['APP_URL'] ?? 'http://localhost:8080',
    ],

    'database' => [
        'driver' => 'pdo_pgsql',
        'host' => $_ENV['DB_HOST'] ?? 'postgres',
        'port' => (int) ($_ENV['DB_PORT'] ?? 5432),
        'dbname' => $_ENV['DB_NAME'] ?? 'guard51',
        'user' => $_ENV['DB_USER'] ?? 'guard51',
        'password' => $_ENV['DB_PASSWORD'] ?? 'guard51_secret',
        'charset' => 'utf8',
    ],

    'redis' => [
        'host' => $_ENV['REDIS_HOST'] ?? 'redis',
        'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
    ],

    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? 'change_me',
        'access_ttl' => (int) ($_ENV['JWT_ACCESS_TTL'] ?? 900),       // 15 minutes
        'refresh_ttl' => (int) ($_ENV['JWT_REFRESH_TTL'] ?? 604800),  // 7 days
        'algorithm' => 'HS256',
        'issuer' => 'guard51',
    ],

    'zeptomail' => [
        'api_key' => $_ENV['ZEPTOMAIL_API_KEY'] ?? '',
        'from_email' => $_ENV['ZEPTOMAIL_FROM_EMAIL'] ?? 'noreply@guard51.com',
        'from_name' => $_ENV['ZEPTOMAIL_FROM_NAME'] ?? 'Guard51',
    ],

    'termii' => [
        'api_key' => $_ENV['TERMII_API_KEY'] ?? '',
        'sender_id' => $_ENV['TERMII_SENDER_ID'] ?? 'Guard51',
        'base_url' => 'https://api.ng.termii.com/api',
    ],

    'paystack' => [
        'secret_key' => $_ENV['PAYSTACK_SECRET_KEY'] ?? '',
        'public_key' => $_ENV['PAYSTACK_PUBLIC_KEY'] ?? '',
        'webhook_secret' => $_ENV['PAYSTACK_WEBHOOK_SECRET'] ?? '',
        'base_url' => 'https://api.paystack.co',
    ],

    'storage' => [
        'driver' => $_ENV['FILE_STORAGE_DRIVER'] ?? 'local',     // 'local' or 's3'
        'local_path' => __DIR__ . '/../storage/uploads',
        's3' => [
            'bucket' => $_ENV['AWS_S3_BUCKET'] ?? '',
            'region' => $_ENV['AWS_S3_REGION'] ?? 'af-south-1',
            'key' => $_ENV['AWS_S3_KEY'] ?? '',
            'secret' => $_ENV['AWS_S3_SECRET'] ?? '',
        ],
    ],

    'cors' => [
        'allowed_origins' => ['http://localhost:4200', 'http://localhost:8080'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Request-ID', 'X-Tenant-ID'],
        'max_age' => 86400,
    ],

    'logging' => [
        'name' => 'guard51',
        'path' => __DIR__ . '/../logs/app.log',
        'level' => $_ENV['APP_DEBUG'] ?? true ? 'debug' : 'info',
    ],
];
