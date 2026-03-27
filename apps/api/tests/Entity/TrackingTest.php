<?php

declare(strict_types=1);

namespace Guard51\Tests\Entity;

use Guard51\Entity\CheckpointType;
use Guard51\Entity\GeofenceAlert;
use Guard51\Entity\GeofenceAlertType;
use Guard51\Entity\GuardLocation;
use Guard51\Entity\IdleAlert;
use Guard51\Entity\LocationSource;
use Guard51\Entity\PanicAlert;
use Guard51\Entity\PanicAlertStatus;
use Guard51\Entity\ScanMethod;
use Guard51\Entity\TourCheckpoint;
use Guard51\Entity\TourCheckpointScan;
use Guard51\Entity\TourSession;
use Guard51\Entity\TourSessionStatus;
use PHPUnit\Framework\TestCase;

class TrackingTest extends TestCase
{
    // ── Enums ────────────────────────────────────────

    public function testLocationSourceLabels(): void
    {
        $this->assertEquals('WebSocket', LocationSource::WEBSOCKET->label());
        $this->assertEquals('HTTP Poll', LocationSource::HTTP_POLL->label());
        $this->assertEquals('Offline Sync', LocationSource::OFFLINE_SYNC->label());
    }

    public function testGeofenceAlertTypeSeverity(): void
    {
        $this->assertEquals('high', GeofenceAlertType::EXIT->severity());
        $this->assertEquals('critical', GeofenceAlertType::ENTRY_UNAUTHORIZED->severity());
        $this->assertEquals('medium', GeofenceAlertType::EXTENDED_ABSENCE->severity());
    }

    public function testPanicAlertStatusActive(): void
    {
        $this->assertTrue(PanicAlertStatus::TRIGGERED->isActive());
        $this->assertTrue(PanicAlertStatus::ACKNOWLEDGED->isActive());
        $this->assertTrue(PanicAlertStatus::RESPONDING->isActive());
        $this->assertFalse(PanicAlertStatus::RESOLVED->isActive());
        $this->assertFalse(PanicAlertStatus::FALSE_ALARM->isActive());
    }

    public function testPanicAlertStatusTerminal(): void
    {
        $this->assertTrue(PanicAlertStatus::RESOLVED->isTerminal());
        $this->assertTrue(PanicAlertStatus::FALSE_ALARM->isTerminal());
        $this->assertFalse(PanicAlertStatus::TRIGGERED->isTerminal());
    }

    public function testCheckpointTypeLabels(): void
    {
        $this->assertEquals('NFC Tag', CheckpointType::NFC->label());
        $this->assertEquals('QR Code', CheckpointType::QR->label());
        $this->assertEquals('Virtual (GPS)', CheckpointType::VIRTUAL->label());
    }

    public function testScanMethodLabels(): void
    {
        $this->assertEquals('NFC', ScanMethod::NFC->label());
        $this->assertEquals('QR Code', ScanMethod::QR->label());
        $this->assertEquals('Virtual GPS', ScanMethod::VIRTUAL_GPS->label());
    }

    // ── GuardLocation ────────────────────────────────

    public function testGuardLocationCreation(): void
    {
        $loc = new GuardLocation();
        $loc->setTenantId('t-1')
            ->setGuardId('g-1')
            ->setSiteId('site-1')
            ->setLatitude(6.4281)
            ->setLongitude(3.4219)
            ->setAccuracy(8.5)
            ->setSpeed(1.2)
            ->setHeading(45.0)
            ->setBatteryLevel(85)
            ->setIsMoving(true)
            ->setSource(LocationSource::WEBSOCKET)
            ->setRecordedAt(new \DateTimeImmutable());

        $this->assertNotEmpty($loc->getId());
        $this->assertEquals(6.4281, $loc->getLatitude());
        $this->assertEquals(3.4219, $loc->getLongitude());
        $this->assertEquals(8.5, $loc->getAccuracy());
        $this->assertEquals(1.2, $loc->getSpeed());
        $this->assertEquals(85, $loc->getBatteryLevel());
        $this->assertTrue($loc->isMoving());
        $this->assertEquals(LocationSource::WEBSOCKET, $loc->getSource());
    }

    public function testGuardLocationToArray(): void
    {
        $loc = new GuardLocation();
        $loc->setTenantId('t-1')->setGuardId('g-1')
            ->setLatitude(6.5)->setLongitude(3.4)
            ->setAccuracy(10)->setSource(LocationSource::HTTP_POLL)
            ->setRecordedAt(new \DateTimeImmutable());

        $arr = $loc->toArray();
        $this->assertEquals(6.5, $arr['lat']);
        $this->assertEquals(3.4, $arr['lng']);
        $this->assertEquals('http_poll', $arr['source']);
    }

    // ── GeofenceAlert ────────────────────────────────

    public function testGeofenceAlertCreation(): void
    {
        $alert = new GeofenceAlert();
        $alert->setTenantId('t-1')
            ->setGuardId('g-1')
            ->setSiteId('site-1')
            ->setAlertType(GeofenceAlertType::EXIT)
            ->setLatitude(6.43)
            ->setLongitude(3.42)
            ->setMessage('Guard exited geofence.');

        $this->assertFalse($alert->isAcknowledged());
        $this->assertEquals(GeofenceAlertType::EXIT, $alert->getAlertType());
    }

    public function testGeofenceAlertAcknowledge(): void
    {
        $alert = new GeofenceAlert();
        $alert->setTenantId('t-1')->setGuardId('g-1')->setSiteId('s-1')
            ->setAlertType(GeofenceAlertType::EXIT)
            ->setLatitude(6.5)->setLongitude(3.4)
            ->setMessage('Test');

        $alert->acknowledge('admin-1');
        $this->assertTrue($alert->isAcknowledged());
        $arr = $alert->toArray();
        $this->assertEquals('admin-1', $arr['acknowledged_by']);
        $this->assertEquals('high', $arr['severity']);
    }

    // ── IdleAlert ────────────────────────────────────

    public function testIdleAlert(): void
    {
        $alert = new IdleAlert();
        $alert->setTenantId('t-1')->setGuardId('g-1')->setSiteId('s-1')
            ->setIdleStartAt(new \DateTimeImmutable('-30 minutes'))
            ->setIdleDurationMinutes(30)
            ->setLastKnownLat(6.5)->setLastKnownLng(3.4);

        $this->assertFalse($alert->isAcknowledged());
        $this->assertEquals(30, $alert->getIdleDurationMinutes());

        $alert->acknowledge('admin-1');
        $this->assertTrue($alert->isAcknowledged());
    }

    // ── TourCheckpoint ───────────────────────────────

    public function testTourCheckpointCreation(): void
    {
        $cp = new TourCheckpoint();
        $cp->setTenantId('t-1')
            ->setSiteId('site-1')
            ->setName('Main Gate')
            ->setCheckpointType(CheckpointType::QR)
            ->setQrCodeValue('GUARD51-CP-MAINGATE-001')
            ->setSequenceOrder(1)
            ->setIsRequired(true);

        $this->assertEquals('Main Gate', $cp->getName());
        $this->assertEquals(CheckpointType::QR, $cp->getCheckpointType());
        $this->assertEquals('GUARD51-CP-MAINGATE-001', $cp->getQrCodeValue());
        $this->assertEquals(1, $cp->getSequenceOrder());
        $this->assertTrue($cp->isRequired());
    }

    public function testVirtualCheckpoint(): void
    {
        $cp = new TourCheckpoint();
        $cp->setTenantId('t-1')->setSiteId('s-1')
            ->setName('Parking Lot B')
            ->setCheckpointType(CheckpointType::VIRTUAL)
            ->setLatitude(6.4281)->setLongitude(3.4219)
            ->setVirtualRadius(15);

        $this->assertEquals(CheckpointType::VIRTUAL, $cp->getCheckpointType());
        $this->assertEquals(15, $cp->getVirtualRadius());
        $this->assertEquals(6.4281, $cp->getLatitude());
    }

    public function testTourCheckpointToArray(): void
    {
        $cp = new TourCheckpoint();
        $cp->setTenantId('t-1')->setSiteId('s-1')
            ->setName('Server Room')
            ->setCheckpointType(CheckpointType::NFC)
            ->setNfcTagId('NFC-A1B2C3');

        $arr = $cp->toArray();
        $this->assertEquals('Server Room', $arr['name']);
        $this->assertEquals('nfc', $arr['checkpoint_type']);
        $this->assertEquals('NFC Tag', $arr['checkpoint_type_label']);
        $this->assertEquals('NFC-A1B2C3', $arr['nfc_tag_id']);
    }

    // ── TourSession ──────────────────────────────────

    public function testTourSessionCreation(): void
    {
        $session = new TourSession();
        $session->setTenantId('t-1')->setGuardId('g-1')->setSiteId('s-1')
            ->setTotalCheckpoints(5);

        $this->assertEquals(TourSessionStatus::IN_PROGRESS, $session->getStatus());
        $this->assertEquals(5, $session->getTotalCheckpoints());
        $this->assertEquals(0, $session->getScannedCheckpoints());
        $this->assertEquals(0.0, $session->getCompletionRate());
    }

    public function testTourSessionProgress(): void
    {
        $session = new TourSession();
        $session->setTenantId('t-1')->setGuardId('g-1')->setSiteId('s-1')
            ->setTotalCheckpoints(4);

        $session->recordScan();
        $session->recordScan();
        $this->assertEquals(2, $session->getScannedCheckpoints());
        $this->assertEquals(50.0, $session->getCompletionRate());
    }

    public function testTourSessionComplete(): void
    {
        $session = new TourSession();
        $session->setTenantId('t-1')->setGuardId('g-1')->setSiteId('s-1')
            ->setTotalCheckpoints(3);

        $session->recordScan();
        $session->recordScan();
        $session->recordScan();
        $session->complete();

        $this->assertEquals(TourSessionStatus::COMPLETED, $session->getStatus());
        $this->assertEquals(100.0, $session->getCompletionRate());
    }

    public function testTourSessionIncomplete(): void
    {
        $session = new TourSession();
        $session->setTenantId('t-1')->setGuardId('g-1')->setSiteId('s-1')
            ->setTotalCheckpoints(5);

        $session->recordScan();
        $session->recordScan();
        $session->complete();

        $this->assertEquals(TourSessionStatus::INCOMPLETE, $session->getStatus());
        $this->assertEquals(40.0, $session->getCompletionRate());
    }

    // ── TourCheckpointScan ───────────────────────────

    public function testCheckpointScan(): void
    {
        $scan = new TourCheckpointScan();
        $scan->setSessionId('session-1')
            ->setCheckpointId('cp-1')
            ->setScanMethod(ScanMethod::QR)
            ->setLatitude(6.4281)
            ->setLongitude(3.4219)
            ->setNotes('All clear at this checkpoint');

        $arr = $scan->toArray();
        $this->assertEquals('session-1', $arr['session_id']);
        $this->assertEquals('qr', $arr['scan_method']);
        $this->assertEquals('QR Code', $arr['scan_method_label']);
    }

    // ── PanicAlert ───────────────────────────────────

    public function testPanicAlertCreation(): void
    {
        $alert = new PanicAlert();
        $alert->setTenantId('t-1')
            ->setGuardId('g-1')
            ->setSiteId('site-1')
            ->setLatitude(6.4281)
            ->setLongitude(3.4219)
            ->setMessage('Under attack!');

        $this->assertNotEmpty($alert->getId());
        $this->assertEquals(PanicAlertStatus::TRIGGERED, $alert->getStatus());
        $this->assertTrue($alert->isActive());
    }

    public function testPanicAlertWorkflow(): void
    {
        $alert = new PanicAlert();
        $alert->setTenantId('t-1')->setGuardId('g-1')
            ->setLatitude(6.5)->setLongitude(3.4);

        // Triggered → Acknowledged
        $alert->acknowledge('admin-1');
        $this->assertEquals(PanicAlertStatus::ACKNOWLEDGED, $alert->getStatus());
        $this->assertTrue($alert->isActive());

        // Acknowledged → Responding
        $alert->markResponding();
        $this->assertEquals(PanicAlertStatus::RESPONDING, $alert->getStatus());
        $this->assertTrue($alert->isActive());

        // Responding → Resolved
        $alert->resolve('admin-1', 'Situation contained. Police called.');
        $this->assertEquals(PanicAlertStatus::RESOLVED, $alert->getStatus());
        $this->assertFalse($alert->isActive());
    }

    public function testPanicAlertFalseAlarm(): void
    {
        $alert = new PanicAlert();
        $alert->setTenantId('t-1')->setGuardId('g-1')
            ->setLatitude(6.5)->setLongitude(3.4);

        $alert->acknowledge('admin-1');
        $alert->markFalseAlarm('admin-1', 'Accidental trigger');

        $this->assertEquals(PanicAlertStatus::FALSE_ALARM, $alert->getStatus());
        $this->assertFalse($alert->isActive());
        $this->assertTrue($alert->getStatus()->isTerminal());
    }

    public function testPanicAlertToArray(): void
    {
        $alert = new PanicAlert();
        $alert->setTenantId('t-1')->setGuardId('g-1')->setSiteId('s-1')
            ->setLatitude(6.4281)->setLongitude(3.4219)
            ->setMessage('Help!')->setAudioUrl('/recordings/panic-001.mp3');

        $arr = $alert->toArray();
        $this->assertEquals('triggered', $arr['status']);
        $this->assertEquals('Triggered', $arr['status_label']);
        $this->assertTrue($arr['is_active']);
        $this->assertEquals('Help!', $arr['message']);
        $this->assertEquals('/recordings/panic-001.mp3', $arr['audio_url']);
        $this->assertEquals(6.4281, $arr['lat']);
    }
}
