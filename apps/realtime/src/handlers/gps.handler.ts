import { Server, Socket } from "socket.io";
import { RedisService } from "../services/redis.service";

export function registerGpsHandlers(io: Server, socket: Socket, redis: RedisService): void {
  socket.on("gps:ping", async (data: { lat: number; lng: number; accuracy: number; battery?: number; speed?: number }) => {
    const { tenantId, userId } = socket.data;

    const locationData = {
      guard_id: userId,
      tenant_id: tenantId,
      lat: data.lat,
      lng: data.lng,
      accuracy: data.accuracy,
      battery_level: data.battery || null,
      speed: data.speed || null,
      recorded_at: new Date().toISOString(),
    };

    // Buffer in Redis sorted set
    const key = `gps:guard:${userId}:locations`;
    await redis.client.zadd(key, Date.now(), JSON.stringify(locationData));

    // Set TTL to auto-cleanup old data (24 hours)
    await redis.client.expire(key, 86400);

    // Publish to tenant subscribers (live map)
    await redis.publisher.publish("guard-locations", JSON.stringify(locationData));
  });

  socket.on("gps:batch", async (locations: Array<{ lat: number; lng: number; accuracy: number; recorded_at: string }>) => {
    const { tenantId, userId } = socket.data;
    const key = `gps:guard:${userId}:locations`;

    for (const loc of locations) {
      const locationData = {
        guard_id: userId,
        tenant_id: tenantId,
        lat: loc.lat,
        lng: loc.lng,
        accuracy: loc.accuracy,
        recorded_at: loc.recorded_at,
      };
      const timestamp = new Date(loc.recorded_at).getTime();
      await redis.client.zadd(key, timestamp, JSON.stringify(locationData));
    }

    await redis.client.expire(key, 86400);

    // Publish latest position only
    if (locations.length > 0) {
      const latest = locations[locations.length - 1];
      await redis.publisher.publish("guard-locations", JSON.stringify({
        guard_id: userId,
        tenant_id: tenantId,
        ...latest,
      }));
    }
  });
}
