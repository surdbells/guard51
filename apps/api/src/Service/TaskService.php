<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\Severity;
use Guard51\Entity\Task;
use Guard51\Entity\TaskStatus;
use Guard51\Exception\ApiException;
use Guard51\Repository\TaskRepository;
use Psr\Log\LoggerInterface;

final class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepo,
        private readonly LoggerInterface $logger,
    ) {}

    public function createTask(string $tenantId, array $data, string $assignedBy): Task
    {
        if (empty($data['site_id']) || empty($data['assigned_to']) || empty($data['title']) || empty($data['description'])) {
            throw ApiException::validation('site_id, assigned_to, title, description required.');
        }
        $task = new Task();
        $task->setTenantId($tenantId)->setSiteId($data['site_id'])->setAssignedTo($data['assigned_to'])
            ->setAssignedBy($assignedBy)->setTitle($data['title'])->setDescription($data['description']);
        if (!empty($data['priority'])) $task->setPriority(Severity::from($data['priority']));
        if (isset($data['due_date'])) $task->setDueDate(new \DateTimeImmutable($data['due_date']));
        if (isset($data['attachments'])) $task->setAttachments($data['attachments']);
        $this->taskRepo->save($task);
        return $task;
    }

    public function updateStatus(string $taskId, string $action, ?string $notes = null): Task
    {
        $task = $this->taskRepo->findOrFail($taskId);
        match ($action) {
            'start' => $task->start(),
            'complete' => $task->complete($notes),
            'cancel' => $task->cancel(),
            default => throw ApiException::validation("Invalid action: {$action}"),
        };
        $this->taskRepo->save($task);
        return $task;
    }

    public function listByTenant(string $tenantId, ?string $status = null): array { return $this->taskRepo->findByTenant($tenantId, $status); }
    public function listByGuard(string $guardId): array { return $this->taskRepo->findByGuard($guardId); }
    public function listBySite(string $siteId): array { return $this->taskRepo->findBySite($siteId); }
    public function findOverdue(string $tenantId): array { return $this->taskRepo->findOverdue($tenantId); }

    public function detectOverdueTasks(string $tenantId): int
    {
        $overdue = $this->taskRepo->findOverdue($tenantId);
        $count = 0;
        foreach ($overdue as $task) {
            if ($task->getStatus() !== TaskStatus::OVERDUE) {
                $task->markOverdue();
                $this->taskRepo->save($task);
                $count++;
            }
        }
        return $count;
    }
}
