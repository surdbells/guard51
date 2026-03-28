<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\TwoFactorSecret;
use Guard51\Exception\ApiException;
use Guard51\Repository\TwoFactorSecretRepository;

final class TwoFactorService
{
    public function __construct(private readonly TwoFactorSecretRepository $repo) {}

    public function setup(string $userId): array
    {
        $secret = $this->generateSecret();
        $entity = new TwoFactorSecret();
        $entity->setUserId($userId)->setSecret($secret)
            ->setBackupCodes($this->generateBackupCodes());
        $this->repo->save($entity);

        $issuer = $_ENV['TOTP_ISSUER'] ?? 'Guard51';
        $otpauthUrl = "otpauth://totp/{$issuer}:{$userId}?secret={$secret}&issuer={$issuer}&digits=6&period=30";
        return ['secret' => $secret, 'otpauth_url' => $otpauthUrl, 'backup_codes' => $entity->getBackupCodes()];
    }

    public function verify(string $userId, string $code): bool
    {
        $entity = $this->repo->findOneBy(['userId' => $userId]);
        if (!$entity) throw ApiException::validation('2FA not set up.');
        if ($this->validateTOTP($entity->getSecret(), $code)) {
            if (!$entity->isEnabled()) { $entity->enable(); $this->repo->save($entity); }
            return true;
        }
        // Try backup codes
        if ($entity->useBackupCode($code)) { $this->repo->save($entity); return true; }
        return false;
    }

    public function disable(string $userId): void
    {
        $entity = $this->repo->findOneBy(['userId' => $userId]);
        if ($entity) { $entity->disable(); $this->repo->save($entity); }
    }

    public function isEnabled(string $userId): bool
    {
        $entity = $this->repo->findOneBy(['userId' => $userId]);
        return $entity?->isEnabled() ?? false;
    }

    public function getStatus(string $userId): array
    {
        $entity = $this->repo->findOneBy(['userId' => $userId]);
        return $entity ? $entity->toArray() : ['is_enabled' => false];
    }

    private function generateSecret(int $length = 32): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) $secret .= $chars[random_int(0, 31)];
        return $secret;
    }

    private function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) $codes[] = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        return $codes;
    }

    private function validateTOTP(string $secret, string $code, int $window = 1): bool
    {
        $timestamp = time();
        for ($i = -$window; $i <= $window; $i++) {
            $timeSlice = intdiv($timestamp, 30) + $i;
            if ($this->computeOTP($secret, $timeSlice) === $code) return true;
        }
        return false;
    }

    private function computeOTP(string $secret, int $timeSlice): string
    {
        $decoded = $this->base32Decode($secret);
        $time = pack('N*', 0, $timeSlice);
        $hmac = hash_hmac('sha1', $time, $decoded, true);
        $offset = ord($hmac[19]) & 0x0F;
        $value = ((ord($hmac[$offset]) & 0x7F) << 24) | ((ord($hmac[$offset+1]) & 0xFF) << 16) | ((ord($hmac[$offset+2]) & 0xFF) << 8) | (ord($hmac[$offset+3]) & 0xFF);
        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $input): string
    {
        $map = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $binary = '';
        foreach (str_split($input) as $char) $binary .= str_pad(decbin($map[$char] ?? 0), 5, '0', STR_PAD_LEFT);
        $result = '';
        for ($i = 0; $i + 8 <= strlen($binary); $i += 8) $result .= chr(bindec(substr($binary, $i, 8)));
        return $result;
    }
}
