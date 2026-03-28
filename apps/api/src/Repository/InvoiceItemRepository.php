<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\InvoiceItem;

/** @extends BaseRepository<InvoiceItem> */
class InvoiceItemRepository extends BaseRepository
{
    protected function getEntityClass(): string { return InvoiceItem::class; }
    public function findByInvoice(string $invoiceId): array { return $this->findBy(['invoiceId' => $invoiceId]); }
}
