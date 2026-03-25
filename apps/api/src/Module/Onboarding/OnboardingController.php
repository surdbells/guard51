<?php

declare(strict_types=1);

namespace Guard51\Module\Onboarding;

use Guard51\Helper\JsonResponse;
use Guard51\Service\OnboardingService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class OnboardingController
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * GET /api/v1/onboarding/status — Get onboarding progress
     */
    public function status(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $status = $this->onboardingService->getStatus($tenantId);
        return JsonResponse::success($response, $status);
    }

    /**
     * PUT /api/v1/onboarding/company — Update company info (step 1 revision)
     */
    public function updateCompany(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $body = (array) $request->getParsedBody();
        $tenant = $this->onboardingService->updateCompanyInfo($tenantId, $body);
        return JsonResponse::success($response, $tenant->toArray());
    }

    /**
     * PUT /api/v1/onboarding/branding — Update branding (logo, colors, domain)
     */
    public function updateBranding(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $body = (array) $request->getParsedBody();
        $tenant = $this->onboardingService->updateBranding($tenantId, $body);
        return JsonResponse::success($response, [
            'branding' => $tenant->getBranding(),
            'logo_url' => $tenant->getLogoUrl(),
            'custom_domain' => $tenant->getCustomDomain(),
        ]);
    }

    /**
     * POST /api/v1/onboarding/bank-account — Save bank account (step 5)
     */
    public function saveBankAccount(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $body = (array) $request->getParsedBody();
        $account = $this->onboardingService->saveBankAccount($tenantId, $body);
        return JsonResponse::success($response, $account->toArray());
    }

    /**
     * GET /api/v1/onboarding/platform-bank-accounts — Guard51 bank details for manual payment
     */
    public function platformBankAccounts(Request $request, Response $response): Response
    {
        $accounts = $this->onboardingService->getPlatformBankAccounts();
        return JsonResponse::success($response, ['accounts' => $accounts]);
    }

    /**
     * POST /api/v1/onboarding/complete — Mark onboarding done
     */
    public function complete(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $tenant = $this->onboardingService->completeOnboarding($tenantId);
        return JsonResponse::success($response, [
            'message' => 'Onboarding completed. Welcome to Guard51!',
            'tenant' => $tenant->toArray(),
        ]);
    }

    /**
     * POST /api/v1/onboarding/skip — Skip remaining steps
     */
    public function skip(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $tenant = $this->onboardingService->skipOnboarding($tenantId);
        return JsonResponse::success($response, [
            'message' => 'Onboarding skipped. You can complete setup later in Settings.',
            'tenant' => $tenant->toArray(),
        ]);
    }
}
