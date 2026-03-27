<?php

declare(strict_types=1);

namespace Guard51\Tests\Entity;

use Guard51\Entity\CustomReportTemplate;
use Guard51\Entity\DailyActivityReport;
use Guard51\Entity\DispatchAssignment;
use Guard51\Entity\DispatchAssignmentStatus;
use Guard51\Entity\DispatchCall;
use Guard51\Entity\DispatchCallType;
use Guard51\Entity\DispatchStatus;
use Guard51\Entity\IncidentReport;
use Guard51\Entity\IncidentStatus;
use Guard51\Entity\IncidentType;
use Guard51\Entity\MediaType;
use Guard51\Entity\ReportStatus;
use Guard51\Entity\Severity;
use Guard51\Entity\Task;
use Guard51\Entity\TaskStatus;
use Guard51\Entity\WatchModeLog;
use PHPUnit\Framework\TestCase;

class ReportingDispatchTest extends TestCase
{
    // ── Enums ────────────────────────────────────────

    public function testSeverityLevels(): void
    {
        $this->assertGreaterThan(Severity::LOW->level(), Severity::MEDIUM->level());
        $this->assertGreaterThan(Severity::HIGH->level(), Severity::CRITICAL->level());
    }

    public function testIncidentStatusActive(): void
    {
        $this->assertTrue(IncidentStatus::REPORTED->isActive());
        $this->assertTrue(IncidentStatus::INVESTIGATING->isActive());
        $this->assertFalse(IncidentStatus::RESOLVED->isActive());
        $this->assertTrue(IncidentStatus::RESOLVED->isTerminal());
    }

    public function testDispatchStatusActive(): void
    {
        $this->assertTrue(DispatchStatus::RECEIVED->isActive());
        $this->assertTrue(DispatchStatus::DISPATCHED->isActive());
        $this->assertFalse(DispatchStatus::RESOLVED->isActive());
    }

    public function testTaskStatusActive(): void
    {
        $this->assertTrue(TaskStatus::PENDING->isActive());
        $this->assertTrue(TaskStatus::IN_PROGRESS->isActive());
        $this->assertTrue(TaskStatus::OVERDUE->isActive());
        $this->assertFalse(TaskStatus::COMPLETED->isActive());
    }

    // ── DailyActivityReport ──────────────────────────

    public function testDARCreation(): void
    {
        $dar = new DailyActivityReport();
        $dar->setTenantId('t-1')->setGuardId('g-1')->setSiteId('s-1')
            ->setContent('Routine patrol completed. All clear.')
            ->setWeather('Clear skies');

        $this->assertEquals(ReportStatus::DRAFT, $dar->getStatus());
        $this->assertEquals('Routine patrol completed. All clear.', $dar->getContent());
    }

    public function testDARWorkflow(): void
    {
        $dar = new DailyActivityReport();
        $dar->setTenantId('t-1')->setGuardId('g-1')->setSiteId('s-1')
            ->setContent('Test report');

        $dar->submit();
        $this->assertEquals(ReportStatus::SUBMITTED, $dar->getStatus());

        $dar->review('admin-1');
        $this->assertEquals(ReportStatus::REVIEWED, $dar->getStatus());

        $dar2 = new DailyActivityReport();
        $dar2->setTenantId('t-1')->setGuardId('g-2')->setSiteId('s-1')
            ->setContent('Another report');
        $dar2->submit();
        $dar2->approve('admin-1');
        $this->assertEquals(ReportStatus::APPROVED, $dar2->getStatus());
    }

    public function testDARToArray(): void
    {
        $dar = new DailyActivityReport();
        $dar->setTenantId('t-1')->setGuardId('g-1')->setSiteId('s-1')
            ->setContent('Test')->setAttachments([['url' => '/photo.jpg']]);
        $arr = $dar->toArray();
        $this->assertEquals('draft', $arr['status']);
        $this->assertEquals(1, $arr['attachment_count']);
    }

    // ── CustomReportTemplate ─────────────────────────

    public function testCustomTemplate(): void
    {
        $t = new CustomReportTemplate();
        $t->setTenantId('t-1')->setName('Vehicle Inspection')
            ->setDescription('Daily vehicle check form')
            ->setFields([
                ['name' => 'vehicle_number', 'type' => 'text', 'required' => true],
                ['name' => 'mileage', 'type' => 'number', 'required' => true],
                ['name' => 'condition', 'type' => 'select', 'options' => ['good', 'fair', 'poor']],
            ])
            ->setCreatedBy('admin-1');

        $arr = $t->toArray();
        $this->assertEquals('Vehicle Inspection', $arr['name']);
        $this->assertEquals(3, $arr['field_count']);
        $this->assertTrue($arr['is_active']);
    }

    // ── IncidentReport ───────────────────────────────

    public function testIncidentCreation(): void
    {
        $ir = new IncidentReport();
        $ir->setTenantId('t-1')->setGuardId('g-1')->setSiteId('s-1')
            ->setIncidentType(IncidentType::THEFT)->setSeverity(Severity::HIGH)
            ->setTitle('Laptop stolen from office')
            ->setDescription('A laptop was reported stolen from office 3B during the night shift.')
            ->setLocationDetail('Building A, Office 3B');

        $this->assertEquals(IncidentStatus::REPORTED, $ir->getStatus());
        $this->assertEquals(IncidentType::THEFT, $ir->getIncidentType());
        $this->assertEquals(Severity::HIGH, $ir->getSeverity());
        $this->assertTrue($ir->getStatus()->isActive());
    }

    public function testIncidentWorkflow(): void
    {
        $ir = new IncidentReport();
        $ir->setTenantId('t-1')->setGuardId('g-1')->setSiteId('s-1')
            ->setIncidentType(IncidentType::TRESPASS)->setSeverity(Severity::MEDIUM)
            ->setTitle('Trespass detected')->setDescription('Unknown person seen.');

        $ir->acknowledge();
        $this->assertEquals(IncidentStatus::ACKNOWLEDGED, $ir->getStatus());

        $ir->investigate();
        $this->assertEquals(IncidentStatus::INVESTIGATING, $ir->getStatus());

        $ir->resolve('admin-1', 'Person identified as delivery driver. No further action needed.');
        $this->assertEquals(IncidentStatus::RESOLVED, $ir->getStatus());
        $this->assertFalse($ir->getStatus()->isActive());
    }

    public function testIncidentEscalation(): void
    {
        $ir = new IncidentReport();
        $ir->setTenantId('t-1')->setGuardId('g-1')->setSiteId('s-1')
            ->setIncidentType(IncidentType::ASSAULT)->setSeverity(Severity::CRITICAL)
            ->setTitle('Assault on guard')->setDescription('Guard was attacked.');

        $ir->escalate();
        $this->assertEquals(IncidentStatus::ESCALATED, $ir->getStatus());
    }

    public function testIncidentToArray(): void
    {
        $ir = new IncidentReport();
        $ir->setTenantId('t-1')->setGuardId('g-1')->setSiteId('s-1')
            ->setIncidentType(IncidentType::FIRE)->setSeverity(Severity::CRITICAL)
            ->setTitle('Fire alarm')->setDescription('Fire detected.');
        $arr = $ir->toArray();
        $this->assertEquals('fire', $arr['incident_type']);
        $this->assertEquals('Fire', $arr['incident_type_label']);
        $this->assertEquals('critical', $arr['severity']);
        $this->assertTrue($arr['is_active']);
    }

    // ── DispatchCall ─────────────────────────────────

    public function testDispatchCallCreation(): void
    {
        $call = new DispatchCall();
        $call->setTenantId('t-1')->setCallerName('Mrs. Adeyemi')->setCallerPhone('+2348012345678')
            ->setCallType(DispatchCallType::EMERGENCY)->setPriority(Severity::HIGH)
            ->setDescription('Suspicious persons near the gate')->setCreatedBy('dispatcher-1');

        $this->assertEquals(DispatchStatus::RECEIVED, $call->getStatus());
        $this->assertNull($call->getResponseTimeMinutes());
    }

    public function testDispatchWorkflow(): void
    {
        $call = new DispatchCall();
        $call->setTenantId('t-1')->setCallerName('Test')->setCallType(DispatchCallType::ROUTINE)
            ->setPriority(Severity::LOW)->setDescription('Test call')->setCreatedBy('d-1');

        $call->dispatch();
        $this->assertEquals(DispatchStatus::DISPATCHED, $call->getStatus());
        $this->assertNotNull($call->getResponseTimeMinutes());

        $call->markInProgress();
        $this->assertEquals(DispatchStatus::IN_PROGRESS, $call->getStatus());

        $call->resolve('Situation resolved. No further action needed.');
        $this->assertEquals(DispatchStatus::RESOLVED, $call->getStatus());
        $this->assertFalse($call->getStatus()->isActive());
    }

    // ── DispatchAssignment ───────────────────────────

    public function testDispatchAssignmentWorkflow(): void
    {
        $a = new DispatchAssignment();
        $a->setDispatchId('call-1')->setGuardId('g-1');

        $this->assertEquals(DispatchAssignmentStatus::ASSIGNED, $a->getStatus());

        $a->acknowledge();
        $this->assertEquals(DispatchAssignmentStatus::ACKNOWLEDGED, $a->getStatus());

        $a->markEnRoute();
        $this->assertEquals(DispatchAssignmentStatus::EN_ROUTE, $a->getStatus());

        $a->markOnScene();
        $this->assertEquals(DispatchAssignmentStatus::ON_SCENE, $a->getStatus());

        $a->complete('Situation handled. All clear.');
        $this->assertEquals(DispatchAssignmentStatus::COMPLETED, $a->getStatus());
    }

    // ── Task ─────────────────────────────────────────

    public function testTaskCreation(): void
    {
        $task = new Task();
        $task->setTenantId('t-1')->setSiteId('s-1')->setAssignedTo('g-1')->setAssignedBy('admin-1')
            ->setTitle('Check fire extinguisher expiry')
            ->setDescription('Verify all fire extinguishers on Floor 2 are within expiry date.')
            ->setPriority(Severity::MEDIUM)
            ->setDueDate(new \DateTimeImmutable('+2 hours'));

        $this->assertEquals(TaskStatus::PENDING, $task->getStatus());
        $this->assertFalse($task->isOverdue());
    }

    public function testTaskWorkflow(): void
    {
        $task = new Task();
        $task->setTenantId('t-1')->setSiteId('s-1')->setAssignedTo('g-1')->setAssignedBy('admin-1')
            ->setTitle('Test task')->setDescription('Test');

        $task->start();
        $this->assertEquals(TaskStatus::IN_PROGRESS, $task->getStatus());

        $task->complete('All done. 3 extinguishers replaced.');
        $this->assertEquals(TaskStatus::COMPLETED, $task->getStatus());
        $this->assertFalse($task->getStatus()->isActive());
    }

    public function testTaskOverdue(): void
    {
        $task = new Task();
        $task->setTenantId('t-1')->setSiteId('s-1')->setAssignedTo('g-1')->setAssignedBy('admin-1')
            ->setTitle('Overdue task')->setDescription('This is overdue')
            ->setDueDate(new \DateTimeImmutable('-1 hour'));

        $this->assertTrue($task->isOverdue());

        $task->markOverdue();
        $this->assertEquals(TaskStatus::OVERDUE, $task->getStatus());
    }

    public function testTaskToArray(): void
    {
        $task = new Task();
        $task->setTenantId('t-1')->setSiteId('s-1')->setAssignedTo('g-1')->setAssignedBy('admin-1')
            ->setTitle('Test')->setDescription('Desc')->setPriority(Severity::HIGH)
            ->setAttachments([['url' => '/doc.pdf']]);
        $arr = $task->toArray();
        $this->assertEquals('high', $arr['priority']);
        $this->assertEquals('High', $arr['priority_label']);
        $this->assertEquals('pending', $arr['status']);
    }

    // ── WatchModeLog ─────────────────────────────────

    public function testWatchModeLog(): void
    {
        $log = new WatchModeLog();
        $log->setTenantId('t-1')->setGuardId('g-1')->setSiteId('s-1')
            ->setMediaType(MediaType::PHOTO)->setMediaUrl('/uploads/watch/img001.jpg')
            ->setCaption('Broken fence near parking lot');
        $arr = $log->toArray();
        $this->assertEquals('photo', $arr['media_type']);
        $this->assertEquals('Broken fence near parking lot', $arr['caption']);
    }
}
