<?php
declare(strict_types=1);
namespace Guard51\Tests\Entity;

use Guard51\Entity\Invoice;
use Guard51\Entity\InvoiceItem;
use Guard51\Entity\InvoicePayment;
use Guard51\Entity\InvoiceStatus;
use Guard51\Entity\InvoiceType;
use Guard51\Entity\PaymentMethod;
use Guard51\Entity\PayRateAppliesTo;
use Guard51\Entity\PayRateMultiplier;
use Guard51\Entity\PayrollItem;
use Guard51\Entity\PayrollItemStatus;
use Guard51\Entity\PayrollPeriod;
use Guard51\Entity\PayrollStatus;
use Guard51\Entity\Payslip;
use PHPUnit\Framework\TestCase;

class FinanceBillingTest extends TestCase
{
    // ── Invoice ──────────────────────────────────────

    public function testInvoiceCreation(): void
    {
        $inv = new Invoice();
        $inv->setTenantId('t-1')->setClientId('c-1')->setCreatedBy('admin-1')
            ->setInvoiceNumber('INV-00001');

        $this->assertEquals(InvoiceStatus::DRAFT, $inv->getStatus());
        $this->assertEquals('INV-00001', $inv->getInvoiceNumber());
        $this->assertEquals(InvoiceType::INVOICE, $inv->getType());
    }

    public function testInvoiceTotals(): void
    {
        $inv = new Invoice();
        $inv->setTenantId('t-1')->setClientId('c-1')->setCreatedBy('admin-1')
            ->setInvoiceNumber('INV-00002')->setTaxRate(7.5);

        $inv->calculateTotals(100000); // ₦100,000
        $this->assertEquals(100000, $inv->getSubtotal());
        $this->assertEquals(107500, $inv->getTotal()); // + 7.5% VAT
        $this->assertEquals(107500, $inv->getBalanceDue());
    }

    public function testInvoicePartialPayment(): void
    {
        $inv = new Invoice();
        $inv->setTenantId('t-1')->setClientId('c-1')->setCreatedBy('admin-1')
            ->setInvoiceNumber('INV-00003')->setTaxRate(7.5);
        $inv->calculateTotals(100000);

        $inv->recordPayment(50000);
        $this->assertEquals(InvoiceStatus::PARTIALLY_PAID, $inv->getStatus());
        $this->assertEquals(50000, $inv->getAmountPaid());
        $this->assertEquals(57500, $inv->getBalanceDue());
    }

    public function testInvoiceFullPayment(): void
    {
        $inv = new Invoice();
        $inv->setTenantId('t-1')->setClientId('c-1')->setCreatedBy('admin-1')
            ->setInvoiceNumber('INV-00004')->setTaxRate(7.5);
        $inv->calculateTotals(100000);

        $inv->recordPayment(107500);
        $this->assertEquals(InvoiceStatus::PAID, $inv->getStatus());
        $this->assertTrue($inv->getStatus()->isPaid());
        $this->assertEquals(0, $inv->getBalanceDue());
    }

    public function testInvoiceSend(): void
    {
        $inv = new Invoice();
        $inv->setTenantId('t-1')->setClientId('c-1')->setCreatedBy('admin-1')
            ->setInvoiceNumber('INV-00005');
        $inv->send();
        $this->assertEquals(InvoiceStatus::SENT, $inv->getStatus());
    }

    public function testEstimateConversion(): void
    {
        $inv = new Invoice();
        $inv->setTenantId('t-1')->setClientId('c-1')->setCreatedBy('admin-1')
            ->setInvoiceNumber('EST-00001')->setType(InvoiceType::ESTIMATE);
        $inv->send();
        $inv->convertEstimateToInvoice();
        $this->assertEquals(InvoiceType::INVOICE, $inv->getType());
        $this->assertEquals(InvoiceStatus::DRAFT, $inv->getStatus());
    }

    // ── InvoiceItem ──────────────────────────────────

    public function testInvoiceItemCalc(): void
    {
        $item = new InvoiceItem();
        $item->setInvoiceId('inv-1')->setDescription('Guard services - March')
            ->setQuantity(720)->setUnitPrice(500)->calculateAmount();
        $this->assertEquals(360000, $item->getAmount());
    }

    // ── InvoicePayment ───────────────────────────────

    public function testPaymentMethods(): void
    {
        $this->assertEquals('Bank Transfer', PaymentMethod::BANK_TRANSFER->label());
        $this->assertEquals('POS / Card', PaymentMethod::POS_CARD->label());
        $this->assertEquals('Paystack', PaymentMethod::PAYSTACK->label());
        $this->assertTrue(PaymentMethod::BANK_TRANSFER->requiresManualConfirmation());
        $this->assertFalse(PaymentMethod::PAYSTACK->requiresManualConfirmation());
    }

    // ── PayrollPeriod ────────────────────────────────

    public function testPayrollPeriod(): void
    {
        $p = new PayrollPeriod();
        $p->setTenantId('t-1')->setPeriodStart(new \DateTimeImmutable('2026-03-01'))
            ->setPeriodEnd(new \DateTimeImmutable('2026-03-31'));
        $this->assertEquals(PayrollStatus::DRAFT, $p->getStatus());

        $p->updateTotals(5000000, 800000);
        $this->assertEquals(5000000, $p->getTotalGross());
        $this->assertEquals(4200000, $p->getTotalNet());

        $p->approve('admin-1');
        $this->assertEquals(PayrollStatus::APPROVED, $p->getStatus());
    }

    // ── PayrollItem ──────────────────────────────────

    public function testPayrollItemCalc(): void
    {
        $item = new PayrollItem();
        $item->setTenantId('t-1')->setPayrollPeriodId('pp-1')->setGuardId('g-1')
            ->setRegularHours(160)->setOvertimeHours(20)->setHolidayHours(0)
            ->setRegularRate(500)->setOvertimeRate(750)->setHolidayRate(1000)
            ->setDeductions(['paye' => 5000, 'pension' => 6400, 'nhf' => 2000]);
        $item->calculatePay();

        $this->assertEquals(95000, $item->getGrossPay()); // 160*500 + 20*750
        $this->assertEquals(81600, $item->getNetPay());   // 95000 - 13400
    }

    public function testPayrollItemApprove(): void
    {
        $item = new PayrollItem();
        $item->setTenantId('t-1')->setPayrollPeriodId('pp-1')->setGuardId('g-1')
            ->setRegularHours(160)->setRegularRate(500)->setOvertimeRate(750)->setHolidayRate(1000);
        $item->approve();
        $this->assertEquals(PayrollItemStatus::APPROVED, $item->getStatus());
    }

    // ── PayRateMultiplier ────────────────────────────

    public function testPayRateMultiplier(): void
    {
        $r = new PayRateMultiplier();
        $r->setTenantId('t-1')->setName('Overtime 1.5x')->setMultiplier(1.5)
            ->setAppliesTo(PayRateAppliesTo::OVERTIME);
        $this->assertEquals(1.5, $r->getMultiplier());
        $this->assertEquals(PayRateAppliesTo::OVERTIME, $r->getAppliesTo());
        $arr = $r->toArray();
        $this->assertEquals('Overtime', $arr['applies_to_label']);
    }

    // ── Payslip ──────────────────────────────────────

    public function testPayslip(): void
    {
        $slip = new Payslip();
        $slip->setPayrollItemId('pi-1')->setGuardId('g-1')
            ->setPeriodStart(new \DateTimeImmutable('2026-03-01'))
            ->setPeriodEnd(new \DateTimeImmutable('2026-03-31'))
            ->setGrossPay(95000)->setDeductionsBreakdown(['paye' => 5000, 'pension' => 6400, 'nhf' => 2000])
            ->setNetPay(81600);
        $arr = $slip->toArray();
        $this->assertEquals(95000, $arr['gross_pay']);
        $this->assertEquals(81600, $arr['net_pay']);
        $this->assertEquals(13400, $arr['total_deductions']);
    }

    // ── InvoiceStatus ────────────────────────────────

    public function testInvoiceStatusActive(): void
    {
        $this->assertTrue(InvoiceStatus::SENT->isActive());
        $this->assertTrue(InvoiceStatus::PARTIALLY_PAID->isActive());
        $this->assertTrue(InvoiceStatus::OVERDUE->isActive());
        $this->assertTrue(InvoiceStatus::PENDING->isActive()); // Phase 0 subscription
        $this->assertFalse(InvoiceStatus::PAID->isActive());
        $this->assertFalse(InvoiceStatus::DRAFT->isActive());
    }

    public function testInvoiceToArray(): void
    {
        $inv = new Invoice();
        $inv->setTenantId('t-1')->setClientId('c-1')->setCreatedBy('admin-1')
            ->setInvoiceNumber('INV-00010')->setTaxRate(7.5);
        $inv->calculateTotals(200000);
        $arr = $inv->toArray();
        $this->assertEquals('NGN', $arr['currency']);
        $this->assertEquals(200000, $arr['subtotal']);
        $this->assertEquals(215000, $arr['total']);
        $this->assertEquals(7.5, $arr['tax_rate']);
    }
}
