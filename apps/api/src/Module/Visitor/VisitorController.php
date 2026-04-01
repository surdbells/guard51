<?php
declare(strict_types=1);
namespace Guard51\Module\Visitor;

use Guard51\Helper\JsonResponse;
use Guard51\Service\VisitorService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class VisitorController
{
    public function __construct(
        private readonly VisitorService $visitorService,
        private readonly \Guard51\Service\VisitorAppointmentService $appointmentService,
    ) {}

    public function checkIn(Request $request, Response $response): Response
    { return JsonResponse::success($response, $this->visitorService->checkIn($request->getAttribute('tenant_id'), (array) $request->getParsedBody(), $request->getAttribute('user_id'))->toArray(), 201); }

    public function checkOut(Request $request, Response $response, array $args): Response
    { return JsonResponse::success($response, $this->visitorService->checkOut($args['id'], $request->getAttribute('user_id'))->toArray()); }

    public function listBySite(Request $request, Response $response, array $args): Response
    { $p = $request->getQueryParams(); return JsonResponse::success($response, ['visitors' => array_map(fn($v) => $v->toArray(), $this->visitorService->listBySite($args['siteId'], $p['date'] ?? null))]); }

    public function listCheckedIn(Request $request, Response $response, array $args): Response
    { return JsonResponse::success($response, ['visitors' => array_map(fn($v) => $v->toArray(), $this->visitorService->listCheckedIn($args['siteId']))]); }

    public function search(Request $request, Response $response): Response
    { return JsonResponse::success($response, ['visitors' => array_map(fn($v) => $v->toArray(), $this->visitorService->search($request->getAttribute('tenant_id'), $request->getQueryParams()['q'] ?? ''))]); }

    // ── Appointments ──

    public function createAppointment(Request $request, Response $response): Response
    {
        $appt = $this->appointmentService->create($request->getAttribute('tenant_id'), (array) $request->getParsedBody(), $request->getAttribute('user_id'));
        return JsonResponse::success($response, $appt->toArray(), 201);
    }

    public function listAppointments(Request $request, Response $response): Response
    {
        $p = $request->getQueryParams();
        $appts = $this->appointmentService->listByTenant($request->getAttribute('tenant_id'), $p['status'] ?? null, $p['date'] ?? null);
        return JsonResponse::success($response, ['appointments' => array_map(fn($a) => $a->toArray(), $appts)]);
    }

    public function getAppointment(Request $request, Response $response, array $args): Response
    {
        $appt = $this->appointmentService->findById($args['id']);
        return JsonResponse::success($response, $appt->toArray());
    }

    public function verifyCode(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $appt = $this->appointmentService->verifyAccessCode($body['code'] ?? '', $request->getAttribute('tenant_id'));
        return JsonResponse::success($response, $appt->toArray());
    }

    public function appointmentCheckIn(Request $request, Response $response, array $args): Response
    {
        $appt = $this->appointmentService->checkIn($args['id'], $request->getAttribute('user_id'));
        return JsonResponse::success($response, $appt->toArray());
    }

    public function appointmentCheckOut(Request $request, Response $response, array $args): Response
    {
        $appt = $this->appointmentService->checkOut($args['id']);
        return JsonResponse::success($response, $appt->toArray());
    }

    public function cancelAppointment(Request $request, Response $response, array $args): Response
    {
        $appt = $this->appointmentService->cancel($args['id']);
        return JsonResponse::success($response, $appt->toArray());
    }
}
