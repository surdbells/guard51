<?php

declare(strict_types=1);

namespace Guard51\Module\Site;

use Guard51\Entity\SiteStatus;
use Guard51\Exception\ApiException;
use Guard51\Helper\JsonResponse;
use Guard51\Service\SiteService;
use Guard51\Service\ValidationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class SiteController
{
    public function __construct(
        private readonly SiteService $siteService,
        private readonly ValidationService $validator,
        private readonly LoggerInterface $logger,
    ) {}

    // ── Sites ────────────────────────────────────────

    /**
     * GET /api/v1/sites — List all sites for current tenant
     */
    public function index(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $params = $request->getQueryParams();

        $sites = $this->siteService->listSites(
            $tenantId,
            $params['status'] ?? null,
            $params['search'] ?? null,
        );

        return JsonResponse::success($response, [
            'sites' => array_map(fn($s) => $s->toArray(), $sites),
            'total' => count($sites),
        ]);
    }

    /**
     * GET /api/v1/sites/map — Sites with coordinates for map display
     */
    public function map(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $sites = $this->siteService->getSitesForMap($tenantId);

        return JsonResponse::success($response, ['sites' => $sites]);
    }

    /**
     * POST /api/v1/sites — Create a new site
     */
    public function create(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $userId = $request->getAttribute('user_id');
        $body = (array) $request->getParsedBody();

        if (empty($body['name'])) {
            throw ApiException::validation('Site name is required.');
        }

        $site = $this->siteService->createSite($tenantId, $body, $userId);

        return JsonResponse::success($response, $site->toArray(), 201);
    }

    /**
     * GET /api/v1/sites/{id} — Get site detail
     */
    public function show(Request $request, Response $response): Response
    {
        $site = $this->siteService->getSite($request->getAttribute('id'));
        $postOrders = $this->siteService->listPostOrders($request->getAttribute('id'), true);

        return JsonResponse::success($response, [
            'site' => $site->toArray(),
            'post_orders' => array_map(fn($po) => $po->toArray(), $postOrders),
            'post_order_count' => count($postOrders),
        ]);
    }

    /**
     * PUT /api/v1/sites/{id} — Update site
     */
    public function update(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $body = (array) $request->getParsedBody();

        $site = $this->siteService->updateSite($request->getAttribute('id'), $body, $userId);

        return JsonResponse::success($response, $site->toArray());
    }

    /**
     * DELETE /api/v1/sites/{id} — Deactivate site
     */
    public function delete(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $this->siteService->deleteSite($request->getAttribute('id'), $tenantId);

        return JsonResponse::success($response, ['message' => 'Site deactivated.']);
    }

    /**
     * POST /api/v1/sites/{id}/suspend — Suspend site
     */
    public function suspend(Request $request, Response $response): Response
    {
        $site = $this->siteService->updateStatus($request->getAttribute('id'), SiteStatus::SUSPENDED);
        return JsonResponse::success($response, $site->toArray());
    }

    /**
     * POST /api/v1/sites/{id}/activate — Reactivate site
     */
    public function activate(Request $request, Response $response): Response
    {
        $site = $this->siteService->updateStatus($request->getAttribute('id'), SiteStatus::ACTIVE);
        return JsonResponse::success($response, $site->toArray());
    }

    // ── Post Orders ──────────────────────────────────

    /**
     * GET /api/v1/sites/{siteId}/post-orders — List post orders for a site
     */
    public function listPostOrders(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $effectiveOnly = ($params['effective'] ?? 'false') === 'true';
        $orders = $this->siteService->listPostOrders($request->getAttribute('siteId'), $effectiveOnly);

        return JsonResponse::success($response, [
            'post_orders' => array_map(fn($po) => $po->toArray(), $orders),
            'total' => count($orders),
        ]);
    }

    /**
     * POST /api/v1/sites/{siteId}/post-orders — Create post order
     */
    public function createPostOrder(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $userId = $request->getAttribute('user_id');
        $body = (array) $request->getParsedBody();

        if (empty($body['title']) || empty($body['instructions'])) {
            throw ApiException::validation('Post order title and instructions are required.');
        }

        $order = $this->siteService->createPostOrder($tenantId, $request->getAttribute('siteId'), $body, $userId);

        return JsonResponse::success($response, $order->toArray(), 201);
    }

    /**
     * PUT /api/v1/post-orders/{id} — Update post order
     */
    public function updatePostOrder(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $body = (array) $request->getParsedBody();

        $order = $this->siteService->updatePostOrder($request->getAttribute('id'), $body, $userId);

        return JsonResponse::success($response, $order->toArray());
    }

    /**
     * DELETE /api/v1/post-orders/{id} — Deactivate post order
     */
    public function deletePostOrder(Request $request, Response $response): Response
    {
        $this->siteService->deletePostOrder($request->getAttribute('id'));
        return JsonResponse::success($response, ['message' => 'Post order deactivated.']);
    }
}
