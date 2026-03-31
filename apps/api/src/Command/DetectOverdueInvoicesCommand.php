<?php
declare(strict_types=1);
namespace Guard51\Command;

use Doctrine\ORM\EntityManagerInterface;
use Guard51\Entity\Tenant;
use Guard51\Service\InvoiceService;

final class DetectOverdueInvoicesCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly InvoiceService $invoiceService,
    ) {}

    public function execute(): int
    {
        $tenants = $this->em->getRepository(Tenant::class)->findAll();
        $total = 0;
        foreach ($tenants as $tenant) {
            $count = $this->invoiceService->detectOverdueInvoices($tenant->getId());
            $total += $count;
        }
        echo "Marked {$total} invoices as overdue.\n";
        return 0;
    }
}
