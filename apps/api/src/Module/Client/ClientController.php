<?php

declare(strict_types=1);

namespace Guard51\Module\Client;

use Guard51\Exception\ApiException;
use Guard51\Helper\JsonResponse;
use Guard51\Service\ClientService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class ClientController
{
    public function __construct(
        private readonly ClientService $clientService,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $params = $request->getQueryParams();
        $clients = $this->clientService->listClients($tenantId, $params['status'] ?? null, $params['search'] ?? null);
        return JsonResponse::success($response, [
            'clients' => array_map(fn($c) => $c->toArray(), $clients),
            'total' => count($clients),
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $body = (array) $request->getParsedBody();
        $client = $this->clientService->createClient($tenantId, $body);
        return JsonResponse::success($response, $client->toArray(), 201);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $data = $this->clientService->getClient($args['id']);
        return JsonResponse::success($response, $data);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        $client = $this->clientService->updateClient($args['id'], $body);
        return JsonResponse::success($response, $client->toArray());
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $this->clientService->deleteClient($args['id'], $tenantId);
        return JsonResponse::success($response, ['message' => 'Client deactivated.']);
    }

    // ── Contacts ─────────────────────────────────────

    public function listContacts(Request $request, Response $response, array $args): Response
    {
        $data = $this->clientService->getClient($args['id']);
        return JsonResponse::success($response, ['contacts' => $data['contacts']]);
    }

    public function addContact(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        $contact = $this->clientService->addContact($args['id'], $body);
        return JsonResponse::success($response, $contact->toArray(), 201);
    }

    public function updateContact(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        $contact = $this->clientService->updateContact($args['contactId'], $body);
        return JsonResponse::success($response, $contact->toArray());
    }

    public function deleteContact(Request $request, Response $response, array $args): Response
    {
        $this->clientService->deleteContact($args['contactId']);
        return JsonResponse::success($response, ['message' => 'Contact deleted.']);
    }
}
