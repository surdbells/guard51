<?php

declare(strict_types=1);

namespace Guard51\Module\AppDistribution;

use Guard51\Helper\JsonResponse;
use Guard51\Repository\TenantAppConfigRepository;
use Guard51\Service\AppDistributionService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class AppClientController
{
    public function __construct(
        private readonly AppDistributionService $appService,
        private readonly TenantAppConfigRepository $configRepo,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * GET /api/v1/apps/available — Available apps for current tenant
     */
    public function available(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $apps = $this->appService->getAvailableAppsForTenant($tenantId);

        return JsonResponse::success($response, [
            'apps' => $apps,
            'total' => count($apps),
        ]);
    }

    /**
     * GET /api/v1/apps/download/{releaseId} — Download app binary (signed URL)
     */
    public function download(Request $request, Response $response, array $args): Response
    {
        $releaseId = $args['releaseId'] ?? '';
        $params = $request->getQueryParams();
        $expires = (int) ($params['expires'] ?? 0);
        $signature = $params['sig'] ?? '';

        $tenantId = $request->getAttribute('tenant_id');
        $userId = $request->getAttribute('user_id');
        $ip = $this->getClientIp($request);

        $release = $this->appService->processDownload(
            releaseId: $releaseId,
            expires: $expires,
            signature: $signature,
            tenantId: $tenantId,
            userId: $userId,
            ipAddress: $ip,
            userAgent: $request->getHeaderLine('User-Agent') ?: null,
        );

        // Return the file URL (in production, this would redirect to S3/CDN)
        return JsonResponse::success($response, [
            'file_url' => $release->getFileUrl(),
            'file_hash' => $release->getFileHashSha256(),
            'file_size' => $release->getFileSizeBytes(),
            'version' => $release->getVersion(),
        ]);
    }

    /**
     * GET /api/v1/apps/check-update — Version check (called by installed apps, public)
     *
     * Query params: app, platform, current_version, tenant_id (optional)
     */
    public function checkUpdate(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $result = $this->appService->checkForUpdate(
            appKeyStr: $params['app'] ?? '',
            platformStr: $params['platform'] ?? '',
            currentVersion: $params['current_version'] ?? '0.0.0',
            tenantId: $params['tenant_id'] ?? null,
        );

        return JsonResponse::success($response, $result);
    }

    /**
     * POST /api/v1/apps/heartbeat — App reports version and device info (public)
     */
    public function heartbeat(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $this->appService->recordHeartbeat($body);

        return JsonResponse::success($response, ['status' => 'ok']);
    }

    /**
     * GET /api/v1/apps/config — Tenant app config for current tenant
     */
    public function getConfig(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $configs = $this->configRepo->findByTenant($tenantId);

        return JsonResponse::success($response, [
            'configs' => array_map(fn($c) => $c->toArray(), $configs),
        ]);
    }

    /**
     * PUT /api/v1/apps/config/{appKey} — Update tenant app config
     */
    public function updateConfig(Request $request, Response $response, array $args): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $appKey = $args['appKey'] ?? '';
        $body = (array) $request->getParsedBody();

        $config = $this->configRepo->findOrCreate($tenantId, $appKey);

        if (isset($body['auto_update'])) $config->setAutoUpdate((bool) $body['auto_update']);
        if (array_key_exists('pinned_version', $body)) $config->setPinnedVersion($body['pinned_version']);
        if (isset($body['settings'])) $config->setSettings($body['settings']);

        $this->configRepo->save($config);

        return JsonResponse::success($response, $config->toArray());
    }

    private function getClientIp(Request $request): string
    {
        $headers = ['X-Forwarded-For', 'X-Real-IP', 'CF-Connecting-IP'];
        foreach ($headers as $header) {
            $value = $request->getHeaderLine($header);
            if (!empty($value)) return trim(explode(',', $value)[0]);
        }
        return $request->getServerParams()['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
