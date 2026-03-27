import { Server, Socket } from "socket.io";
import { RedisService } from "../services/redis.service";

export function registerGpsHandlers(io: Server, socket: Socket, redis: RedisService): void {
  socket.on("gps:ping", async (data: { lat: number; lng: number; accuracy: number; battery?: number; speed?: number; heading?: number; is_moving?: boolean }) => {
    const { tenantId, userId } = socket.data;

    const locationData = {
      guard_id: userId,
      tenant_id: tenantId,
      lat: data.lat,
      lng: data.lng,
      accuracy: data.accuracy,
      battery_level: data.battery || null,
      speed: data.speed || null,
      heading: data.heading || null,
      is_moving: data.is_moving ?? true,
      source: "websocket",
      recorded_at: new Date().toISOString(),
    };

    // Buffer in Redis sorted set
    const key = `gps:guard:${userId}:locations`;
    await redis.client.zadd(key, Date.now(), JSON.stringify(locationData));
    await redis.client.expire(key, 86400);

    // Store latest position for fast lookup (live map)
    await redis.client.hset(`gps:latest:${tenantId}`, userId, JSON.stringify({
      lat: data.lat, lng: data.lng, accuracy: data.accuracy,
      speed: data.speed, battery_level: data.battery,
      is_moving: data.is_moving ?? true,
      updated_at: new Date().toISOString(),
    }));

    // Publish to Redis for PHP backend consumption (bulk insert worker)
    await redis.publisher.publish("guard-locations", JSON.stringify(locationData));

    // Broadcast to admin room for live map
    io.to(`admin:${tenantId}`).emit("tracking:update", locationData);
  });

  socket.on("gps:batch", async (locations: Array<{ lat: number; lng: number; accuracy: number; recorded_at: string; speed?: number; battery?: number }>) => {
    const { tenantId, userId } = socket.data;
    const key = `gps:guard:${userId}:locations`;

    for (const loc of locations) {
      const locationData = {
        guard_id: userId, tenant_id: tenantId,
        lat: loc.lat, lng: loc.lng, accuracy: loc.accuracy,
        speed: loc.speed || null, battery_level: loc.battery || null,
        source: "offline_sync", recorded_at: loc.recorded_at,
      };
      const timestamp = new Date(loc.recorded_at).getTime();
      await redis.client.zadd(key, timestamp, JSON.stringify(locationData));
      await redis.publisher.publish("guard-locations", JSON.stringify(locationData));
    }

    await redis.client.expire(key, 86400);

    // Update latest position + broadcast
    if (locations.length > 0) {
      const latest = locations[locations.length - 1];
      await redis.client.hset(`gps:latest:${tenantId}`, userId, JSON.stringify({
        lat: latest.lat, lng: latest.lng, accuracy: latest.accuracy,
        updated_at: new Date().toISOString(),
      }));
      io.to(`admin:${tenantId}`).emit("tracking:update", {
        guard_id: userId, tenant_id: tenantId, ...latest,
      });
    }

    socket.emit("gps:batch:ack", { count: locations.length });
  });
}
