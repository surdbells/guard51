<?php

declare(strict_types=1);

namespace Guard51\Module\AppDistribution;

use Guard51\Entity\AppKey;
use Guard51\Entity\AppPlatform;
use Guard51\Entity\ReleaseType;
use Guard51\Exception\ApiException;
use Guard51\Helper\JsonResponse;
use Guard51\Repository\AppReleaseRepository;
use Guard51\Service\AppDistributionService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class AppReleaseController
{
    public function __construct(
        private readonly AppDistributionService $appService,
        private readonly AppReleaseRepository $releaseRepo,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * GET /api/v1/admin/apps/dashboard — App distribution dashboard (super admin)
     */
    public function dashboard(Request $request, Response $response): Response
    {
        $data = $this->appService->getDashboard();
        return JsonResponse::success($response, $data);
    }

    /**
     * GET /api/v1/admin/apps/releases — List all releases (super admin)
     */
    public function listReleases(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $appKey = isset($params['app']) ? AppKey::tryFrom($params['app']) : null;
        $platform = isset($params['platform']) ? AppPlatform::tryFrom($params['platform']) : null;

        if ($appKey && $platform) {
            $releases = $this->releaseRepo->findByAppAndPlatform($appKey, $platform);
        } else {
            $releases = $this->releaseRepo->findBy([], ['uploadedAt' => 'DESC']);
        }

        return JsonResponse::success($response, [
            'releases' => array_map(fn($r) => $r->toArray(), $releases),
            'total' => count($releases),
        ]);
    }

    /**
     * POST /api/v1/admin/apps/releases — Upload new release (super admin)
     */
    public function upload(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        // Validate required fields
        $appKey = AppKey::tryFrom($body['app_key'] ?? '');
        $platform = AppPlatform::tryFrom($body['platform'] ?? '');
        $releaseType = ReleaseType::tryFrom($body['release_type'] ?? 'stable') ?? ReleaseType::STABLE;

        if (!$appKey) throw ApiException::validation('Valid app_key is required.');
        if (!$platform) throw ApiException::validation('Valid platform is required.');
        if (empty($body['version'])) throw ApiException::validation('Version is required.');
        if (empty($body['version_code'])) throw ApiException::validation('Version code is required.');
        if (empty($body['file_base64'])) throw ApiException::validation('File content (base64) is required.');

        $fileContents = base64_decode($body['file_base64'], true);
        if ($fileContents === false) {
            throw ApiException::validation('Invalid base64 file content.');
        }

        $release = $this->appService->uploadRelease(
            appKey: $appKey,
            platform: $platform,
            version: $body['version'],
            versionCode: (int) $body['version_code'],
            fileContents: $fileContents,
            uploadedBy: $userId,
            releaseType: $releaseType,
            releaseNotes: $body['release_notes'] ?? null,
            minApiVersion: $body['min_api_version'] ?? null,
            isMandatory: (bool) ($body['is_mandatory'] ?? false),
        );

        return JsonResponse::success($response, $release->toArray(), 201);
    }

    /**
     * GET /api/v1/admin/apps/releases/{id} — Get release detail (super admin)
     */
    public function show(Request $request, Response $response): Response
    {
        $release = $this->releaseRepo->findOrFail($request->getAttribute('id'));
        return JsonResponse::success($response, $release->toArray());
    }

    /**
     * PUT /api/v1/admin/apps/releases/{id} — Update release (notes, mandatory, active)
     */
    public function update(Request $request, Response $response): Response
    {
        $release = $this->releaseRepo->findOrFail($request->getAttribute('id'));
        $body = (array) $request->getParsedBody();

        if (isset($body['release_notes'])) $release->setReleaseNotes($body['release_notes']);
        if (isset($body['is_mandatory'])) $release->setIsMandatory((bool) $body['is_mandatory']);
        if (isset($body['is_active'])) {
            $body['is_active'] ? $release->reactivate() : $release->deactivate();
        }
        if (isset($body['min_api_version'])) $release->setMinApiVersion($body['min_api_version']);

        $this->releaseRepo->save($release);

        return JsonResponse::success($response, $release->toArray());
    }

    /**
     * DELETE /api/v1/admin/apps/releases/{id} — Deactivate release (super admin)
     */
    public function deactivate(Request $request, Response $response): Response
    {
        $release = $this->appService->deactivateRelease($request->getAttribute('id'));
        return JsonResponse::success($response, [
            'message' => 'Release deactivated.',
            'release' => $release->toArray(),
        ]);
    }

    /**
     * GET /api/v1/admin/apps/analytics — Download analytics (super admin)
     */
    public function analytics(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $since = isset($params['since'])
            ? new \DateTimeImmutable($params['since'])
            : new \DateTimeImmutable('-30 days');

        $data = $this->appService->getAnalytics($since);

        return JsonResponse::success($response, [
            'analytics' => $data,
            'since' => $since->format('Y-m-d'),
        ]);
    }
}
