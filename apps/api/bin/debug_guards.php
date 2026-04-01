<?php
require __DIR__ . '/../vendor/autoload.php';

// Load .env just like the web app does
$dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$settings = require __DIR__ . '/../config/settings.php';

echo "=== DB Connection Info ===\n";
echo "Host: " . ($settings['database']['host'] ?? '?') . "\n";
echo "Port: " . ($settings['database']['port'] ?? '?') . "\n";
echo "DB: " . ($settings['database']['dbname'] ?? '?') . "\n";
echo "User: " . ($settings['database']['user'] ?? '?') . "\n\n";

// Direct PDO connection
try {
    $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s',
        $settings['database']['host'],
        $settings['database']['port'],
        $settings['database']['dbname']
    );
    $pdo = new PDO($dsn, $settings['database']['user'], $settings['database']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ DB Connected!\n\n";
} catch (PDOException $e) {
    echo "❌ DB Connection FAILED: " . $e->getMessage() . "\n";
    echo "\nTrying localhost:5433 fallback...\n";
    try {
        $pdo = new PDO('pgsql:host=127.0.0.1;port=5433;dbname=guard51', 'guard51', $settings['database']['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✅ Fallback connected!\n\n";
    } catch (PDOException $e2) {
        echo "❌ Fallback also failed: " . $e2->getMessage() . "\n";
        exit(1);
    }
}

echo "=== Raw SQL: All guards ===\n";
$stmt = $pdo->query('SELECT id, tenant_id, employee_number, first_name, last_name, status FROM guards ORDER BY created_at LIMIT 20');
$guards = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($guards as $r) {
    echo "  {$r['employee_number']} | {$r['first_name']} {$r['last_name']} | {$r['status']} | tenant: {$r['tenant_id']}\n";
}
$total = $pdo->query('SELECT COUNT(*) FROM guards')->fetchColumn();
echo "Total guards in DB: {$total}\n\n";

echo "=== Raw SQL: All tenants ===\n";
$stmt = $pdo->query('SELECT id, name, email, status FROM tenants ORDER BY created_at LIMIT 10');
$tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($tenants as $t) {
    echo "  {$t['id']} | {$t['name']} | {$t['email']} | {$t['status']}\n";
}

echo "\n=== Raw SQL: All users ===\n";
$stmt = $pdo->query('SELECT id, email, role, tenant_id, is_active FROM users ORDER BY created_at LIMIT 10');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo "  {$u['email']} | {$u['role']} | tenant: " . ($u['tenant_id'] ?? 'NULL') . " | active: {$u['is_active']}\n";
}

echo "\n=== Cross-check: guards per tenant ===\n";
$stmt = $pdo->query('SELECT tenant_id, COUNT(*) as cnt FROM guards GROUP BY tenant_id');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "  Tenant {$r['tenant_id']}: {$r['cnt']} guards\n";
}

echo "\nDone.\n";
