<?php

declare(strict_types=1);

namespace Guard51\Service;

use Guard51\Entity\OnboardingStep;
use Guard51\Entity\PlatformBankAccount;
use Guard51\Entity\Tenant;
use Guard51\Entity\TenantBankAccount;
use Guard51\Exception\ApiException;
use Guard51\Repository\TenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class OnboardingService
{
    public function __construct(
        private readonly TenantRepository $tenantRepo,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Get onboarding status for a tenant: which steps are complete.
     */
    public function getStatus(string $tenantId): array
    {
        $tenant = $this->tenantRepo->findOrFail($tenantId);

        // Steps 1-2 are always complete after registration
        $completed = [
            OnboardingStep::COMPANY_INFO->value => true,
            OnboardingStep::ADMIN_ACCOUNT->value => true,
        ];

        // Check step 3: has a subscription (active or pending)
        $hasSub = (bool) $this->em->getConnection()->fetchOne(
            "SELECT 1 FROM subscriptions WHERE tenant_id = ? LIMIT 1",
            [$tenantId]
        );
        $completed[OnboardingStep::SELECT_PLAN->value] = $hasSub;
        $completed[OnboardingStep::PAYMENT_METHOD->value] = $hasSub;

        // Check step 5: bank account exists
        $hasBank = (bool) $this->em->getConnection()->fetchOne(
            "SELECT 1 FROM tenant_bank_accounts WHERE tenant_id = ? LIMIT 1",
            [$tenantId]
        );
        $completed[OnboardingStep::BANK_ACCOUNT->value] = $hasBank;

        // Check step 6: has at least one site (will be checked after Phase 1)
        $hasSite = (bool) $this->em->getConnection()->fetchOne(
            "SELECT 1 FROM information_schema.tables WHERE table_name = 'sites' LIMIT 1"
        );
        if ($hasSite) {
            $siteCount = (int) $this->em->getConnection()->fetchOne(
                "SELECT COUNT(*) FROM sites WHERE tenant_id = ?",
                [$tenantId]
            );
            $completed[OnboardingStep::FIRST_SITE->value] = $siteCount > 0;
        } else {
            $completed[OnboardingStep::FIRST_SITE->value] = false;
        }

        // Check step 7: has sent at least one invitation
        $hasInvites = (bool) $this->em->getConnection()->fetchOne(
            "SELECT 1 FROM tenant_invitations WHERE tenant_id = ? LIMIT 1",
            [$tenantId]
        );
        $completed[OnboardingStep::INVITE_GUARDS->value] = $hasInvites;

        $totalSteps = count(OnboardingStep::cases());
        $completedCount = count(array_filter($completed));
        $isComplete = $completedCount >= $totalSteps;

        return [
            'tenant_id' => $tenantId,
            'is_onboarded' => $tenant->isOnboarded(),
            'steps' => $this->formatSteps($completed),
            'completed_count' => $completedCount,
            'total_steps' => $totalSteps,
            'progress_percentage' => round(($completedCount / $totalSteps) * 100),
            'next_step' => $this->findNextStep($completed),
        ];
    }

    /**
     * Update tenant company info (Step 1 revision — can be updated post-registration).
     */
    public function updateCompanyInfo(string $tenantId, array $data): Tenant
    {
        $tenant = $this->tenantRepo->findOrFail($tenantId);

        if (isset($data['name'])) $tenant->setName($data['name']);
        if (isset($data['rc_number'])) $tenant->setRcNumber($data['rc_number']);
        if (isset($data['email'])) $tenant->setEmail($data['email']);
        if (isset($data['phone'])) $tenant->setPhone($data['phone']);
        if (isset($data['address'])) $tenant->setAddress($data['address']);
        if (isset($data['city'])) $tenant->setCity($data['city']);
        if (isset($data['state'])) $tenant->setState($data['state']);
        if (isset($data['country'])) $tenant->setCountry($data['country'] ?? 'Nigeria');

        $this->tenantRepo->save($tenant);
        return $tenant;
    }

    /**
     * Update tenant branding (logo, colors, custom domain).
     */
    public function updateBranding(string $tenantId, array $data): Tenant
    {
        $tenant = $this->tenantRepo->findOrFail($tenantId);

        if (isset($data['logo_url'])) {
            $tenant->setLogoUrl($data['logo_url']);
        }

        if (isset($data['custom_domain'])) {
            // Verify domain uniqueness
            $existing = $this->tenantRepo->findByCustomDomain($data['custom_domain']);
            if ($existing && $existing->getId() !== $tenantId) {
                throw ApiException::conflict('This custom domain is already in use by another organization.');
            }
            $tenant->setCustomDomain($data['custom_domain']);
        }

        // Merge branding colors into existing branding JSON
        $branding = $tenant->getBranding();
        if (isset($data['primary_color'])) $branding['primary_color'] = $data['primary_color'];
        if (isset($data['secondary_color'])) $branding['secondary_color'] = $data['secondary_color'];
        if (isset($data['accent_color'])) $branding['accent_color'] = $data['accent_color'];
        if (isset($data['font_family'])) $branding['font_family'] = $data['font_family'];
        $tenant->setBranding($branding);

        $this->tenantRepo->save($tenant);

        $this->logger->info('Tenant branding updated.', ['tenant_id' => $tenantId]);
        return $tenant;
    }

    /**
     * Save or update tenant bank account (Step 5).
     */
    public function saveBankAccount(string $tenantId, array $data): TenantBankAccount
    {
        if (empty($data['bank_name']) || empty($data['account_number']) || empty($data['account_name'])) {
            throw ApiException::validation('Bank name, account number, and account name are required.');
        }

        // Find existing primary or create new
        $existing = $this->em->getRepository(TenantBankAccount::class)
            ->findOneBy(['tenantId' => $tenantId, 'isPrimary' => true]);

        $account = $existing ?? new TenantBankAccount();
        if (!$existing) {
            $account->setTenantId($tenantId);
        }

        $account->setBankName($data['bank_name'])
            ->setAccountNumber($data['account_number'])
            ->setAccountName($data['account_name'])
            ->setBankCode($data['bank_code'] ?? null)
            ->setIsPrimary(true)
            ->setIsActive(true);

        $this->em->persist($account);
        $this->em->flush();

        $this->logger->info('Tenant bank account saved.', ['tenant_id' => $tenantId]);
        return $account;
    }

    /**
     * Get platform bank account details (shown to tenants for manual bank transfer).
     */
    public function getPlatformBankAccounts(): array
    {
        $accounts = $this->em->getRepository(PlatformBankAccount::class)
            ->findBy(['isActive' => true]);
        return array_map(fn($a) => $a->toArray(), $accounts);
    }

    /**
     * Mark tenant onboarding as complete.
     */
    public function completeOnboarding(string $tenantId): Tenant
    {
        $tenant = $this->tenantRepo->findOrFail($tenantId);

        if (!$tenant->isOnboarded()) {
            $tenant->markOnboarded();
            $this->tenantRepo->save($tenant);
            $this->logger->info('Tenant onboarding completed.', ['tenant_id' => $tenantId]);
        }

        return $tenant;
    }

    /**
     * Skip remaining onboarding steps (user can complete later).
     */
    public function skipOnboarding(string $tenantId): Tenant
    {
        return $this->completeOnboarding($tenantId);
    }

    private function formatSteps(array $completed): array
    {
        $steps = [];
        foreach (OnboardingStep::cases() as $step) {
            $steps[] = [
                'key' => $step->value,
                'number' => $step->stepNumber(),
                'label' => $step->label(),
                'completed' => $completed[$step->value] ?? false,
            ];
        }
        return $steps;
    }

    private function findNextStep(array $completed): ?string
    {
        foreach (OnboardingStep::cases() as $step) {
            if (!($completed[$step->value] ?? false)) {
                return $step->value;
            }
        }
        return null;
    }
}
