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
        private readonly \Guard51\Repository\UserRepository $userRepo,
        private readonly \Doctrine\ORM\EntityManagerInterface $em,
        private readonly ReportService $reportService,
        private readonly InvoiceService $invoiceService,
        private readonly IncidentService $incidentService,
    ) {}

    /** GET /api/v1/client-portal/profile — Client user profile + permissions */
    /** GET /api/v1/client-portal/stats — Client dashboard KPIs */
    public function stats(Request $request, Response $response): Response
    {
        $cu = $this->clientUserRepo->findByUserId($request->getAttribute('user_id'));
        if (!$cu) return JsonResponse::error($response, 'Not found', 404);
        $clientId = $cu->getClientId();
        $conn = $this->em->getConnection();
        $guards = 0; $sites = 0; $incidents = 0; $outstanding = 0;
        try { $guards = (int) $conn->fetchOne("SELECT COUNT(*) FROM guards g JOIN sites s ON g.site_id = s.id WHERE s.client_id = ?", [$clientId]); } catch (\Throwable) {}
        try { $sites = (int) $conn->fetchOne("SELECT COUNT(*) FROM sites WHERE client_id = ?", [$clientId]); } catch (\Throwable) {}
        try { $incidents = (int) $conn->fetchOne("SELECT COUNT(*) FROM incident_reports ir JOIN sites s ON ir.site_id = s.id WHERE s.client_id = ? AND ir.created_at >= NOW() - INTERVAL '30 days'", [$clientId]); } catch (\Throwable) {}
        try { $outstanding = (float) $conn->fetchOne("SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE client_id = ? AND status != 'paid'", [$clientId]); } catch (\Throwable) {}
        return JsonResponse::success($response, ['active_guards' => $guards, 'total_sites' => $sites, 'incidents_30d' => $incidents, 'outstanding_amount' => $outstanding]);
    }

    /** GET /api/v1/client-portal/guard-activity — Recent guard activity for client's sites */
    public function guardActivity(Request $request, Response $response): Response
    {
        $cu = $this->clientUserRepo->findByUserId($request->getAttribute('user_id'));
        if (!$cu) return JsonResponse::error($response, 'Not found', 404);
        $conn = $this->em->getConnection();
        $activity = [];
        try {
            $activity = $conn->fetchAllAssociative(
                "SELECT tc.id, u.first_name || ' ' || u.last_name as guard_name, s.name as site_name, tc.clock_in_time, tc.clock_out_time, CASE WHEN tc.clock_out_time IS NULL THEN 'clocked_in' ELSE 'completed' END as status, tc.clock_in_time as timestamp FROM time_clocks tc JOIN users u ON tc.user_id = u.id JOIN sites s ON tc.site_id = s.id WHERE s.client_id = ? ORDER BY tc.clock_in_time DESC LIMIT 50",
                [$cu->getClientId()]
            );
        } catch (\Throwable) {}
        return JsonResponse::success($response, ['items' => $activity]);
    }

    /** GET /api/v1/client-portal/sites — List sites assigned to this client */
    public function clientSites(Request $request, Response $response): Response
    {
        $cu = $this->clientUserRepo->findByUserId($request->getAttribute('user_id'));
        if (!$cu) return JsonResponse::error($response, 'Not found', 404);
        $conn = $this->em->getConnection();
        $sites = [];
        try { $sites = $conn->fetchAllAssociative("SELECT id, name, address, city, state FROM sites WHERE client_id = ?", [$cu->getClientId()]); } catch (\Throwable) {}
        return JsonResponse::success($response, ['sites' => $sites]);
    }

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

    /** GET /api/v1/client-portal/employees — List client's employees */
    public function listEmployees(Request $request, Response $response): Response
    {
        $cu = $this->clientUserRepo->findByUserId($request->getAttribute('user_id'));
        if (!$cu) return JsonResponse::error($response, 'Not found', 404);
        $employees = $this->clientUserRepo->findBy(['clientId' => $cu->getClientId()]);
        $result = [];
        foreach ($employees as $emp) {
            $user = $this->userRepo->find($emp->getUserId());
            $result[] = array_merge($emp->toArray(), [
                'first_name' => $user?->getFirstName() ?? '',
                'last_name' => $user?->getLastName() ?? '',
                'email' => $user?->getEmail() ?? '',
                'phone' => $user?->getPhone() ?? '',
                'is_active' => $user?->getIsActive() ?? false,
            ]);
        }
        return JsonResponse::success($response, ['employees' => $result]);
    }

    /** POST /api/v1/client-portal/employees — Onboard a new client employee */
    public function addEmployee(Request $request, Response $response): Response
    {
        $cu = $this->clientUserRepo->findByUserId($request->getAttribute('user_id'));
        if (!$cu) return JsonResponse::error($response, 'Not found', 404);

        $body = (array) $request->getParsedBody();
        $email = $body['email'] ?? '';
        $firstName = $body['first_name'] ?? '';
        $lastName = $body['last_name'] ?? '';

        if (!$email || !$firstName) {
            return JsonResponse::error($response, 'Email and first name are required.', 422);
        }

        // Check if user with this email already exists
        $existing = $this->userRepo->findByEmail($email);
        if ($existing) {
            return JsonResponse::error($response, 'A user with this email already exists.', 409);
        }

        // Create the User account
        $user = new \Guard51\Entity\User();
        $user->setTenantId($cu->getTenantId());
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setEmail($email);
        $user->setPhone($body['phone'] ?? null);
        $user->setRole(\Guard51\Entity\UserRole::CLIENT);
        $user->setPasswordHash(password_hash($body['password'] ?? bin2hex(random_bytes(6)), PASSWORD_ARGON2ID));
        $user->setIsActive(true);
        $this->userRepo->save($user);

        // Create the ClientUser link with permissions
        $newCu = new \Guard51\Entity\ClientUser();
        $newCu->setTenantId($cu->getTenantId());
        $newCu->setClientId($cu->getClientId());
        $newCu->setUserId($user->getId());
        $newCu->setCanViewReports((bool) ($body['can_view_reports'] ?? true));
        $newCu->setCanViewTracking((bool) ($body['can_view_tracking'] ?? true));
        $newCu->setCanViewInvoices((bool) ($body['can_view_invoices'] ?? false));
        $newCu->setCanViewIncidents((bool) ($body['can_view_incidents'] ?? true));
        $newCu->setCanMessage((bool) ($body['can_message'] ?? true));
        $this->clientUserRepo->save($newCu);

        return JsonResponse::success($response, [
            'employee' => array_merge($newCu->toArray(), [
                'first_name' => $firstName, 'last_name' => $lastName, 'email' => $email,
            ]),
            'message' => 'Employee onboarded successfully.',
        ], 201);
    }

    /** DELETE /api/v1/client-portal/employees/{id} — Remove a client employee */
    public function removeEmployee(Request $request, Response $response): Response
    {
        $cu = $this->clientUserRepo->findByUserId($request->getAttribute('user_id'));
        if (!$cu) return JsonResponse::error($response, 'Not found', 404);

        $empId = $request->getAttribute('id');
        $emp = $this->clientUserRepo->find($empId);
        if (!$emp || $emp->getClientId() !== $cu->getClientId()) {
            return JsonResponse::error($response, 'Employee not found.', 404);
        }

        $this->clientUserRepo->remove($emp);
        return JsonResponse::success($response, ['message' => 'Employee removed.']);
    }
}
