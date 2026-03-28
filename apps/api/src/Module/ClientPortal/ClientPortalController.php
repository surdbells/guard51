<?php
declare(strict_types=1);
namespace Guard51\Module\ClientPortal;

use Guard51\Helper\JsonResponse;
use Guard51\Repository\ClientUserRepository;
use Guard51\Service\ReportService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ClientPortalController
{
    public function __construct(
        private readonly ClientUserRepository $clientUserRepo,
        private readonly ReportService $reportService,
    ) {}

    /** GET /api/v1/client-portal/profile — Get client user profile + permissions */
    public function profile(Request $request, Response $response): Response
    {
        $cu = $this->clientUserRepo->findByUserId($request->getAttribute('user_id'));
        if (!$cu) return JsonResponse::error($response, 'Client user not found', 404);
        return JsonResponse::success($response, $cu->toArray());
    }

    /** GET /api/v1/client-portal/reports — Get approved reports for client's sites */
    public function reports(Request $request, Response $response): Response
    {
        $cu = $this->clientUserRepo->findByUserId($request->getAttribute('user_id'));
        if (!$cu) return JsonResponse::error($response, 'Not found', 404);
        $reports = $this->reportService->getClientShareableReports($cu->getClientId());
        return JsonResponse::success($response, ['reports' => array_map(fn($r) => $r->toArray(), $reports)]);
    }
}
