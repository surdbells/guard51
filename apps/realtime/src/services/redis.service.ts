import Redis from "ioredis";

export interface RedisService {
  publisher: Redis;
  subscriber: Redis;
  client: Redis;
}

export function createRedisService(): RedisService {
  const host = process.env.REDIS_HOST || "redis";
  const port = parseInt(process.env.REDIS_PORT || "6379", 10);

  const config = { host, port, maxRetriesPerRequest: null };

  return {
    publisher: new Redis(config),
    subscriber: new Redis(config),
    client: new Redis(config),
  };
}
