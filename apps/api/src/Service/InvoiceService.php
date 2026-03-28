<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\Invoice;
use Guard51\Entity\InvoiceItem;
use Guard51\Entity\InvoicePayment;
use Guard51\Entity\InvoiceStatus;
use Guard51\Entity\InvoiceType;
use Guard51\Entity\PaymentMethod;
use Guard51\Exception\ApiException;
use Guard51\Repository\InvoiceItemRepository;
use Guard51\Repository\InvoicePaymentRepository;
use Guard51\Repository\InvoiceRepository;
use Psr\Log\LoggerInterface;

final class InvoiceService
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepo,
        private readonly InvoiceItemRepository $itemRepo,
        private readonly InvoicePaymentRepository $paymentRepo,
        private readonly LoggerInterface $logger,
    ) {}

    public function createInvoice(string $tenantId, array $data, string $createdBy): Invoice
    {
        if (empty($data['client_id'])) throw ApiException::validation('client_id required.');

        $inv = new Invoice();
        $inv->setTenantId($tenantId)->setClientId($data['client_id'])->setCreatedBy($createdBy)
            ->setInvoiceNumber($this->invoiceRepo->getNextInvoiceNumber($tenantId));
        if (isset($data['type'])) $inv->setType(InvoiceType::from($data['type']));
        if (isset($data['issue_date'])) $inv->setIssueDate(new \DateTimeImmutable($data['issue_date']));
        if (isset($data['due_date'])) $inv->setDueDate(new \DateTimeImmutable($data['due_date']));
        if (isset($data['tax_rate'])) $inv->setTaxRate((float) $data['tax_rate']);
        if (isset($data['notes'])) $inv->setNotes($data['notes']);
        if (isset($data['payment_terms'])) $inv->setPaymentTerms($data['payment_terms']);
        if (isset($data['currency'])) $inv->setCurrency($data['currency']);
        $this->invoiceRepo->save($inv);

        // Add items
        $subtotal = 0;
        foreach ($data['items'] ?? [] as $itemData) {
            $item = new InvoiceItem();
            $item->setInvoiceId($inv->getId())->setDescription($itemData['description'] ?? '')
                ->setQuantity((float) ($itemData['quantity'] ?? 1))
                ->setUnitPrice((float) ($itemData['unit_price'] ?? 0))
                ->calculateAmount();
            if (isset($itemData['is_taxable'])) $item->setIsTaxable((bool) $itemData['is_taxable']);
            $this->itemRepo->save($item);
            $subtotal += $item->getAmount();
        }

        $inv->calculateTotals($subtotal);
        $this->invoiceRepo->save($inv);
        return $inv;
    }

    public function getInvoiceDetail(string $invoiceId): array
    {
        $inv = $this->invoiceRepo->findOrFail($invoiceId);
        $items = $this->itemRepo->findByInvoice($invoiceId);
        $payments = $this->paymentRepo->findByInvoice($invoiceId);
        return [
            'invoice' => $inv->toArray(),
            'items' => array_map(fn($i) => $i->toArray(), $items),
            'payments' => array_map(fn($p) => $p->toArray(), $payments),
        ];
    }

    public function recordPayment(string $invoiceId, array $data, string $receivedBy): InvoicePayment
    {
        if (!isset($data['amount']) || !isset($data['payment_method'])) throw ApiException::validation('amount and payment_method required.');
        $inv = $this->invoiceRepo->findOrFail($invoiceId);

        $payment = new InvoicePayment();
        $payment->setInvoiceId($invoiceId)->setAmount((float) $data['amount'])
            ->setPaymentMethod(PaymentMethod::from($data['payment_method']))->setReceivedBy($receivedBy);
        if (isset($data['reference'])) $payment->setReference($data['reference']);
        if (isset($data['proof_url'])) $payment->setProofUrl($data['proof_url']);
        if (isset($data['notes'])) $payment->setNotes($data['notes']);
        if (isset($data['payment_date'])) $payment->setPaymentDate(new \DateTimeImmutable($data['payment_date']));
        $this->paymentRepo->save($payment);

        $inv->recordPayment((float) $data['amount']);
        $this->invoiceRepo->save($inv);
        return $payment;
    }

    public function sendInvoice(string $invoiceId): Invoice
    {
        $inv = $this->invoiceRepo->findOrFail($invoiceId);
        $inv->send();
        $this->invoiceRepo->save($inv);
        // TODO: Email via ZeptoMail
        return $inv;
    }

    public function convertEstimate(string $invoiceId): Invoice
    {
        $inv = $this->invoiceRepo->findOrFail($invoiceId);
        $inv->convertEstimateToInvoice();
        $this->invoiceRepo->save($inv);
        return $inv;
    }

    public function listInvoices(string $tenantId, ?string $status = null, ?string $clientId = null): array
    {
        return $this->invoiceRepo->findByTenant($tenantId, $status, $clientId);
    }

    public function findOverdue(string $tenantId): array { return $this->invoiceRepo->findOverdue($tenantId); }

    public function detectOverdueInvoices(string $tenantId): int
    {
        $overdue = $this->invoiceRepo->findOverdue($tenantId);
        $count = 0;
        foreach ($overdue as $inv) {
            if ($inv->getStatus() !== InvoiceStatus::OVERDUE) { $inv->markOverdue(); $this->invoiceRepo->save($inv); $count++; }
        }
        return $count;
    }

    public function exportInvoiceHtml(string $invoiceId): array
    {
        $detail = $this->getInvoiceDetail($invoiceId);
        $inv = $detail['invoice'];
        $html = "<h1>{$inv['type_label']} #{$inv['invoice_number']}</h1>";
        $html .= "<p>Date: {$inv['issue_date']} | Due: {$inv['due_date']}</p>";
        $html .= "<table><tr><th>Description</th><th>Qty</th><th>Rate</th><th>Amount</th></tr>";
        foreach ($detail['items'] as $item) {
            $html .= "<tr><td>{$item['description']}</td><td>{$item['quantity']}</td><td>{$item['unit_price']}</td><td>{$item['amount']}</td></tr>";
        }
        $html .= "</table><p>Subtotal: {$inv['currency']} {$inv['subtotal']}</p>";
        $html .= "<p>VAT ({$inv['tax_rate']}%): {$inv['currency']} {$inv['tax_amount']}</p>";
        $html .= "<p><strong>Total: {$inv['currency']} {$inv['total']}</strong></p>";
        return ['html' => $html, 'invoice' => $inv];
    }
}
