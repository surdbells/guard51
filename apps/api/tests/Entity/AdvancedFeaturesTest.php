<?php
declare(strict_types=1);
namespace Guard51\Tests\Entity;

use Guard51\Entity\AuditAction;
use Guard51\Entity\AuditLog;
use Guard51\Entity\GuardLicense;
use Guard51\Entity\GuardPerformanceIndex;
use Guard51\Entity\LicenseType;
use Guard51\Entity\Property;
use Guard51\Entity\TwoFactorSecret;
use PHPUnit\Framework\TestCase;

class AdvancedFeaturesTest extends TestCase
{
    public function testGuardLicenseCreation(): void
    {
        $l = new GuardLicense();
        $l->setTenantId('t-1')->setGuardId('g-1')->setLicenseType(LicenseType::SECURITY_LICENSE)
            ->setName('NSCDC Security License')->setLicenseNumber('SEC-2026-001')
            ->setIssuingAuthority('Nigeria Security and Civil Defence Corps')
            ->setIssueDate(new \DateTimeImmutable('2025-01-01'))
            ->setExpiryDate(new \DateTimeImmutable('2027-01-01'));
        $arr = $l->toArray();
        $this->assertEquals('security_license', $arr['license_type']);
        $this->assertFalse($arr['is_expired']);
        $this->assertGreaterThan(0, $arr['days_until_expiry']);
    }

    public function testExpiredLicense(): void
    {
        $l = new GuardLicense();
        $l->setTenantId('t-1')->setGuardId('g-1')->setLicenseType(LicenseType::FIRST_AID)
            ->setName('First Aid Cert')->setIssueDate(new \DateTimeImmutable('2024-01-01'))
            ->setExpiryDate(new \DateTimeImmutable('2025-01-01'));
        $this->assertTrue($l->isExpired());
        $this->assertFalse($l->isExpiringSoon());
    }

    public function testExpiringSoonLicense(): void
    {
        $l = new GuardLicense();
        $l->setTenantId('t-1')->setGuardId('g-1')->setLicenseType(LicenseType::CPR)
            ->setName('CPR Cert')->setIssueDate(new \DateTimeImmutable('-11 months'))
            ->setExpiryDate(new \DateTimeImmutable('+15 days'));
        $this->assertFalse($l->isExpired());
        $this->assertTrue($l->isExpiringSoon());
    }

    public function testTwoFactorSecret(): void
    {
        $tfa = new TwoFactorSecret();
        $tfa->setUserId('u-1')->setSecret('JBSWY3DPEHPK3PXP')
            ->setBackupCodes(['12345678', '87654321', '11223344']);
        $this->assertFalse($tfa->isEnabled());
        $this->assertEquals(3, count($tfa->getBackupCodes()));

        $tfa->enable();
        $this->assertTrue($tfa->isEnabled());
    }

    public function testBackupCodeUsage(): void
    {
        $tfa = new TwoFactorSecret();
        $tfa->setUserId('u-1')->setSecret('TEST')
            ->setBackupCodes(['11111111', '22222222', '33333333']);

        $this->assertTrue($tfa->useBackupCode('22222222'));
        $this->assertEquals(2, count($tfa->getBackupCodes()));
        $this->assertFalse($tfa->useBackupCode('99999999'));
    }

    public function testAuditLog(): void
    {
        $log = new AuditLog();
        $log->setTenantId('t-1')->setUserId('u-1')->setAction(AuditAction::LOGIN)
            ->setResourceType('auth')->setDescription('User logged in successfully')
            ->setIpAddress('41.190.2.55')->setUserAgent('Mozilla/5.0');
        $arr = $log->toArray();
        $this->assertEquals('login', $arr['action']);
        $this->assertEquals('Login', $arr['action_label']);
        $this->assertEquals('41.190.2.55', $arr['ip_address']);
    }

    public function testAuditActions(): void
    {
        $this->assertEquals('Create', AuditAction::CREATE->label());
        $this->assertEquals('Enable 2fa', AuditAction::ENABLE_2FA->label());
        $this->assertEquals('Permission change', AuditAction::PERMISSION_CHANGE->label());
    }

    public function testPerformanceIndex(): void
    {
        $perf = new GuardPerformanceIndex();
        $perf->setTenantId('t-1')->setGuardId('g-1')->setPeriodMonth('2026-03')
            ->setPunctualityScore(95.0)->setTourComplianceScore(90.0)
            ->setReportCompletionScore(88.0)->setIncidentResponseScore(85.0)
            ->calculateOverall();
        $arr = $perf->toArray();
        // 95*0.3 + 90*0.25 + 88*0.25 + 85*0.2 = 28.5 + 22.5 + 22 + 17 = 90
        $this->assertEquals(90.0, $arr['overall_score']);
        $this->assertEquals('A', $arr['grade']);
    }

    public function testPerformanceGrades(): void
    {
        $perf = new GuardPerformanceIndex();
        $perf->setTenantId('t-1')->setGuardId('g-1')->setPeriodMonth('2026-03')
            ->setPunctualityScore(100)->setTourComplianceScore(100)
            ->setReportCompletionScore(100)->setIncidentResponseScore(100)
            ->calculateOverall();
        $this->assertEquals('A+', $perf->toArray()['grade']);

        $perf2 = new GuardPerformanceIndex();
        $perf2->setTenantId('t-1')->setGuardId('g-2')->setPeriodMonth('2026-03')
            ->setPunctualityScore(40)->setTourComplianceScore(30)
            ->setReportCompletionScore(35)->setIncidentResponseScore(25)
            ->calculateOverall();
        $this->assertEquals('F', $perf2->toArray()['grade']);
    }

    public function testProperty(): void
    {
        $p = new Property();
        $p->setTenantId('t-1')->setName('Lagos HQ')->setAddress('42 Ozumba Mbadiwe')
            ->setCity('Lagos')->setState('Lagos');
        $arr = $p->toArray();
        $this->assertEquals('Lagos HQ', $arr['name']);
        $this->assertTrue($arr['is_active']);
    }
}
