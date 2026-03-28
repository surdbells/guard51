<?php
declare(strict_types=1);
namespace Guard51\Module\Security;

use Guard51\Helper\JsonResponse;
use Guard51\Service\AuditService;
use Guard51\Service\TwoFactorService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SecurityController
{
    public function __construct(private readonly TwoFactorService $twoFa, private readonly AuditService $audit) {}

    public function setup2FA(Request $request, Response $response): Response
    { return JsonResponse::success($response, $this->twoFa->setup($request->getAttribute('user_id')), 201); }

    public function verify2FA(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $ok = $this->twoFa->verify($request->getAttribute('user_id'), $body['code'] ?? '');
        return JsonResponse::success($response, ['verified' => $ok]);
    }

    public function disable2FA(Request $request, Response $response): Response
    { $this->twoFa->disable($request->getAttribute('user_id')); return JsonResponse::success($response, ['disabled' => true]); }

    public function status2FA(Request $request, Response $response): Response
    { return JsonResponse::success($response, $this->twoFa->getStatus($request->getAttribute('user_id'))); }

    public function auditLog(Request $request, Response $response): Response
    { $logs = $this->audit->getByTenant($request->getAttribute('tenant_id')); return JsonResponse::success($response, ['logs' => array_map(fn($l) => $l->toArray(), $logs)]); }
}
