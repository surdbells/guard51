<?php
declare(strict_types=1);
namespace Guard51\Module\License;

use Guard51\Helper\JsonResponse;
use Guard51\Service\LicenseService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class LicenseController
{
    public function __construct(private readonly LicenseService $licenseService) {}

    public function create(Request $request, Response $response): Response
    { return JsonResponse::success($response, $this->licenseService->create($request->getAttribute('tenant_id'), (array) $request->getParsedBody())->toArray(), 201); }

    public function byGuard(Request $request, Response $response, array $args): Response
    { return JsonResponse::success($response, ['licenses' => array_map(fn($l) => $l->toArray(), $this->licenseService->listByGuard($args['guardId']))]); }

    public function expiringSoon(Request $request, Response $response): Response
    { return JsonResponse::success($response, ['licenses' => array_map(fn($l) => $l->toArray(), $this->licenseService->findExpiringSoon($request->getAttribute('tenant_id')))]); }

    public function expired(Request $request, Response $response): Response
    { return JsonResponse::success($response, ['licenses' => array_map(fn($l) => $l->toArray(), $this->licenseService->findExpired($request->getAttribute('tenant_id')))]); }
}
