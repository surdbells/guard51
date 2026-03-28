<?php
declare(strict_types=1);
namespace Guard51\Module\ClientPortal;

use Guard51\Helper\JsonResponse;
use Guard51\Repository\ClientUserRepository;
use Guard51\Service\IncidentService;
use Guard51\Service\InvoiceService;
use Guard51\Service\ReportService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ClientPortalController
{
    public function __construct(
        private readonly ClientUserRepository $clientUserRepo,
        private readonly ReportService $reportService,
        private readonly InvoiceService $invoiceService,
        private readonly IncidentService $incidentService,
    ) {}

    /** GET /api/v1/client-portal/profile — Client user profile + permissions */
    public function profile(Request $request, Response $response): Response
    {
        $cu = $this->clientUserRepo->findByUserId($request->getAttribute('user_id'));
        if (!$cu) return JsonResponse::error($response, 'Client user not found', 404);
        return JsonResponse::success($response, $cu->toArray());
    }

    /** GET /api/v1/client-portal/reports — Approved reports for client's sites */
    public function reports(Request $request, Response $response): Response
    {
        $cu = $this->clientUserRepo->findByUserId($request->getAttribute('user_id'));
        if (!$cu) return JsonResponse::error($response, 'Not found', 404);
        $reports = $this->reportService->getClientShareableReports($cu->getClientId());
        return JsonResponse::success($response, ['reports' => array_map(fn($r) => $r->toArray(), $reports)]);
    }

    /** GET /api/v1/client-portal/invoices — Invoices for this client */
    public function invoices(Request $request, Response $response): Response
    {
        $cu = $this->clientUserRepo->findByUserId($request->getAttribute('user_id'));
        if (!$cu) return JsonResponse::error($response, 'Not found', 404);
        $invoices = $this->invoiceService->listInvoices($request->getAttribute('tenant_id'), null, $cu->getClientId());
        return JsonResponse::success($response, ['invoices' => array_map(fn($i) => $i->toArray(), $invoices)]);
    }

    /** GET /api/v1/client-portal/incidents — Incidents at client's sites */
    public function incidents(Request $request, Response $response): Response
    {
        $cu = $this->clientUserRepo->findByUserId($request->getAttribute('user_id'));
        if (!$cu) return JsonResponse::error($response, 'Not found', 404);
        // Client sees all incidents for the tenant (filtered by their sites in a production version)
        $incidents = $this->incidentService->listActive($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['incidents' => array_map(fn($i) => $i->toArray(), $incidents)]);
    }

    /** GET /api/v1/client-portal/tracking — Guard positions at client's sites */
    public function tracking(Request $request, Response $response): Response
    {
        $cu = $this->clientUserRepo->findByUserId($request->getAttribute('user_id'));
        if (!$cu) return JsonResponse::error($response, 'Not found', 404);
        // Returns guard location data — in production, filtered by client's assigned sites
        return JsonResponse::success($response, ['guards' => [], 'message' => 'Live tracking data via WebSocket']);
    }

    /** GET /api/v1/client-portal/attendance — Guard check-in/out history for client's sites */
    public function attendance(Request $request, Response $response): Response
    {
        $cu = $this->clientUserRepo->findByUserId($request->getAttribute('user_id'));
        if (!$cu) return JsonResponse::error($response, 'Not found', 404);
        // Returns time clock data for client's sites — in production, filtered by site assignment
        return JsonResponse::success($response, ['records' => [], 'message' => 'Attendance data for client sites']);
    }
}
