<?php

declare(strict_types=1);

namespace Guard51\Module\Guard;

use Guard51\Entity\GuardStatus;
use Guard51\Exception\ApiException;
use Guard51\Helper\JsonResponse;
use Guard51\Service\GuardService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class GuardController
{
    public function __construct(
        private readonly GuardService $guardService,
        private readonly LoggerInterface $logger,
    ) {}

    // ── Guards ───────────────────────────────────────

    public function index(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $params = $request->getQueryParams();
        $guards = $this->guardService->listGuards($tenantId, $params['status'] ?? null, $params['search'] ?? null);
        return JsonResponse::success($response, [
            'guards' => array_map(fn($g) => $g->toArray(), $guards),
            'total' => count($guards),
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $body = (array) $request->getParsedBody();
        $guard = $this->guardService->createGuard($tenantId, $body);
        return JsonResponse::success($response, $guard->toArray(), 201);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $profile = $this->guardService->getGuardProfile($args['id']);
        return JsonResponse::success($response, $profile);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        $guard = $this->guardService->updateGuard($args['id'], $body);
        return JsonResponse::success($response, $guard->toArray());
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $this->guardService->deleteGuard($args['id'], $tenantId);
        return JsonResponse::success($response, ['message' => 'Guard terminated.']);
    }

    public function suspend(Request $request, Response $response, array $args): Response
    {
        $guard = $this->guardService->updateStatus($args['id'], GuardStatus::SUSPENDED);
        return JsonResponse::success($response, $guard->toArray());
    }

    public function activate(Request $request, Response $response, array $args): Response
    {
        $guard = $this->guardService->updateStatus($args['id'], GuardStatus::ACTIVE);
        return JsonResponse::success($response, $guard->toArray());
    }

    // ── Skills ───────────────────────────────────────

    public function listSkills(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $skills = $this->guardService->listSkills($tenantId);
        return JsonResponse::success($response, [
            'skills' => array_map(fn($s) => $s->toArray(), $skills),
        ]);
    }

    public function createSkill(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $body = (array) $request->getParsedBody();
        if (empty($body['name'])) throw ApiException::validation('Skill name is required.');
        $skill = $this->guardService->createSkill($tenantId, $body['name'], $body['description'] ?? null);
        return JsonResponse::success($response, $skill->toArray(), 201);
    }

    public function assignSkill(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        if (empty($body['skill_id'])) throw ApiException::validation('Skill ID is required.');
        $sa = $this->guardService->assignSkill($args['id'], $body['skill_id'], $body['certified_at'] ?? null, $body['expires_at'] ?? null);
        return JsonResponse::success($response, $sa->toArray(), 201);
    }

    public function removeSkill(Request $request, Response $response, array $args): Response
    {
        $this->guardService->removeSkill($args['guardId'], $args['skillId']);
        return JsonResponse::success($response, ['message' => 'Skill removed.']);
    }

    // ── Documents ────────────────────────────────────

    public function listDocuments(Request $request, Response $response, array $args): Response
    {
        $docs = $this->guardService->getDocuments($args['id']);
        return JsonResponse::success($response, [
            'documents' => array_map(fn($d) => $d->toArray(), $docs),
        ]);
    }

    public function addDocument(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        $doc = $this->guardService->addDocument($args['id'], $body);
        return JsonResponse::success($response, $doc->toArray(), 201);
    }

    public function verifyDocument(Request $request, Response $response, array $args): Response
    {
        $doc = $this->guardService->verifyDocument($args['docId']);
        return JsonResponse::success($response, $doc->toArray());
    }

    public function expiringDocuments(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $params = $request->getQueryParams();
        $days = (int) ($params['days'] ?? 30);
        $docs = $this->guardService->getExpiringDocuments($tenantId, $days);
        return JsonResponse::success($response, ['documents' => $docs, 'days_threshold' => $days]);
    }
}
