<?php

declare(strict_types=1);

namespace Guard51\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Guard51\Entity\User;
use Psr\Log\LoggerInterface;

final class JwtService
{
    public function __construct(
        private readonly array $config,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Generate an access token for the given user.
     * Contains: sub (user_id), tenant_id, role, email.
     */
    public function generateAccessToken(User $user): string
    {
        $now = time();
        $payload = [
            'iss' => $this->config['issuer'],
            'sub' => $user->getId(),
            'tenant_id' => $user->getTenantId(),
            'role' => $user->getRole()->value,
            'email' => $user->getEmail(),
            'iat' => $now,
            'exp' => $now + $this->config['access_ttl'],
            'type' => 'access',
        ];

        return JWT::encode($payload, $this->config['secret'], $this->config['algorithm']);
    }

    /**
     * Validate and decode an access token.
     * Returns the decoded payload as array, or null if invalid/expired.
     */
    public function validateAccessToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->config['secret'], $this->config['algorithm']));
            $payload = (array) $decoded;

            if (($payload['type'] ?? '') !== 'access') {
                $this->logger->warning('Token is not an access token.', ['type' => $payload['type'] ?? 'unknown']);
                return null;
            }

            return $payload;
        } catch (ExpiredException $e) {
            $this->logger->debug('Access token expired.', ['error' => $e->getMessage()]);
            return null;
        } catch (\Exception $e) {
            $this->logger->warning('Invalid access token.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get the access token TTL in seconds.
     */
    public function getAccessTtl(): int
    {
        return $this->config['access_ttl'];
    }

    /**
     * Get the refresh token TTL in seconds.
     */
    public function getRefreshTtl(): int
    {
        return $this->config['refresh_ttl'];
    }

    /**
     * Extract token from Authorization header.
     * Supports: "Bearer <token>"
     */
    public static function extractFromHeader(string $header): ?string
    {
        if (str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);
            return $token !== '' ? $token : null;
        }
        return null;
    }
}
