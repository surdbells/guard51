import { createServer } from "http";
import { Server } from "socket.io";
import { createRedisService } from "./services/redis.service";
import { AuthService } from "./services/auth.service";
import { registerGpsHandlers } from "./handlers/gps.handler";
import { registerChatHandlers } from "./handlers/chat.handler";
import { registerDispatchHandlers } from "./handlers/dispatch.handler";
import { registerPanicHandlers } from "./handlers/panic.handler";

const PORT = parseInt(process.env.PORT || "3001", 10);
const JWT_SECRET = process.env.JWT_SECRET || "change_me";

async function main() {
  const httpServer = createServer((req, res) => {
    if (req.url === "/health") {
      res.writeHead(200, { "Content-Type": "application/json" });
      res.end(JSON.stringify({ status: "ok", service: "guard51-realtime" }));
      return;
    }
    res.writeHead(404);
    res.end();
  });

  const io = new Server(httpServer, {
    cors: {
      origin: ["http://localhost:4200", "http://localhost:8080"],
      methods: ["GET", "POST"],
      credentials: true,
    },
    pingInterval: 10000,
    pingTimeout: 5000,
  });

  const redis = createRedisService();
  const authService = new AuthService(JWT_SECRET);

  // Authentication middleware
  io.use((socket, next) => {
    const token = socket.handshake.auth?.token || socket.handshake.headers?.authorization?.replace("Bearer ", "");
    if (!token) {
      return next(new Error("Authentication required"));
    }

    const payload = authService.verifyToken(token);
    if (!payload) {
      return next(new Error("Invalid token"));
    }

    socket.data.userId = payload.sub;
    socket.data.tenantId = payload.tenant_id;
    socket.data.role = payload.role;
    next();
  });

  io.on("connection", (socket) => {
    const { tenantId, userId, role } = socket.data;
    console.log(`[Connected] User: ${userId}, Tenant: ${tenantId}, Role: ${role}`);

    // Join tenant room
    socket.join(`tenant:${tenantId}`);

    // Register handlers
    registerGpsHandlers(io, socket, redis);
    registerChatHandlers(io, socket, redis);
    registerDispatchHandlers(io, socket, redis);
    registerPanicHandlers(io, socket, redis);

    socket.on("disconnect", (reason) => {
      console.log(`[Disconnected] User: ${userId}, Reason: ${reason}`);
    });
  });

  // Subscribe to Redis for cross-process events
  const subscriber = redis.subscriber;
  await subscriber.subscribe("guard-locations", "panic-alerts", "dispatch-updates", "chat-messages");

  subscriber.on("message", (channel: string, message: string) => {
    try {
      const data = JSON.parse(message);
      const tenantId = data.tenant_id;

      switch (channel) {
        case "guard-locations":
          io.to(`tenant:${tenantId}`).emit("guard:location", data);
          break;
        case "panic-alerts":
          io.to(`tenant:${tenantId}`).emit("panic:alert", data);
          break;
        case "dispatch-updates":
          io.to(`tenant:${tenantId}`).emit("dispatch:update", data);
          break;
        case "chat-messages":
          const conversationRoom = `chat:${data.conversation_id}`;
          io.to(conversationRoom).emit("chat:message", data);
          break;
      }
    } catch (err) {
      console.error(`Error processing Redis message on ${channel}:`, err);
    }
  });

  httpServer.listen(PORT, () => {
    console.log(`Guard51 Realtime Server running on port ${PORT}`);
  });
}

main().catch(console.error);
