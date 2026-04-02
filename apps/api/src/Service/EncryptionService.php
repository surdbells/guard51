<?php
declare(strict_types=1);
namespace Guard51\Service;

/**
 * AES-256-GCM encryption for PII columns.
 * Key stored in ENCRYPTION_KEY env var (base64-encoded 32 bytes).
 * Each ciphertext is prefixed with 12-byte nonce + 16-byte auth tag.
 */
final class EncryptionService
{
    private ?string $key;
    private const CIPHER = 'aes-256-gcm';
    private const NONCE_LEN = 12;
    private const TAG_LEN = 16;
    private const PREFIX = 'enc:';

    public function __construct()
    {
        $keyB64 = $_ENV['ENCRYPTION_KEY'] ?? '';
        if (empty($keyB64)) {
            $this->key = null; // Encryption disabled — no key configured
        } else {
            $this->key = base64_decode($keyB64);
            if (strlen($this->key) !== 32) {
                throw new \RuntimeException('ENCRYPTION_KEY must be 32 bytes (base64-encoded).');
            }
        }
    }

    public function isEnabled(): bool { return $this->key !== null; }

    public function encrypt(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '' || !$this->isEnabled()) return $plaintext;
        if ($this->isEncrypted($plaintext)) return $plaintext; // Already encrypted

        $nonce = random_bytes(self::NONCE_LEN);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $nonce, $tag, '', self::TAG_LEN);
        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed.');
        }
        return self::PREFIX . base64_encode($nonce . $tag . $ciphertext);
    }

    public function decrypt(?string $encrypted): ?string
    {
        if ($encrypted === null || $encrypted === '') return $encrypted;
        if (!$this->isEncrypted($encrypted)) return $encrypted; // Not encrypted (plaintext)
        if (!$this->isEnabled()) return $encrypted; // Can't decrypt without key

        $raw = base64_decode(substr($encrypted, strlen(self::PREFIX)));
        if ($raw === false || strlen($raw) < self::NONCE_LEN + self::TAG_LEN + 1) {
            return $encrypted; // Invalid format, return as-is
        }

        $nonce = substr($raw, 0, self::NONCE_LEN);
        $tag = substr($raw, self::NONCE_LEN, self::TAG_LEN);
        $ciphertext = substr($raw, self::NONCE_LEN + self::TAG_LEN);

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed — wrong key or corrupted data.');
        }
        return $plaintext;
    }

    public function isEncrypted(?string $value): bool
    {
        return $value !== null && str_starts_with($value, self::PREFIX);
    }

    /** Generate a new encryption key (run once, store in .env) */
    public static function generateKey(): string
    {
        return base64_encode(random_bytes(32));
    }
}
