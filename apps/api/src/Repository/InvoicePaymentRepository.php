<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\InvoicePayment;

/** @extends BaseRepository<InvoicePayment> */
class InvoicePaymentRepository extends BaseRepository
{
    protected function getEntityClass(): string { return InvoicePayment::class; }
    public function findByInvoice(string $invoiceId): array { return $this->findBy(['invoiceId' => $invoiceId], ['paymentDate' => 'DESC']); }
}
