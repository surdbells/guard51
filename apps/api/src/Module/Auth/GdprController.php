<?php
declare(strict_types=1);
namespace Guard51\Module\Auth;

use Guard51\Helper\JsonResponse;
use Guard51\Repository\UserRepository;
use Guard51\Repository\GuardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GdprController
{
    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly EntityManagerInterface $em,
    ) {}

    /** GET /api/v1/account/export — Export all user data as JSON */
    public function exportData(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $tenantId = $request->getAttribute('tenant_id');
        $user = $this->userRepo->findOrFail($userId);
        $conn = $this->em->getConnection();

        $data = ['user' => $user->toArray(), 'exported_at' => date('c')];

        // Collect all user-related data
        $tables = [
            'guards' => "SELECT * FROM guards WHERE user_id = ? OR tenant_id = ?",
            'time_clocks' => "SELECT * FROM time_clocks WHERE user_id = ?",
            'incident_reports' => "SELECT * FROM incident_reports WHERE reported_by = ?",
            'notifications' => "SELECT * FROM notifications WHERE user_id = ?",
            'audit_logs' => "SELECT * FROM audit_logs WHERE user_id = ?",
            'chat_messages' => "SELECT * FROM chat_messages WHERE sender_id = ?",
        ];

        foreach ($tables as $key => $sql) {
            try {
                $params = str_contains($sql, 'tenant_id') ? [$userId, $tenantId] : [$userId];
                $data[$key] = $conn->fetchAllAssociative($sql, $params);
            } catch (\Throwable) {
                $data[$key] = [];
            }
        }

        return JsonResponse::success($response, $data);
    }

    /** POST /api/v1/account/delete — Anonymize user data (right to be forgotten) */
    public function deleteAccount(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $user = $this->userRepo->findOrFail($userId);
        $conn = $this->em->getConnection();

        // Anonymize PII
        $anon = 'DELETED_' . substr(md5($userId), 0, 8);
        $conn->executeStatement(
            "UPDATE users SET first_name = 'Deleted', last_name = 'User', email = ?, phone = NULL, password_hash = '', is_active = false WHERE id = ?",
            [$anon . '@deleted.guard51.com', $userId]
        );

        // Anonymize guard record if exists
        $conn->executeStatement(
            "UPDATE guards SET first_name = 'Deleted', last_name = 'User', phone = NULL, email = NULL, address = NULL, emergency_contact_name = NULL, emergency_contact_phone = NULL, bank_account_number = NULL, bank_account_name = NULL WHERE user_id = ?",
            [$userId]
        );

        return JsonResponse::success($response, [
            'message' => 'Account anonymized. Personal data has been removed.',
            'user_id' => $userId,
        ]);
    }
}
