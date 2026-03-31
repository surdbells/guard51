<?php
/**
 * Guard51 Cron Runner
 * Usage: php bin/cron.php <command>
 * Commands: snapshots, overdue, auto-clockout, license-expiry
 */
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$container = (require __DIR__ . '/../config/container.php');
$command = $argv[1] ?? '';

$em = $container->get(\Doctrine\ORM\EntityManagerInterface::class);

switch ($command) {
    case 'snapshots':
        $cmd = new \Guard51\Command\GenerateDailySnapshotsCommand(
            $em,
            $container->get(\Guard51\Repository\GuardRepository::class),
            $container->get(\Guard51\Repository\SiteRepository::class),
        );
        exit($cmd->execute());

    case 'overdue':
        $cmd = new \Guard51\Command\DetectOverdueInvoicesCommand(
            $em,
            $container->get(\Guard51\Service\InvoiceService::class),
        );
        exit($cmd->execute());

    case 'auto-clockout':
        $cmd = new \Guard51\Command\AutoClockOutCommand($em);
        exit($cmd->execute());

    case 'license-expiry':
        $cmd = new \Guard51\Command\CheckLicenseExpiryCommand($em);
        exit($cmd->execute());

    default:
        echo "Guard51 Cron Runner\n";
        echo "Usage: php bin/cron.php <command>\n\n";
        echo "Commands:\n";
        echo "  snapshots      Generate daily snapshots for all tenants\n";
        echo "  overdue        Detect and mark overdue invoices\n";
        echo "  auto-clockout  Auto clock-out guards after 14 hours\n";
        echo "  license-expiry Check for expiring guard licenses\n";
        exit(1);
}
