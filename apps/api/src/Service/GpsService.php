<?php

declare(strict_types=1);

namespace Guard51\Service;

use Doctrine\ORM\EntityManagerInterface;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

final class GpsService
{
    public function __construct(
        private readonly RedisClient $redis,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Buffer a GPS ping in Redis (called by HTTP fallback endpoint).
     */
    public function bufferLocation(string $guardId, float $lat, float $lng, float $accuracy, string $recordedAt): void
    {
        $key = "gps:guard:{$guardId}:locations";
        $data = json_encode([
            'guard_id' => $guardId,
            'lat' => $lat,
            'lng' => $lng,
            'accuracy' => $accuracy,
            'recorded_at' => $recordedAt,
            'received_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);

        $timestamp = (new \DateTimeImmutable($recordedAt))->getTimestamp();
        $this->redis->zadd($key, [$data => $timestamp]);

        // Publish to real-time subscribers
        $this->redis->publish("tenant:guard-locations", $data);

        $this->logger->debug('GPS ping buffered.', ['guard_id' => $guardId, 'lat' => $lat, 'lng' => $lng]);
    }

    /**
     * Get latest known position for a guard from Redis buffer.
     */
    public function getLatestPosition(string $guardId): ?array
    {
        $key = "gps:guard:{$guardId}:locations";
        $result = $this->redis->zrevrange($key, 0, 0);

        if (empty($result)) {
            return null;
        }

        return json_decode($result[0], true);
    }

    /**
     * Flush buffered GPS pings to PostgreSQL (called by background worker).
     */
    public function flushToDatabase(string $guardId): int
    {
        $key = "gps:guard:{$guardId}:locations";
        $entries = $this->redis->zrange($key, 0, -1);

        if (empty($entries)) {
            return 0;
        }

        $count = 0;
        $conn = $this->em->getConnection();

        foreach ($entries as $entry) {
            $data = json_decode($entry, true);
            $conn->executeStatement(
                'INSERT INTO guard_locations (id, tenant_id, guard_id, latitude, longitude, accuracy, source, recorded_at, received_at) 
                 VALUES (gen_random_uuid(), (SELECT tenant_id FROM guards WHERE id = :guard_id), :guard_id, :lat, :lng, :accuracy, :source, :recorded_at, :received_at)',
                [
                    'guard_id' => $data['guard_id'],
                    'lat' => $data['lat'],
                    'lng' => $data['lng'],
                    'accuracy' => $data['accuracy'],
                    'source' => 'http_poll',
                    'recorded_at' => $data['recorded_at'],
                    'received_at' => $data['received_at'],
                ]
            );
            $count++;
        }

        // Clear processed entries
        $this->redis->del([$key]);

        $this->logger->info('GPS pings flushed to database.', ['guard_id' => $guardId, 'count' => $count]);
        return $count;
    }
}
