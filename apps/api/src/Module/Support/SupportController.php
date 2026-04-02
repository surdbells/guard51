<?php
declare(strict_types=1);
namespace Guard51\Module\Support;

use Guard51\Helper\JsonResponse;
use Guard51\Service\SupportTicketService;
use Guard51\Repository\HelpArticleRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SupportController
{
    public function __construct(
        private readonly SupportTicketService $ticketService,
        private readonly HelpArticleRepository $helpRepo,
    ) {}

    // Tickets
    public function createTicket(Request $request, Response $response): Response
    {
        $t = $this->ticketService->create($request->getAttribute('tenant_id'), $request->getAttribute('user_id'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $t->toArray(), 201);
    }

    public function listTickets(Request $request, Response $response): Response
    {
        $p = $request->getQueryParams();
        $tickets = $this->ticketService->listByTenant($request->getAttribute('tenant_id'), $p['status'] ?? null);
        return JsonResponse::success($response, ['tickets' => array_map(fn($t) => $t->toArray(), $tickets)]);
    }

    public function resolveTicket(Request $request, Response $response): Response
    {
        return JsonResponse::success($response, $this->ticketService->resolve($request->getAttribute('id'))->toArray());
    }

    public function closeTicket(Request $request, Response $response): Response
    {
        return JsonResponse::success($response, $this->ticketService->close($request->getAttribute('id'))->toArray());
    }

    // Admin: all tickets across tenants
    public function adminListTickets(Request $request, Response $response): Response
    {
        $tickets = $this->ticketService->listAll($request->getQueryParams()['status'] ?? null);
        return JsonResponse::success($response, ['tickets' => array_map(fn($t) => $t->toArray(), $tickets), 'open_count' => $this->ticketService->countOpen()]);
    }

    public function assignTicket(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        return JsonResponse::success($response, $this->ticketService->assign($request->getAttribute('id'), $body['assigned_to'] ?? '')->toArray());
    }

    // Help articles
    public function listArticles(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams()['q'] ?? '';
        $articles = $q ? $this->helpRepo->search($q) : $this->helpRepo->findPublished();
        return JsonResponse::success($response, ['articles' => array_map(fn($a) => $a->toArray(), $articles)]);
    }

    public function getArticle(Request $request, Response $response): Response
    {
        $a = $this->helpRepo->findOrFail($request->getAttribute('id'));
        return JsonResponse::success($response, $a->toArray());
    }
}
