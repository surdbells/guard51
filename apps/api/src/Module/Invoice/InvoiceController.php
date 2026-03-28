<?php
declare(strict_types=1);
namespace Guard51\Module\Invoice;

use Guard51\Helper\JsonResponse;
use Guard51\Service\InvoiceService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class InvoiceController
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    public function list(Request $request, Response $response): Response
    {
        $p = $request->getQueryParams();
        $invoices = $this->invoiceService->listInvoices($request->getAttribute('tenant_id'), $p['status'] ?? null, $p['client_id'] ?? null);
        return JsonResponse::success($response, ['invoices' => array_map(fn($i) => $i->toArray(), $invoices)]);
    }
    public function create(Request $request, Response $response): Response
    {
        $inv = $this->invoiceService->createInvoice($request->getAttribute('tenant_id'), (array) $request->getParsedBody(), $request->getAttribute('user_id'));
        return JsonResponse::success($response, $inv->toArray(), 201);
    }
    public function detail(Request $request, Response $response, array $args): Response
    {
        return JsonResponse::success($response, $this->invoiceService->getInvoiceDetail($args['id']));
    }
    public function recordPayment(Request $request, Response $response, array $args): Response
    {
        $p = $this->invoiceService->recordPayment($args['id'], (array) $request->getParsedBody(), $request->getAttribute('user_id'));
        return JsonResponse::success($response, $p->toArray(), 201);
    }
    public function send(Request $request, Response $response, array $args): Response
    {
        $inv = $this->invoiceService->sendInvoice($args['id']);
        return JsonResponse::success($response, $inv->toArray());
    }
    public function convertEstimate(Request $request, Response $response, array $args): Response
    {
        $inv = $this->invoiceService->convertEstimate($args['id']);
        return JsonResponse::success($response, $inv->toArray());
    }
    public function export(Request $request, Response $response, array $args): Response
    {
        return JsonResponse::success($response, $this->invoiceService->exportInvoiceHtml($args['id']));
    }
    public function overdue(Request $request, Response $response): Response
    {
        $invoices = $this->invoiceService->findOverdue($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['invoices' => array_map(fn($i) => $i->toArray(), $invoices)]);
    }
}
