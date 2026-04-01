<?php
declare(strict_types=1);
namespace Guard51\Module\Notification;

use Guard51\Helper\JsonResponse;
use Guard51\Service\NotificationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class NotificationController
{
    public function __construct(private readonly NotificationService $notifService) {}

    public function list(Request $request, Response $response): Response
    {
        $notifs = $this->notifService->getUserNotifications($request->getAttribute('user_id'));
        $unread = $this->notifService->getUnreadCount($request->getAttribute('user_id'));
        return JsonResponse::success($response, ['notifications' => array_map(fn($n) => $n->toArray(), $notifs), 'unread_count' => $unread]);
    }
    public function markRead(Request $request, Response $response): Response
    {
        $n = $this->notifService->markRead($request->getAttribute('id'));
        return JsonResponse::success($response, $n->toArray());
    }
    public function markAllRead(Request $request, Response $response): Response
    {
        $count = $this->notifService->markAllRead($request->getAttribute('user_id'));
        return JsonResponse::success($response, ['marked' => $count]);
    }
    public function registerDevice(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $dt = $this->notifService->registerDevice($request->getAttribute('user_id'), $body['token'] ?? '', $body['platform'] ?? '');
        return JsonResponse::success($response, $dt->toArray(), 201);
    }
}
