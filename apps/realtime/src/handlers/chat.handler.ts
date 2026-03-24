import { Server, Socket } from "socket.io";
import { RedisService } from "../services/redis.service";

export function registerChatHandlers(io: Server, socket: Socket, redis: RedisService): void {
  socket.on("chat:join", (conversationId: string) => {
    socket.join(`chat:${conversationId}`);
  });

  socket.on("chat:leave", (conversationId: string) => {
    socket.leave(`chat:${conversationId}`);
  });

  socket.on("chat:message", async (data: { conversation_id: string; content: string; message_type?: string }) => {
    const { tenantId, userId } = socket.data;

    const message = {
      tenant_id: tenantId,
      conversation_id: data.conversation_id,
      sender_id: userId,
      content: data.content,
      message_type: data.message_type || "text",
      created_at: new Date().toISOString(),
    };

    // Publish for persistence and cross-process delivery
    await redis.publisher.publish("chat-messages", JSON.stringify(message));
  });

  socket.on("chat:typing", (data: { conversation_id: string }) => {
    socket.to(`chat:${data.conversation_id}`).emit("chat:typing", {
      user_id: socket.data.userId,
      conversation_id: data.conversation_id,
    });
  });
}
