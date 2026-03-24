<?php

declare(strict_types=1);

namespace Guard51\Service;

use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

final class QueueService
{
    public function __construct(
        private readonly RedisClient $redis,
        private readonly LoggerInterface $logger,
    ) {}

    public function dispatch(string $queue, array $payload): void
    {
        $message = json_encode([
            'id' => bin2hex(random_bytes(16)),
            'queue' => $queue,
            'payload' => $payload,
            'created_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);

        $this->redis->rpush("queue:{$queue}", [$message]);
        $this->logger->debug('Job dispatched.', ['queue' => $queue]);
    }

    public function consume(string $queue, int $timeout = 5): ?array
    {
        $result = $this->redis->blpop(["queue:{$queue}"], $timeout);
        if ($result === null) {
            return null;
        }
        return json_decode($result[1], true);
    }
}
