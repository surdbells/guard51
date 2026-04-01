<?php

declare(strict_types=1);

namespace Guard51\Service;

use Guard51\Entity\DocumentType;
use Guard51\Entity\Guard;
use Guard51\Entity\GuardDocument;
use Guard51\Entity\GuardSkill;
use Guard51\Entity\GuardSkillAssignment;
use Guard51\Entity\GuardStatus;
use Guard51\Entity\PayType;
use Guard51\Entity\User;
use Guard51\Entity\UserRole;
use Guard51\Exception\ApiException;
use Guard51\Repository\GuardDocumentRepository;
use Guard51\Repository\GuardRepository;
use Guard51\Repository\GuardSkillRepository;
use Guard51\Repository\SubscriptionPlanRepository;
use Guard51\Repository\SubscriptionRepository;
use Guard51\Repository\TenantUsageMetricRepository;
use Guard51\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class GuardService
{
    public function __construct(
        private readonly GuardRepository $guardRepo,
        private readonly GuardSkillRepository $skillRepo,
        private readonly GuardDocumentRepository $docRepo,
        private readonly UserRepository $userRepo,
        private readonly TenantUsageMetricRepository $usageRepo,
        private readonly SubscriptionRepository $subRepo,
        private readonly SubscriptionPlanRepository $planRepo,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    // ── Guard CRUD ───────────────────────────────────

    public function createGuard(string $tenantId, array $data): Guard
    {
        $this->enforceMaxGuards($tenantId);

        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['phone'])) {
            throw ApiException::validation('First name, last name, and phone are required.');
        }

        // Auto-generate employee number if not provided
        if (empty($data['employee_number'])) {
            $data['employee_number'] = $this->generateEmployeeNumber($tenantId);
        }

        // Check employee number uniqueness
        if ($this->guardRepo->findByEmployeeNumber($tenantId, $data['employee_number'])) {
            throw ApiException::conflict('A guard with this employee number already exists.');
        }

        $guard = new Guard();
        $guard->setTenantId($tenantId);
        $this->hydrateGuard($guard, $data);

        // Optionally create User account for the guard
        if (!empty($data['create_user_account']) && !empty($data['email'])) {
            $user = $this->createGuardUser($tenantId, $data);
            $guard->setUserId($user->getId());
        }

        $this->guardRepo->save($guard);

        $usage = $this->usageRepo->findOrCreateForTenant($tenantId);
        $usage->incrementGuards();
        $this->usageRepo->save($usage);

        $this->logger->info('Guard created.', ['tenant_id' => $tenantId, 'guard' => $guard->getFullName()]);
        return $guard;
    }

    public function updateGuard(string $guardId, array $data): Guard
    {
        $guard = $this->guardRepo->findOrFail($guardId);
        $this->hydrateGuard($guard, $data);
        $this->guardRepo->save($guard);
        return $guard;
    }

    public function getGuard(string $guardId): Guard
    {
        return $this->guardRepo->findOrFail($guardId);
    }

    public function getGuardProfile(string $guardId): array
    {
        $guard = $this->guardRepo->findOrFail($guardId);
        $documents = $this->docRepo->findByGuard($guardId);
        $skillAssignments = $this->em->getRepository(GuardSkillAssignment::class)->findBy(['guardId' => $guardId]);

        $skills = [];
        foreach ($skillAssignments as $sa) {
            $skill = $this->skillRepo->find($sa->getSkillId());
            if ($skill) {
                $skills[] = array_merge($skill->toArray(), $sa->toArray());
            }
        }

        return [
            'guard' => $guard->toArray(),
            'skills' => $skills,
            'documents' => array_map(fn($d) => $d->toArray(), $documents),
            'document_alerts' => count(array_filter($documents, fn($d) => $d->isExpiringSoon() || $d->isExpired())),
        ];
    }

    public function listGuards(string $tenantId, ?string $status = null, ?string $search = null): array
    {
        if ($search) return $this->guardRepo->searchByName($tenantId, $search);
        return $this->guardRepo->findByTenant($tenantId, $status);
    }

    public function updateStatus(string $guardId, GuardStatus $status): Guard
    {
        $guard = $this->guardRepo->findOrFail($guardId);
        $guard->setStatus($status);
        $this->guardRepo->save($guard);
        return $guard;
    }

    public function deleteGuard(string $guardId, string $tenantId): void
    {
        $guard = $this->guardRepo->findOrFail($guardId);
        $guard->terminate();
        $this->guardRepo->save($guard);

        $usage = $this->usageRepo->findOrCreateForTenant($tenantId);
        $usage->decrementGuards();
        $this->usageRepo->save($usage);
    }

    // ── Skills ───────────────────────────────────────

    public function listSkills(string $tenantId): array
    {
        return $this->skillRepo->findByTenant($tenantId);
    }

    public function createSkill(string $tenantId, string $name, ?string $description = null): GuardSkill
    {
        if ($this->skillRepo->findByName($tenantId, $name)) {
            throw ApiException::conflict("Skill '{$name}' already exists.");
        }
        $skill = new GuardSkill();
        $skill->setTenantId($tenantId)->setName($name)->setDescription($description);
        $this->skillRepo->save($skill);
        return $skill;
    }

    public function assignSkill(string $guardId, string $skillId, ?string $certifiedAt = null, ?string $expiresAt = null): GuardSkillAssignment
    {
        $existing = $this->em->getRepository(GuardSkillAssignment::class)->findOneBy(['guardId' => $guardId, 'skillId' => $skillId]);
        if ($existing) throw ApiException::conflict('This skill is already assigned to this guard.');

        $sa = new GuardSkillAssignment();
        $sa->setGuardId($guardId)->setSkillId($skillId);
        if ($certifiedAt) $sa->setCertifiedAt(new \DateTimeImmutable($certifiedAt));
        if ($expiresAt) $sa->setExpiresAt(new \DateTimeImmutable($expiresAt));
        $this->em->persist($sa);
        $this->em->flush();
        return $sa;
    }

    public function removeSkill(string $guardId, string $skillId): void
    {
        $sa = $this->em->getRepository(GuardSkillAssignment::class)->findOneBy(['guardId' => $guardId, 'skillId' => $skillId]);
        if ($sa) { $this->em->remove($sa); $this->em->flush(); }
    }

    // ── Documents ────────────────────────────────────

    public function addDocument(string $guardId, array $data): GuardDocument
    {
        if (empty($data['title']) || empty($data['file_url']) || empty($data['document_type'])) {
            throw ApiException::validation('Document title, file URL, and type are required.');
        }

        $doc = new GuardDocument();
        $doc->setGuardId($guardId)
            ->setTitle($data['title'])
            ->setFileUrl($data['file_url'])
            ->setDocumentType(DocumentType::from($data['document_type']));

        if (isset($data['issue_date'])) $doc->setIssueDate(new \DateTimeImmutable($data['issue_date']));
        if (isset($data['expiry_date'])) $doc->setExpiryDate(new \DateTimeImmutable($data['expiry_date']));
        if (isset($data['notes'])) $doc->setNotes($data['notes']);

        $this->docRepo->save($doc);
        return $doc;
    }

    public function verifyDocument(string $docId): GuardDocument
    {
        $doc = $this->docRepo->findOrFail($docId);
        $doc->verify();
        $this->docRepo->save($doc);
        return $doc;
    }

    public function getDocuments(string $guardId): array
    {
        return $this->docRepo->findByGuard($guardId);
    }

    public function getExpiringDocuments(string $tenantId, int $days = 30): array
    {
        return $this->docRepo->findExpiringSoon($tenantId, $days);
    }

    // ── Helpers ──────────────────────────────────────

    private function enforceMaxGuards(string $tenantId): void
    {
        $subscription = $this->subRepo->findActiveByTenant($tenantId);
        if (!$subscription) return;
        $plan = $this->planRepo->find($subscription->getPlanId());
        if (!$plan) return;
        $usage = $this->usageRepo->findOrCreateForTenant($tenantId);
        if ($usage->wouldExceedGuardLimit($plan->getMaxGuards())) {
            throw ApiException::conflict(sprintf(
                'You have reached the maximum number of guards (%d) for your %s plan.',
                $plan->getMaxGuards(), $plan->getName()
            ));
        }
    }

    private function createGuardUser(string $tenantId, array $data): User
    {
        if ($this->userRepo->emailExists($data['email'])) {
            throw ApiException::conflict('A user with this email already exists.');
        }
        $user = new User();
        $user->setEmail($data['email'])
            ->setPassword($data['password'] ?? 'Guard51@' . rand(1000, 9999))
            ->setFirstName($data['first_name'])
            ->setLastName($data['last_name'])
            ->setPhone($data['phone'])
            ->setRole(UserRole::GUARD)
            ->setTenantId($tenantId)
            ->setIsActive(true);
        $this->userRepo->save($user);
        return $user;
    }

    private function hydrateGuard(Guard $guard, array $data): void
    {
        if (isset($data['employee_number'])) $guard->setEmployeeNumber($data['employee_number']);
        if (isset($data['first_name'])) $guard->setFirstName($data['first_name']);
        if (isset($data['last_name'])) $guard->setLastName($data['last_name']);
        if (isset($data['phone'])) $guard->setPhone($data['phone']);
        if (isset($data['email'])) $guard->setEmail($data['email']);
        if (isset($data['date_of_birth'])) $guard->setDateOfBirth(new \DateTimeImmutable($data['date_of_birth']));
        if (isset($data['gender'])) $guard->setGender($data['gender']);
        if (isset($data['address'])) $guard->setAddress($data['address']);
        if (isset($data['city'])) $guard->setCity($data['city']);
        if (isset($data['state'])) $guard->setState($data['state']);
        if (isset($data['photo_url'])) $guard->setPhotoUrl($data['photo_url']);
        if (isset($data['emergency_contact_name'])) $guard->setEmergencyContactName($data['emergency_contact_name']);
        if (isset($data['emergency_contact_phone'])) $guard->setEmergencyContactPhone($data['emergency_contact_phone']);
        if (isset($data['hire_date'])) $guard->setHireDate(new \DateTimeImmutable($data['hire_date']));
        if (!empty($data['pay_type'])) $guard->setPayType(PayType::from($data['pay_type']));
        if (isset($data['pay_rate'])) $guard->setPayRate((float) $data['pay_rate']);
        if (isset($data['bank_name'])) $guard->setBankName($data['bank_name']);
        if (isset($data['bank_account_number'])) $guard->setBankAccountNumber($data['bank_account_number']);
        if (isset($data['bank_account_name'])) $guard->setBankAccountName($data['bank_account_name']);
        if (isset($data['notes'])) $guard->setNotes($data['notes']);
        if (!empty($data['status'])) $guard->setStatus(GuardStatus::from($data['status']));
    }

    private function generateEmployeeNumber(string $tenantId): string
    {
        // Find the highest existing GRD-XXXX number for this tenant
        $guards = $this->guardRepo->findByTenant($tenantId);
        $maxNum = 0;
        foreach ($guards as $g) {
            $emp = $g->getEmployeeNumber();
            if (preg_match('/GRD-(\d+)/', $emp, $m)) {
                $num = (int) $m[1];
                if ($num > $maxNum) $maxNum = $num;
            }
        }
        $next = $maxNum + 1;
        // Ensure uniqueness
        do {
            $number = 'GRD-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while ($this->guardRepo->findByEmployeeNumber($tenantId, $number));
        return $number;
    }
}
