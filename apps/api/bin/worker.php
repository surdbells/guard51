<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$queue = new \Guard51\Service\RedisQueueService();
$container = require __DIR__ . '/../config/container.php';

echo "[Worker] Started. Listening for jobs...\n";

$queues = ['email', 'sms', 'whatsapp', 'push', 'audit'];

while (true) {
    foreach ($queues as $queueName) {
        $job = $queue->pop($queueName, 1);
        if (!$job) continue;

        $job['attempts'] = ($job['attempts'] ?? 0) + 1;
        echo "[Worker] Processing {$queueName} job {$job['id']} (attempt {$job['attempts']})\n";

        try {
            switch ($queueName) {
                case 'email':
                    $mailer = $container->get(\Guard51\Service\ZeptoMailService::class);
                    $p = $job['payload'];
                    $mailer->send($p['to'], $p['to_name'] ?? '', $p['subject'], $p['body']);
                    break;
                case 'sms':
                case 'whatsapp':
                    $sms = $container->get(\Guard51\Service\TermiiService::class);
                    $p = $job['payload'];
                    $sms->sendSms($p['to'], $p['message']);
                    break;
                case 'push':
                    // FCM integration — Phase 2
                    echo "[Worker] Push notification: {$job['payload']['title']}\n";
                    break;
                default:
                    echo "[Worker] Unknown queue: {$queueName}\n";
            }
            echo "[Worker] Completed {$job['id']}\n";
        } catch (\Throwable $e) {
            echo "[Worker] Failed {$job['id']}: {$e->getMessage()}\n";
            if ($job['attempts'] < 3) {
                // Retry with delay
                sleep((int) (30 * pow(2, $job['attempts'] - 1)));
                $queue->push($queueName, $job['payload']);
            } else {
                $queue->fail($job, $e->getMessage());
            }
        }
    }
}
