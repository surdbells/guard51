<?php

declare(strict_types=1);

namespace Guard51\Tests\Entity;

use Guard51\Entity\AttendanceRecord;
use Guard51\Entity\AttendanceStatus;
use Guard51\Entity\BreakConfig;
use Guard51\Entity\BreakLog;
use Guard51\Entity\BreakType;
use Guard51\Entity\ClockMethod;
use Guard51\Entity\PassdownLog;
use Guard51\Entity\PassdownPriority;
use Guard51\Entity\Shift;
use Guard51\Entity\ShiftStatus;
use Guard51\Entity\ShiftSwapRequest;
use Guard51\Entity\ShiftTemplate;
use Guard51\Entity\SwapRequestStatus;
use Guard51\Entity\TimeClock;
use Guard51\Entity\TimeClockStatus;
use PHPUnit\Framework\TestCase;

class SchedulingTest extends TestCase
{
    // ── ShiftStatus ──────────────────────────────────

    public function testShiftStatusActive(): void
    {
        $this->assertTrue(ShiftStatus::PUBLISHED->isActive());
        $this->assertTrue(ShiftStatus::CONFIRMED->isActive());
        $this->assertTrue(ShiftStatus::IN_PROGRESS->isActive());
        $this->assertFalse(ShiftStatus::DRAFT->isActive());
        $this->assertFalse(ShiftStatus::COMPLETED->isActive());
    }

    public function testShiftStatusTerminal(): void
    {
        $this->assertTrue(ShiftStatus::COMPLETED->isTerminal());
        $this->assertTrue(ShiftStatus::MISSED->isTerminal());
        $this->assertTrue(ShiftStatus::CANCELLED->isTerminal());
        $this->assertFalse(ShiftStatus::DRAFT->isTerminal());
    }

    public function testShiftStatusCanBeConfirmed(): void
    {
        $this->assertTrue(ShiftStatus::PUBLISHED->canBeConfirmed());
        $this->assertFalse(ShiftStatus::DRAFT->canBeConfirmed());
    }

    // ── ShiftTemplate (string times, ISO days 1-7) ──

    public function testShiftTemplateCreation(): void
    {
        $t = new ShiftTemplate();
        $t->setTenantId('t-1')
            ->setName('Day Shift')
            ->setStartTime('06:00')
            ->setEndTime('18:00')
            ->setDaysOfWeek([1, 2, 3, 4, 5]);

        $this->assertEquals('Day Shift', $t->getName());
        $this->assertEquals(12.0, $t->getDurationHours());
        $this->assertTrue($t->appliesToDay(1)); // Monday
        $this->assertFalse($t->appliesToDay(6)); // Saturday
        $this->assertEquals(['Mon', 'Tue', 'Wed', 'Thu', 'Fri'], $t->getDayLabels());
    }

    public function testShiftTemplateOvernightDuration(): void
    {
        $t = new ShiftTemplate();
        $t->setTenantId('t-1')
            ->setName('Night Shift')
            ->setStartTime('18:00')
            ->setEndTime('06:00');

        $this->assertEquals(12.0, $t->getDurationHours());
        $this->assertTrue($t->isOvernight());
    }

    public function testShiftTemplateToArray(): void
    {
        $t = new ShiftTemplate();
        $t->setTenantId('t-1')
            ->setName('Weekend')
            ->setStartTime('08:00')
            ->setEndTime('20:00')
            ->setDaysOfWeek([6, 7]);

        $arr = $t->toArray();
        $this->assertEquals('Weekend', $arr['name']);
        $this->assertEquals('08:00', $arr['start_time']);
        $this->assertEquals('20:00', $arr['end_time']);
        $this->assertEquals(12.0, $arr['duration_hours']);
        $this->assertEquals(['Sat', 'Sun'], $arr['day_labels']);
    }

    // ── Shift ────────────────────────────────────────

    public function testShiftCreation(): void
    {
        $s = new Shift();
        $s->setTenantId('t-1')
            ->setSiteId('site-1')
            ->setShiftDate(new \DateTimeImmutable('2026-03-27'))
            ->setStartTime(new \DateTimeImmutable('2026-03-27 06:00'))
            ->setEndTime(new \DateTimeImmutable('2026-03-27 18:00'))
            ->setCreatedBy('admin-1');

        $this->assertNotEmpty($s->getId());
        $this->assertEquals(ShiftStatus::DRAFT, $s->getStatus());
        $this->assertFalse($s->isAssigned());
        $this->assertEquals(12.0, $s->getDurationHours());
    }

    public function testShiftPublishAndConfirm(): void
    {
        $s = new Shift();
        $s->setTenantId('t-1')->setSiteId('site-1')
            ->setShiftDate(new \DateTimeImmutable('2026-03-27'))
            ->setStartTime(new \DateTimeImmutable('2026-03-27 06:00'))
            ->setEndTime(new \DateTimeImmutable('2026-03-27 18:00'))
            ->setCreatedBy('admin-1');

        $s->publish();
        $this->assertEquals(ShiftStatus::PUBLISHED, $s->getStatus());

        $s->confirm('guard-1');
        $this->assertEquals(ShiftStatus::CONFIRMED, $s->getStatus());
        $this->assertEquals('guard-1', $s->getGuardId());
        $this->assertTrue($s->isAssigned());
        $this->assertNotNull($s->getConfirmedAt());
    }

    public function testShiftConflictDetection(): void
    {
        $s = new Shift();
        $s->setStartTime(new \DateTimeImmutable('2026-03-27 06:00'))
            ->setEndTime(new \DateTimeImmutable('2026-03-27 18:00'));

        $this->assertTrue($s->hasConflict(new \DateTimeImmutable('2026-03-27 10:00'), new \DateTimeImmutable('2026-03-27 22:00')));
        $this->assertFalse($s->hasConflict(new \DateTimeImmutable('2026-03-27 18:00'), new \DateTimeImmutable('2026-03-28 06:00')));
    }

    public function testShiftLifecycle(): void
    {
        $s = new Shift();
        $s->setTenantId('t-1')->setSiteId('s-1')
            ->setShiftDate(new \DateTimeImmutable())
            ->setStartTime(new \DateTimeImmutable())
            ->setEndTime(new \DateTimeImmutable('+12 hours'))
            ->setCreatedBy('a-1');

        $s->publish();
        $s->confirm('g-1');
        $s->startShift();
        $this->assertEquals(ShiftStatus::IN_PROGRESS, $s->getStatus());
        $s->complete();
        $this->assertTrue($s->getStatus()->isTerminal());
    }

    public function testShiftOpenClaim(): void
    {
        $s = new Shift();
        $s->setTenantId('t-1')->setSiteId('s-1')
            ->setShiftDate(new \DateTimeImmutable())
            ->setStartTime(new \DateTimeImmutable())
            ->setEndTime(new \DateTimeImmutable('+12 hours'))
            ->setCreatedBy('a-1')
            ->setIsOpen(true);

        $this->assertTrue($s->isOpen());
        $s->publish();
        $s->confirm('g-2');
        $this->assertFalse($s->isOpen());
        $this->assertTrue($s->isAssigned());
    }

    // ── ShiftSwapRequest ─────────────────────────────

    public function testSwapRequestCreation(): void
    {
        $r = new ShiftSwapRequest();
        $r->setTenantId('t-1')->setRequestingGuardId('g-1')->setTargetGuardId('g-2')
            ->setShiftId('shift-1')->setReason('Family emergency');

        $this->assertTrue($r->isPending());
        $this->assertEquals('Family emergency', $r->getReason());
    }

    public function testSwapRequestApproval(): void
    {
        $r = new ShiftSwapRequest();
        $r->setTenantId('t-1')->setRequestingGuardId('g-1')->setTargetGuardId('g-2')
            ->setShiftId('shift-1')->setReason('Test');

        $r->approve('admin-1', 'OK');
        $this->assertEquals(SwapRequestStatus::APPROVED, $r->getStatus());
        $this->assertFalse($r->isPending());
        $this->assertNotNull($r->getReviewedAt());
    }

    public function testSwapRequestRejection(): void
    {
        $r = new ShiftSwapRequest();
        $r->setTenantId('t-1')->setRequestingGuardId('g-1')->setTargetGuardId('g-2')
            ->setShiftId('shift-1')->setReason('Test');

        $r->reject('admin-1', 'Short-staffed');
        $this->assertEquals(SwapRequestStatus::REJECTED, $r->getStatus());
        $this->assertTrue($r->getStatus()->isResolved());
    }

    // ── TimeClock ────────────────────────────────────

    public function testTimeClockIn(): void
    {
        $tc = new TimeClock();
        $tc->setTenantId('t-1')->setGuardId('g-1')->setSiteId('site-1')
            ->setClockInLat(6.4281)->setClockInLng(3.4219)
            ->setClockInMethod(ClockMethod::APP_GPS)->setIsWithinGeofenceIn(true);

        $this->assertTrue($tc->isClockedIn());
        $this->assertEquals(TimeClockStatus::CLOCKED_IN, $tc->getStatus());
        $this->assertNull($tc->getTotalHours());
    }

    public function testTimeClockOut(): void
    {
        $tc = new TimeClock();
        $tc->setTenantId('t-1')->setGuardId('g-1')->setSiteId('site-1')
            ->setClockInTime(new \DateTimeImmutable('-8 hours'))
            ->setClockInLat(6.4281)->setClockInLng(3.4219)
            ->setClockInMethod(ClockMethod::APP_GPS)->setIsWithinGeofenceIn(true);

        $tc->clockOut(6.4282, 3.4220, ClockMethod::APP_GPS, true);
        $this->assertEquals(TimeClockStatus::CLOCKED_OUT, $tc->getStatus());
        $this->assertGreaterThan(7.0, $tc->getTotalHours());
    }

    public function testAutoClockOut(): void
    {
        $tc = new TimeClock();
        $tc->setTenantId('t-1')->setGuardId('g-1')->setSiteId('site-1')
            ->setClockInTime(new \DateTimeImmutable('-14 hours'))
            ->setClockInLat(6.4281)->setClockInLng(3.4219)
            ->setClockInMethod(ClockMethod::WEB_PORTAL)->setIsWithinGeofenceIn(false);

        $tc->autoClockOut();
        $this->assertEquals(TimeClockStatus::AUTO_CLOCKED_OUT, $tc->getStatus());
    }

    // ── AttendanceRecord ─────────────────────────────

    public function testAttendancePresent(): void
    {
        $ar = new AttendanceRecord();
        $ar->setTenantId('t-1')->setGuardId('g-1')->setShiftId('s-1')->setSiteId('site-1')
            ->setScheduledStart(new \DateTimeImmutable('2026-03-27 06:00'))
            ->setScheduledEnd(new \DateTimeImmutable('2026-03-27 18:00'));

        $ar->markPresent(new \DateTimeImmutable('2026-03-27 05:58'));
        $this->assertEquals(AttendanceStatus::PRESENT, $ar->getStatus());
        $this->assertEquals(0, $ar->getLateMinutes());
    }

    public function testAttendanceLate(): void
    {
        $ar = new AttendanceRecord();
        $ar->setTenantId('t-1')->setGuardId('g-1')->setShiftId('s-1')->setSiteId('site-1')
            ->setScheduledStart(new \DateTimeImmutable('2026-03-27 06:00'))
            ->setScheduledEnd(new \DateTimeImmutable('2026-03-27 18:00'));

        $ar->markPresent(new \DateTimeImmutable('2026-03-27 06:20'));
        $this->assertEquals(AttendanceStatus::LATE, $ar->getStatus());
        $this->assertEquals(20, $ar->getLateMinutes());
    }

    public function testAttendanceReconcile(): void
    {
        $ar = new AttendanceRecord();
        $ar->setTenantId('t-1')->setGuardId('g-1')->setShiftId('s-1')->setSiteId('site-1')
            ->setScheduledStart(new \DateTimeImmutable('06:00'))
            ->setScheduledEnd(new \DateTimeImmutable('18:00'));

        $ar->reconcile('admin-1', AttendanceStatus::EXCUSED, 'Transport issue');
        $this->assertTrue($ar->isReconciled());
        $this->assertEquals(AttendanceStatus::EXCUSED, $ar->getStatus());
    }

    public function testAttendanceClockOut(): void
    {
        $ar = new AttendanceRecord();
        $ar->setTenantId('t-1')->setGuardId('g-1')->setShiftId('s-1')->setSiteId('site-1')
            ->setScheduledStart(new \DateTimeImmutable('2026-03-27 06:00'))
            ->setScheduledEnd(new \DateTimeImmutable('2026-03-27 18:00'));

        $ar->markPresent(new \DateTimeImmutable('2026-03-27 06:00'));
        $ar->markClockOut(new \DateTimeImmutable('2026-03-27 17:30'));
        $this->assertEqualsWithDelta(11.5, $ar->getTotalWorkedHours(), 0.1);
    }

    // ── BreakConfig ──────────────────────────────────

    public function testBreakConfig(): void
    {
        $bc = new BreakConfig();
        $bc->setTenantId('t-1')->setName('Lunch')->setBreakType(BreakType::PAID)->setDurationMinutes(60);

        $this->assertEquals('Lunch', $bc->getName());
        $this->assertEquals(60, $bc->getDurationMinutes());
    }

    // ── BreakLog ─────────────────────────────────────

    public function testBreakLog(): void
    {
        $bl = new BreakLog();
        $bl->setTimeClockId('tc-1')->setBreakConfigId('bc-1');

        $this->assertTrue($bl->isOnBreak());
        $bl->endBreak();
        $this->assertFalse($bl->isOnBreak());
        $this->assertNotNull($bl->getDurationMinutes());
    }

    // ── PassdownLog ──────────────────────────────────

    public function testPassdownCreation(): void
    {
        $p = new PassdownLog();
        $p->setTenantId('t-1')->setSiteId('site-1')->setGuardId('g-1')
            ->setContent('All clear. Perimeter checked.')
            ->setPriority(PassdownPriority::IMPORTANT)
            ->setAttachments([['url' => '/gate.jpg', 'type' => 'image', 'name' => 'gate.jpg']]);

        $this->assertEquals(PassdownPriority::IMPORTANT, $p->getPriority());
        $this->assertCount(1, $p->getAttachments());
        $this->assertFalse($p->isAcknowledged());
    }

    public function testPassdownAcknowledge(): void
    {
        $p = new PassdownLog();
        $p->setTenantId('t-1')->setSiteId('site-1')->setGuardId('g-1')
            ->setContent('Key handover');

        $p->acknowledge('g-2');
        $this->assertTrue($p->isAcknowledged());
    }

    public function testPassdownToArray(): void
    {
        $p = new PassdownLog();
        $p->setTenantId('t-1')->setSiteId('site-1')->setGuardId('g-1')
            ->setContent('Nothing to report')
            ->setPriority(PassdownPriority::NORMAL);

        $arr = $p->toArray();
        $this->assertEquals('normal', $arr['priority']);
        $this->assertEquals(0, $arr['attachment_count']);
        $this->assertFalse($arr['is_acknowledged']);
    }

    // ── Enums ────────────────────────────────────────

    public function testClockMethodLabels(): void
    {
        $this->assertEquals('App (GPS)', ClockMethod::APP_GPS->label());
        $this->assertEquals('Web Portal', ClockMethod::WEB_PORTAL->label());
    }

    public function testAttendanceStatusPresent(): void
    {
        $this->assertTrue(AttendanceStatus::PRESENT->isPresent());
        $this->assertTrue(AttendanceStatus::LATE->isPresent());
        $this->assertFalse(AttendanceStatus::ABSENT->isPresent());
    }

    public function testPassdownPriorityLevels(): void
    {
        $this->assertGreaterThan(PassdownPriority::NORMAL->level(), PassdownPriority::IMPORTANT->level());
        $this->assertGreaterThan(PassdownPriority::IMPORTANT->level(), PassdownPriority::URGENT->level());
    }
}
