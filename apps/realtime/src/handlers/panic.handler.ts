import { Server, Socket } from "socket.io";
import { RedisService } from "../services/redis.service";

export function registerPanicHandlers(io: Server, socket: Socket, redis: RedisService): void {
  socket.on("panic:trigger", async (data: { lat: number; lng: number; message?: string }) => {
    const { tenantId, userId } = socket.data;

    const alert = {
      tenant_id: tenantId,
      guard_id: userId,
      lat: data.lat,
      lng: data.lng,
      message: data.message || null,
      status: "triggered",
      triggered_at: new Date().toISOString(),
    };

    // Publish immediately — this is critical/emergency
    await redis.publisher.publish("panic-alerts", JSON.stringify(alert));

    // Also store in Redis for persistence until API processes it
    const key = `panic:active:${tenantId}:${userId}`;
    await redis.client.set(key, JSON.stringify(alert), "EX", 3600);

    // Confirm to the guard that alert was sent
    socket.emit("panic:confirmed", { status: "sent", timestamp: alert.triggered_at });
  });

  socket.on("panic:location_update", async (data: { lat: number; lng: number }) => {
    const { tenantId, userId } = socket.data;

    // During active panic, send GPS updates every 5 seconds
    await redis.publisher.publish("panic-alerts", JSON.stringify({
      tenant_id: tenantId,
      guard_id: userId,
      lat: data.lat,
      lng: data.lng,
      type: "location_update",
      timestamp: new Date().toISOString(),
    }));
  });
}
