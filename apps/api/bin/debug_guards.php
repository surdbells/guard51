<?php
require __DIR__ . '/../vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Boot Doctrine the EXACT same way the web app does
$em = require __DIR__ . '/../config/doctrine.php';

$conn = $em->getConnection();
echo "=== Direct SQL (via Doctrine DBAL) ===\n";
$total = $conn->fetchOne('SELECT COUNT(*) FROM guards');
echo "Guards in DB: {$total}\n";

$guards = $conn->fetchAllAssociative('SELECT id, tenant_id, employee_number, first_name FROM guards LIMIT 5');
foreach ($guards as $g) {
    echo "  {$g['employee_number']} {$g['first_name']} | tenant: {$g['tenant_id']}\n";
}

echo "\n=== Doctrine ORM (NO filter) ===\n";
$repo = $em->getRepository(\Guard51\Entity\Guard::class);
$all = $repo->findAll();
echo "findAll(): " . count($all) . " guards\n";
foreach ($all as $g) {
    echo "  {$g->getEmployeeNumber()} {$g->getFirstName()} | tenant: {$g->getTenantId()}\n";
}

echo "\n=== Doctrine ORM with findBy(['tenantId' => ...]) ===\n";
$tid = '26b93009-d3bf-4c76-a341-8fbc4cde2b90';
$filtered = $repo->findBy(['tenantId' => $tid]);
echo "findBy(['tenantId' => '{$tid}']): " . count($filtered) . " guards\n";

echo "\n=== Enable TenantFilter ===\n";
try {
    $filter = $em->getFilters()->enable('tenant_filter');
    $filter->setParameter('tenant_id', "'{$tid}'");
    echo "Filter enabled for tenant: {$tid}\n";
    
    $withFilter = $repo->findAll();
    echo "findAll() with filter: " . count($withFilter) . " guards\n";
    
    $withFilterAndCriteria = $repo->findBy(['tenantId' => $tid]);
    echo "findBy(['tenantId']) with filter: " . count($withFilterAndCriteria) . " guards\n";
} catch (\Throwable $e) {
    echo "Filter ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== DQL test ===\n";
$qb = $em->createQueryBuilder();
$qb->select('g')->from(\Guard51\Entity\Guard::class, 'g')->where('g.tenantId = :tid')->setParameter('tid', $tid);
$dql = $qb->getDQL();
echo "DQL: {$dql}\n";
$sql = $qb->getQuery()->getSQL();
echo "SQL: {$sql}\n";
$result = $qb->getQuery()->getResult();
echo "Result: " . count($result) . " guards\n";

echo "\nDone.\n";
