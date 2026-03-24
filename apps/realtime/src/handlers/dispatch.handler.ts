import { Server, Socket } from "socket.io";
import { RedisService } from "../services/redis.service";

export function registerDispatchHandlers(io: Server, socket: Socket, redis: RedisService): void {
  socket.on("dispatch:acknowledge", async (data: { dispatch_id: string }) => {
    const { tenantId, userId } = socket.data;

    await redis.publisher.publish("dispatch-updates", JSON.stringify({
      tenant_id: tenantId,
      dispatch_id: data.dispatch_id,
      guard_id: userId,
      status: "acknowledged",
      timestamp: new Date().toISOString(),
    }));
  });

  socket.on("dispatch:arrived", async (data: { dispatch_id: string; lat: number; lng: number }) => {
    const { tenantId, userId } = socket.data;

    await redis.publisher.publish("dispatch-updates", JSON.stringify({
      tenant_id: tenantId,
      dispatch_id: data.dispatch_id,
      guard_id: userId,
      status: "on_scene",
      lat: data.lat,
      lng: data.lng,
      timestamp: new Date().toISOString(),
    }));
  });
}
