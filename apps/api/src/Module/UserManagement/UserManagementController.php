<?php
declare(strict_types=1);
namespace Guard51\Module\UserManagement;

use Guard51\Helper\JsonResponse;
use Guard51\Service\UserManagementService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class UserManagementController
{
    public function __construct(private readonly UserManagementService $userMgmt) {}

    /** GET /users — List all tenant users */
    public function list(Request $request, Response $response): Response
    {
        $users = $this->userMgmt->listTenantUsers($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['users' => array_map(fn($u) => $u->toArray(), $users)]);
    }

    /** PUT /users/{id}/role — Change user role */
    public function changeRole(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $this->userMgmt->changeRole($request->getAttribute('id'), $body['role'] ?? '');
        return JsonResponse::success($response, ['message' => 'Role updated']);
    }

    /** GET /users/{id}/permissions — Get user permissions */
    public function permissions(Request $request, Response $response): Response
    {
        $perms = $this->userMgmt->getUserPermissions($request->getAttribute('id'));
        return JsonResponse::success($response, ['permissions' => array_map(fn($p) => $p->toArray(), $perms)]);
    }

    /** POST /users/{id}/permissions — Set module permission */
    public function setPermission(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $perm = $this->userMgmt->setModulePermission(
            $request->getAttribute('tenant_id'), $request->getAttribute('id'), $body['module_key'] ?? '', $body
        );
        return JsonResponse::success($response, $perm->toArray());
    }

    /** DELETE /users/{id}/permissions/{moduleKey} — Revoke module permission */
    public function revokePermission(Request $request, Response $response): Response
    {
        $this->userMgmt->revokeModulePermission($request->getAttribute('id'), $request->getAttribute('moduleKey'));
        return JsonResponse::success($response, ['revoked' => true]);
    }

    /** GET /users/modules — List available modules */
    public function modules(Request $request, Response $response): Response
    {
        return JsonResponse::success($response, ['modules' => $this->userMgmt->getAvailableModules()]);
    }
}
