<?php

declare(strict_types=1);

namespace Guard51\Module\Subscription;

use Guard51\Entity\SubscriptionPlan;
use Guard51\Entity\SubscriptionTier;
use Guard51\Exception\ApiException;
use Guard51\Helper\JsonResponse;
use Guard51\Repository\FeatureModuleRepository;
use Guard51\Repository\SubscriptionPlanRepository;
use Guard51\Service\ValidationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class PlanController
{
    public function __construct(
        private readonly SubscriptionPlanRepository $planRepo,
        private readonly FeatureModuleRepository $moduleRepo,
        private readonly ValidationService $validator,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * GET /api/v1/admin/plans — List all plans (super admin)
     */
    public function index(Request $request, Response $response): Response
    {
        $plans = $this->planRepo->findBy([], ['sortOrder' => 'ASC']);
        return JsonResponse::success($response, [
            'plans' => array_map(fn($p) => $p->toArray(), $plans),
        ]);
    }

    /**
     * POST /api/v1/admin/plans — Create custom plan (super admin)
     */
    public function create(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $this->validatePlanData($body);

        $plan = new SubscriptionPlan();
        $this->hydratePlan($plan, $body);
        $plan->setIsCustom(true);

        $this->planRepo->save($plan);

        $this->logger->info('Custom plan created.', ['plan' => $plan->getName()]);

        return JsonResponse::success($response, $plan->toArray(), 201);
    }

    /**
     * PUT /api/v1/admin/plans/{id} — Update plan (super admin)
     */
    public function update(Request $request, Response $response): Response
    {
        $plan = $this->planRepo->findOrFail($request->getAttribute('id'));
        $body = (array) $request->getParsedBody();
        $this->validatePlanData($body);

        $this->hydratePlan($plan, $body);
        $this->planRepo->save($plan);

        $this->logger->info('Plan updated.', ['plan_id' => $plan->getId()]);

        return JsonResponse::success($response, $plan->toArray());
    }

    /**
     * DELETE /api/v1/admin/plans/{id} — Deactivate plan (super admin)
     */
    public function delete(Request $request, Response $response): Response
    {
        $plan = $this->planRepo->findOrFail($request->getAttribute('id'));
        $plan->setIsActive(false);
        $this->planRepo->save($plan);

        return JsonResponse::success($response, ['message' => 'Plan deactivated.']);
    }

    /**
     * POST /api/v1/admin/plans/{id}/duplicate — Clone plan (super admin)
     */
    public function duplicate(Request $request, Response $response): Response
    {
        $source = $this->planRepo->findOrFail($request->getAttribute('id'));

        $clone = new SubscriptionPlan();
        $clone->setName($source->getName() . ' (Copy)')
            ->setDescription($source->getDescription())
            ->setTier($source->getTier())
            ->setMonthlyPrice($source->getMonthlyPrice())
            ->setAnnualPrice($source->getAnnualPrice())
            ->setMaxGuards($source->getMaxGuards())
            ->setMaxSites($source->getMaxSites())
            ->setMaxClients($source->getMaxClients())
            ->setMaxStaff($source->getMaxStaff())
            ->setIncludedModules($source->getIncludedModules())
            ->setTenantTypes($source->getTenantTypes())
            ->setFeatureFlags($source->getFeatureFlags())
            ->setTrialDays($source->getTrialDays())
            ->setIsCustom(true)
            ->setSortOrder($source->getSortOrder() + 1);

        $this->planRepo->save($clone);

        return JsonResponse::success($response, $clone->toArray(), 201);
    }

    /**
     * GET /api/v1/subscriptions/plans — Public: list available plans
     */
    public function publicPlans(Request $request, Response $response): Response
    {
        $plans = $this->planRepo->findPublicActive();
        return JsonResponse::success($response, [
            'plans' => array_map(fn($p) => $p->toArray(), $plans),
        ]);
    }

    private function validatePlanData(array $body): void
    {
        if (empty($body['name'])) {
            throw ApiException::validation('Plan name is required.');
        }
        if (!isset($body['monthly_price']) || (float) $body['monthly_price'] < 0) {
            throw ApiException::validation('Monthly price is required and must be non-negative.');
        }
        if (empty($body['tier']) || SubscriptionTier::tryFrom($body['tier']) === null) {
            throw ApiException::validation('Valid tier is required (all, starter, professional, business, enterprise).');
        }
    }

    private function hydratePlan(SubscriptionPlan $plan, array $body): void
    {
        $plan->setName($body['name'])
            ->setDescription($body['description'] ?? null)
            ->setTier(SubscriptionTier::from($body['tier']))
            ->setMonthlyPrice((string) $body['monthly_price'])
            ->setAnnualPrice(isset($body['annual_price']) ? (string) $body['annual_price'] : null)
            ->setMaxGuards((int) ($body['max_guards'] ?? 25))
            ->setMaxSites((int) ($body['max_sites'] ?? 5))
            ->setMaxClients((int) ($body['max_clients'] ?? 5))
            ->setMaxStaff(isset($body['max_staff']) ? (int) $body['max_staff'] : null)
            ->setIncludedModules($body['included_modules'] ?? [])
            ->setTenantTypes($body['tenant_types'] ?? ['private_security'])
            ->setFeatureFlags($body['feature_flags'] ?? [])
            ->setTrialDays((int) ($body['trial_days'] ?? 14))
            ->setSortOrder((int) ($body['sort_order'] ?? 0))
            ->setIsActive((bool) ($body['is_active'] ?? true));

        if (isset($body['private_tenant_id'])) {
            $plan->setPrivateTenantId($body['private_tenant_id']);
        }
    }
}
