<?php
require __DIR__ . '/../vendor/autoload.php';

$settings = require __DIR__ . '/../config/settings.php';
$container = require __DIR__ . '/../config/container.php';

$em = $container->get(\Doctrine\ORM\EntityManagerInterface::class);
$conn = $em->getConnection();

// Direct SQL query — no Doctrine filter
echo "=== Raw SQL: All guards ===\n";
$result = $conn->fetchAllAssociative('SELECT id, tenant_id, employee_number, first_name, last_name FROM guards LIMIT 10');
foreach ($result as $r) {
    echo "  {$r['employee_number']} {$r['first_name']} {$r['last_name']} | tenant: {$r['tenant_id']}\n";
}
echo "Total guards in DB: " . $conn->fetchOne('SELECT COUNT(*) FROM guards') . "\n\n";

// Check tenants
echo "=== Raw SQL: All tenants ===\n";
$tenants = $conn->fetchAllAssociative('SELECT id, name, email FROM tenants LIMIT 10');
foreach ($tenants as $t) {
    echo "  {$t['id']} | {$t['name']} | {$t['email']}\n";
}
echo "\n";

// Test with Doctrine filter enabled
if (!empty($tenants)) {
    $tid = $tenants[0]['id'];
    echo "=== Enabling TenantFilter for tenant: {$tid} ===\n";
    $filter = $em->getFilters()->enable('tenant_filter');
    $filter->setParameter('tenant_id', "'{$tid}'");
    
    $guards = $em->getRepository(\Guard51\Entity\Guard::class)->findAll();
    echo "Guards via Doctrine (with filter): " . count($guards) . "\n";
    foreach ($guards as $g) {
        echo "  {$g->getEmployeeNumber()} {$g->getFirstName()} {$g->getLastName()}\n";
    }
    
    // Also test findBy with empty criteria
    $guards2 = $em->getRepository(\Guard51\Entity\Guard::class)->findBy([]);
    echo "Guards via findBy([]): " . count($guards2) . "\n";
}
