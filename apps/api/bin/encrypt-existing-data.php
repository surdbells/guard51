<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$settings = require __DIR__ . '/../config/settings.php';
$dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $settings['database']['host'], $settings['database']['port'], $settings['database']['dbname']);
$pdo = new PDO($dsn, $settings['database']['user'], $settings['database']['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$enc = new \Guard51\Service\EncryptionService();

echo "=== Encrypting existing PII data ===\n\n";

// Guards
$stmt = $pdo->query('SELECT id, phone, bank_account_number, bank_account_name, emergency_contact_phone FROM guards');
$guards = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = 0;
foreach ($guards as $g) {
    $updates = [];
    $params = [];
    foreach (['phone', 'bank_account_number', 'bank_account_name', 'emergency_contact_phone'] as $col) {
        if ($g[$col] && !$enc->isEncrypted($g[$col])) {
            $updates[] = "{$col} = ?";
            $params[] = $enc->encrypt($g[$col]);
        }
    }
    if ($updates) {
        $params[] = $g['id'];
        $pdo->prepare('UPDATE guards SET ' . implode(', ', $updates) . ' WHERE id = ?')->execute($params);
        $count++;
    }
}
echo "Guards encrypted: {$count}\n";

// Users
$stmt = $pdo->query('SELECT id, phone FROM users WHERE phone IS NOT NULL');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = 0;
foreach ($users as $u) {
    if ($u['phone'] && !$enc->isEncrypted($u['phone'])) {
        $pdo->prepare('UPDATE users SET phone = ? WHERE id = ?')->execute([$enc->encrypt($u['phone']), $u['id']]);
        $count++;
    }
}
echo "Users encrypted: {$count}\n";

echo "\nDone.\n";
