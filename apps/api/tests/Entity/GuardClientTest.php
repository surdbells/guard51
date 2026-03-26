<?php

declare(strict_types=1);

namespace Guard51\Tests\Entity;

use Guard51\Entity\BillingType;
use Guard51\Entity\Client;
use Guard51\Entity\ClientContact;
use Guard51\Entity\ClientStatus;
use Guard51\Entity\DailySnapshot;
use Guard51\Entity\DocumentType;
use Guard51\Entity\Guard;
use Guard51\Entity\GuardDocument;
use Guard51\Entity\GuardSkill;
use Guard51\Entity\GuardSkillAssignment;
use Guard51\Entity\GuardStatus;
use Guard51\Entity\PayType;
use PHPUnit\Framework\TestCase;

class GuardClientTest extends TestCase
{
    // ── GuardStatus ──────────────────────────────────

    public function testGuardStatusOperational(): void
    {
        $this->assertTrue(GuardStatus::ACTIVE->isOperational());
        $this->assertTrue(GuardStatus::ACTIVE->canBeAssigned());
        $this->assertFalse(GuardStatus::SUSPENDED->isOperational());
        $this->assertFalse(GuardStatus::TERMINATED->canBeAssigned());
    }

    // ── PayType ──────────────────────────────────────

    public function testPayTypeLabels(): void
    {
        $this->assertEquals('Hourly', PayType::HOURLY->label());
        $this->assertEquals('Daily', PayType::DAILY->label());
        $this->assertEquals('Monthly', PayType::MONTHLY->label());
    }

    // ── DocumentType ─────────────────────────────────

    public function testDocumentTypeLabels(): void
    {
        $this->assertEquals('Security License', DocumentType::LICENSE->label());
        $this->assertEquals('ID Card', DocumentType::ID_CARD->label());
        $this->assertEquals('Medical Report', DocumentType::MEDICAL->label());
    }

    // ── Guard Entity ─────────────────────────────────

    public function testGuardCreation(): void
    {
        $guard = new Guard();
        $guard->setTenantId('tenant-1')
            ->setEmployeeNumber('G-001')
            ->setFirstName('Musa')
            ->setLastName('Ibrahim')
            ->setPhone('+2348012345678')
            ->setEmail('musa@shield.com')
            ->setPayType(PayType::MONTHLY)
            ->setPayRate(85000.00)
            ->setEmergencyContactName('Amina Ibrahim')
            ->setEmergencyContactPhone('+2348098765432');

        $this->assertNotEmpty($guard->getId());
        $this->assertEquals('Musa Ibrahim', $guard->getFullName());
        $this->assertEquals('G-001', $guard->getEmployeeNumber());
        $this->assertEquals(PayType::MONTHLY, $guard->getPayType());
        $this->assertEquals(85000.00, $guard->getPayRate());
        $this->assertEquals(GuardStatus::ACTIVE, $guard->getStatus());
        $this->assertTrue($guard->canBeAssigned());
    }

    public function testGuardStatusTransitions(): void
    {
        $guard = new Guard();
        $guard->setTenantId('t-1')->setEmployeeNumber('G-002')
            ->setFirstName('A')->setLastName('B')->setPhone('123');

        $guard->suspend();
        $this->assertEquals(GuardStatus::SUSPENDED, $guard->getStatus());
        $this->assertFalse($guard->canBeAssigned());

        $guard->activate();
        $this->assertEquals(GuardStatus::ACTIVE, $guard->getStatus());

        $guard->terminate();
        $this->assertEquals(GuardStatus::TERMINATED, $guard->getStatus());
    }

    public function testGuardBankDetails(): void
    {
        $guard = new Guard();
        $guard->setTenantId('t-1')->setEmployeeNumber('G-003')
            ->setFirstName('C')->setLastName('D')->setPhone('456')
            ->setBankName('First Bank')
            ->setBankAccountNumber('3012345678')
            ->setBankAccountName('Musa Ibrahim');

        $this->assertEquals('First Bank', $guard->getBankName());
        $this->assertEquals('3012345678', $guard->getBankAccountNumber());
    }

    public function testGuardToArray(): void
    {
        $guard = new Guard();
        $guard->setTenantId('t-1')
            ->setEmployeeNumber('G-004')
            ->setFirstName('Kelechi')
            ->setLastName('Eze')
            ->setPhone('+2348012345678')
            ->setStatus(GuardStatus::ACTIVE);

        $arr = $guard->toArray();
        $this->assertEquals('Kelechi Eze', $arr['full_name']);
        $this->assertEquals('active', $arr['status']);
        $this->assertEquals('Active', $arr['status_label']);
        $this->assertEquals('G-004', $arr['employee_number']);
    }

    // ── GuardSkill ───────────────────────────────────

    public function testGuardSkill(): void
    {
        $skill = new GuardSkill();
        $skill->setTenantId('t-1')
            ->setName('Armed Guard')
            ->setDescription('Licensed to carry firearms');

        $this->assertEquals('Armed Guard', $skill->getName());
        $arr = $skill->toArray();
        $this->assertEquals('Armed Guard', $arr['name']);
    }

    // ── GuardSkillAssignment ─────────────────────────

    public function testSkillAssignment(): void
    {
        $sa = new GuardSkillAssignment();
        $sa->setGuardId('guard-1')
            ->setSkillId('skill-1')
            ->setCertifiedAt(new \DateTimeImmutable('2025-01-01'))
            ->setExpiresAt(new \DateTimeImmutable('2026-12-31'));

        $this->assertEquals('guard-1', $sa->getGuardId());
        $this->assertFalse($sa->isExpired());

        // Expired
        $sa->setExpiresAt(new \DateTimeImmutable('2024-01-01'));
        $this->assertTrue($sa->isExpired());
    }

    // ── GuardDocument ────────────────────────────────

    public function testGuardDocument(): void
    {
        $doc = new GuardDocument();
        $doc->setGuardId('guard-1')
            ->setDocumentType(DocumentType::LICENSE)
            ->setTitle('Security License')
            ->setFileUrl('/uploads/license.pdf')
            ->setIssueDate(new \DateTimeImmutable('2025-01-01'))
            ->setExpiryDate(new \DateTimeImmutable('2026-12-31'));

        $this->assertEquals(DocumentType::LICENSE, $doc->getDocumentType());
        $this->assertFalse($doc->isExpired());
        $this->assertFalse($doc->isVerified());

        $doc->verify();
        $this->assertTrue($doc->isVerified());
    }

    public function testDocumentExpiry(): void
    {
        $doc = new GuardDocument();
        $doc->setGuardId('guard-1')
            ->setDocumentType(DocumentType::MEDICAL)
            ->setTitle('Medical')
            ->setFileUrl('/medical.pdf')
            ->setExpiryDate(new \DateTimeImmutable('-1 day'));

        $this->assertTrue($doc->isExpired());
        $this->assertFalse($doc->isExpiringSoon());
    }

    public function testDocumentExpiringSoon(): void
    {
        $doc = new GuardDocument();
        $doc->setGuardId('guard-1')
            ->setDocumentType(DocumentType::CERTIFICATE)
            ->setTitle('Cert')
            ->setFileUrl('/cert.pdf')
            ->setExpiryDate(new \DateTimeImmutable('+15 days'));

        $this->assertFalse($doc->isExpired());
        $this->assertTrue($doc->isExpiringSoon(30));
        $this->assertFalse($doc->isExpiringSoon(10));
    }

    public function testDocumentToArray(): void
    {
        $doc = new GuardDocument();
        $doc->setGuardId('guard-1')
            ->setDocumentType(DocumentType::ID_CARD)
            ->setTitle('National ID')
            ->setFileUrl('/id.pdf');

        $arr = $doc->toArray();
        $this->assertEquals('id_card', $arr['document_type']);
        $this->assertEquals('ID Card', $arr['document_type_label']);
        $this->assertFalse($arr['is_verified']);
    }

    // ── ClientStatus ─────────────────────────────────

    public function testClientStatusOperational(): void
    {
        $this->assertTrue(ClientStatus::ACTIVE->isOperational());
        $this->assertFalse(ClientStatus::INACTIVE->isOperational());
    }

    // ── BillingType ──────────────────────────────────

    public function testBillingTypeLabels(): void
    {
        $this->assertEquals('Hourly', BillingType::HOURLY->label());
        $this->assertEquals('Contract', BillingType::CONTRACT->label());
    }

    // ── Client Entity ────────────────────────────────

    public function testClientCreation(): void
    {
        $client = new Client();
        $client->setTenantId('t-1')
            ->setCompanyName('Silver Creek Estates')
            ->setContactName('Bessie Cooper')
            ->setContactEmail('bessie@silvercreek.ng')
            ->setContactPhone('+2348012345678')
            ->setCity('Lagos')
            ->setBillingType(BillingType::MONTHLY)
            ->setBillingRate(250000.00);

        $this->assertNotEmpty($client->getId());
        $this->assertEquals('Silver Creek Estates', $client->getCompanyName());
        $this->assertEquals(BillingType::MONTHLY, $client->getBillingType());
        $this->assertEquals(250000.00, $client->getBillingRate());
        $this->assertEquals(ClientStatus::ACTIVE, $client->getStatus());
    }

    public function testClientContract(): void
    {
        $client = new Client();
        $client->setTenantId('t-1')
            ->setCompanyName('Test Co')
            ->setContactName('A')->setContactEmail('a@b.com')->setContactPhone('123')
            ->setContractStart(new \DateTimeImmutable('2025-01-01'))
            ->setContractEnd(new \DateTimeImmutable('2025-12-31'));

        $this->assertNotNull($client->getContractStart());
        $this->assertNotNull($client->getContractEnd());
    }

    public function testClientToArray(): void
    {
        $client = new Client();
        $client->setTenantId('t-1')
            ->setCompanyName('Horizon Heights')
            ->setContactName('Wade Warren')
            ->setContactEmail('wade@horizon.ng')
            ->setContactPhone('+234801234')
            ->setBillingType(BillingType::CONTRACT);

        $arr = $client->toArray();
        $this->assertEquals('Horizon Heights', $arr['company_name']);
        $this->assertEquals('active', $arr['status']);
        $this->assertEquals('contract', $arr['billing_type']);
    }

    // ── ClientContact ────────────────────────────────

    public function testClientContact(): void
    {
        $contact = new ClientContact();
        $contact->setClientId('client-1')
            ->setName('Funmi Adeyemi')
            ->setRole('Facility Manager')
            ->setEmail('funmi@client.ng')
            ->setPhone('+234801234')
            ->setIsPrimary(true);

        $this->assertEquals('Funmi Adeyemi', $contact->getName());
        $this->assertEquals('Facility Manager', $contact->getRole());
        $this->assertTrue($contact->isPrimary());

        $arr = $contact->toArray();
        $this->assertTrue($arr['is_primary']);
    }

    // ── DailySnapshot ────────────────────────────────

    public function testDailySnapshot(): void
    {
        $snap = new DailySnapshot();
        $snap->setTenantId('t-1')
            ->setTotalGuards(24)
            ->setGuardsOnDuty(22)
            ->setGuardsLate(1)
            ->setGuardsAbsent(1)
            ->setTotalSites(12)
            ->setSitesCovered(11)
            ->setIncidentsCount(2)
            ->setShiftsTotal(24)
            ->setShiftsFilled(22);

        $this->assertEquals(91.7, $snap->getAttendanceRate());
        $this->assertEquals(91.7, $snap->getSiteCoverageRate());
    }

    public function testDailySnapshotZeroDivision(): void
    {
        $snap = new DailySnapshot();
        $snap->setTenantId('t-1');

        $this->assertEquals(0.0, $snap->getAttendanceRate());
        $this->assertEquals(0.0, $snap->getSiteCoverageRate());
    }

    public function testDailySnapshotToArray(): void
    {
        $snap = new DailySnapshot();
        $snap->setTenantId('t-1')
            ->setTotalGuards(10)
            ->setGuardsOnDuty(8)
            ->setTotalSites(5)
            ->setSitesCovered(5);

        $arr = $snap->toArray();
        $this->assertEquals(80.0, $arr['attendance_rate']);
        $this->assertEquals(100.0, $arr['site_coverage_rate']);
    }
}
