<?php

declare(strict_types=1);

namespace Guard51\Module\Guard;

use Guard51\Entity\GuardStatus;
use Guard51\Exception\ApiException;
use Guard51\Helper\JsonResponse;
use Guard51\Helper\HandlesFileUploads;
use Guard51\Service\FileStorageService;
use Guard51\Service\GuardService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class GuardController
{
    use HandlesFileUploads;

    public function __construct(
        private readonly GuardService $guardService,
        private readonly FileStorageService $storage,
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

        // Handle photo upload
        $photoUrl = $this->handleSingleUpload($request, $this->storage, 'photo', $tenantId, 'guards');
        if ($photoUrl) $body['photo_url'] = $photoUrl;

        $guard = $this->guardService->createGuard($tenantId, $body);
        return JsonResponse::success($response, $guard->toArray(), 201);
    }

    public function show(Request $request, Response $response): Response
    {
        $profile = $this->guardService->getGuardProfile($request->getAttribute('id'));
        return JsonResponse::success($response, $profile);
    }

    public function update(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        // Handle photo upload
        $tenantId = $request->getAttribute('tenant_id');
        $photoUrl = $this->handleSingleUpload($request, $this->storage, 'photo', $tenantId, 'guards');
        if ($photoUrl) $body['photo_url'] = $photoUrl;

        $guard = $this->guardService->updateGuard($request->getAttribute('id'), $body);
        return JsonResponse::success($response, $guard->toArray());
    }

    public function delete(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $this->guardService->deleteGuard($request->getAttribute('id'), $tenantId);
        return JsonResponse::success($response, ['message' => 'Guard terminated.']);
    }

    public function suspend(Request $request, Response $response): Response
    {
        $guard = $this->guardService->updateStatus($request->getAttribute('id'), GuardStatus::SUSPENDED);
        return JsonResponse::success($response, $guard->toArray());
    }

    public function activate(Request $request, Response $response): Response
    {
        $guard = $this->guardService->updateStatus($request->getAttribute('id'), GuardStatus::ACTIVE);
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

    public function assignSkill(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        if (empty($body['skill_id'])) throw ApiException::validation('Skill ID is required.');
        $sa = $this->guardService->assignSkill($request->getAttribute('id'), $body['skill_id'], $body['certified_at'] ?? null, $body['expires_at'] ?? null);
        return JsonResponse::success($response, $sa->toArray(), 201);
    }

    public function removeSkill(Request $request, Response $response): Response
    {
        $this->guardService->removeSkill($request->getAttribute('guardId'), $request->getAttribute('skillId'));
        return JsonResponse::success($response, ['message' => 'Skill removed.']);
    }

    // ── Documents ────────────────────────────────────

    public function listDocuments(Request $request, Response $response): Response
    {
        $docs = $this->guardService->getDocuments($request->getAttribute('id'));
        return JsonResponse::success($response, [
            'documents' => array_map(fn($d) => $d->toArray(), $docs),
        ]);
    }

    public function addDocument(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $doc = $this->guardService->addDocument($request->getAttribute('id'), $body);
        return JsonResponse::success($response, $doc->toArray(), 201);
    }

    public function verifyDocument(Request $request, Response $response): Response
    {
        $doc = $this->guardService->verifyDocument($request->getAttribute('docId'));
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

    /**
     * POST /api/v1/guards/bulk-import — Import guards from CSV
     */
    public function bulkImport(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $body = (array) $request->getParsedBody();
        $csvData = $body['csv_data'] ?? '';

        if (empty($csvData)) {
            throw ApiException::validation('CSV data is required.');
        }

        $lines = array_filter(explode("\n", trim($csvData)));
        if (count($lines) < 2) {
            throw ApiException::validation('CSV must have a header row and at least one data row.');
        }

        $headers = str_getcsv(array_shift($lines));
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

        $imported = 0;
        $errors = [];

        foreach ($lines as $i => $line) {
            $row = str_getcsv($line);
            if (count($row) !== count($headers)) {
                $errors[] = "Row " . ($i + 2) . ": column count mismatch.";
                continue;
            }

            $data = array_combine($headers, $row);

            try {
                $this->guardService->createGuard($tenantId, $data);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($i + 2) . ": " . $e->getMessage();
            }
        }

        return JsonResponse::success($response, [
            'imported' => $imported,
            'errors' => $errors,
            'total_rows' => count($lines),
        ]);
    }
}
