<?php
declare(strict_types=1);
namespace Guard51\Service;

/**
 * Redis-backed job queue for async processing.
 * Jobs: email, sms, whatsapp, push_notification, audit_log.
 */
final class RedisQueueService
{
    private \Redis $redis;

    public function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect(
            $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            (int) ($_ENV['REDIS_PORT'] ?? 6379)
        );
        $prefix = $_ENV['REDIS_PREFIX'] ?? 'g51:';
        $this->redis->setOption(\Redis::OPT_PREFIX, $prefix);
    }

    /** Push a job to the queue */
    public function push(string $queue, array $payload): void
    {
        $job = json_encode([
            'id' => bin2hex(random_bytes(8)),
            'queue' => $queue,
            'payload' => $payload,
            'attempts' => 0,
            'created_at' => date('c'),
        ]);
        $this->redis->lPush("queue:{$queue}", $job);
    }

    /** Pop a job from the queue (blocking, timeout in seconds) */
    public function pop(string $queue, int $timeout = 5): ?array
    {
        $result = $this->redis->brPop(["queue:{$queue}"], $timeout);
        if (!$result) return null;
        return json_decode($result[1], true);
    }

    /** Push to failed queue */
    public function fail(array $job, string $error): void
    {
        $job['error'] = $error;
        $job['failed_at'] = date('c');
        $this->redis->lPush('queue:failed', json_encode($job));
    }

    /** Get failed job count */
    public function failedCount(): int
    {
        return $this->redis->lLen('queue:failed') ?: 0;
    }

    /** Get queue length */
    public function queueLength(string $queue): int
    {
        return $this->redis->lLen("queue:{$queue}") ?: 0;
    }

    /** Store value with TTL (for JWT blacklist, rate limiting) */
    public function set(string $key, string $value, int $ttlSeconds): void
    {
        $this->redis->setex($key, $ttlSeconds, $value);
    }

    public function get(string $key): ?string
    {
        $val = $this->redis->get($key);
        return $val === false ? null : $val;
    }

    public function exists(string $key): bool
    {
        return (bool) $this->redis->exists($key);
    }
}
