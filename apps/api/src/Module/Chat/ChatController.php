<?php
declare(strict_types=1);
namespace Guard51\Module\Chat;

use Guard51\Helper\JsonResponse;
use Guard51\Service\ChatService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ChatController
{
    public function __construct(private readonly ChatService $chatService) {}

    public function listConversations(Request $request, Response $response): Response
    {
        $convs = $this->chatService->getUserConversations($request->getAttribute('user_id'));
        return JsonResponse::success($response, ['conversations' => array_map(fn($c) => $c->toArray(), $convs)]);
    }
    public function createConversation(Request $request, Response $response): Response
    {
        $conv = $this->chatService->createConversation($request->getAttribute('tenant_id'), (array) $request->getParsedBody(), $request->getAttribute('user_id'));
        return JsonResponse::success($response, $conv->toArray(), 201);
    }
    public function messages(Request $request, Response $response, array $args): Response
    {
        $p = $request->getQueryParams();
        $msgs = $this->chatService->getMessages($args['id'], (int) ($p['limit'] ?? 50), (int) ($p['offset'] ?? 0));
        return JsonResponse::success($response, ['messages' => array_map(fn($m) => $m->toArray(), $msgs)]);
    }
    public function sendMessage(Request $request, Response $response, array $args): Response
    {
        $msg = $this->chatService->sendMessage($args['id'], $request->getAttribute('user_id'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $msg->toArray(), 201);
    }
    public function markRead(Request $request, Response $response, array $args): Response
    {
        $this->chatService->markRead($args['id'], $request->getAttribute('user_id'));
        return JsonResponse::success($response, ['ok' => true]);
    }
}
