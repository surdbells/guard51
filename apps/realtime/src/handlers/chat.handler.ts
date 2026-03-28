import { Server, Socket } from "socket.io";
import { RedisService } from "../services/redis.service";

export function registerChatHandlers(io: Server, socket: Socket, redis: RedisService): void {
  const { tenantId, userId } = socket.data;

  socket.on("chat:join", (conversationId: string) => {
    socket.join(`chat:${conversationId}`);
    console.log(`[Chat] User ${userId} joined chat:${conversationId}`);
  });

  socket.on("chat:leave", (conversationId: string) => {
    socket.leave(`chat:${conversationId}`);
  });

  socket.on("chat:message", async (data: {
    conversation_id: string;
    content: string;
    message_type?: string;
    media_url?: string;
    lat?: number;
    lng?: number;
  }) => {
    const message = {
      id: `msg_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
      tenant_id: tenantId,
      conversation_id: data.conversation_id,
      sender_id: userId,
      content: data.content,
      message_type: data.message_type || "text",
      media_url: data.media_url || null,
      lat: data.lat || null,
      lng: data.lng || null,
      created_at: new Date().toISOString(),
    };

    // Broadcast to all participants in conversation
    io.to(`chat:${data.conversation_id}`).emit("chat:message", message);

    // Publish for API server persistence
    await redis.publisher.publish("chat-messages", JSON.stringify(message));

    console.log(`[Chat] ${userId} → ${data.conversation_id}: ${data.content.substring(0, 50)}`);
  });

  socket.on("chat:typing", (data: { conversation_id: string; is_typing: boolean }) => {
    socket.to(`chat:${data.conversation_id}`).emit("chat:typing", {
      user_id: userId,
      conversation_id: data.conversation_id,
      is_typing: data.is_typing ?? true,
    });
  });

  socket.on("chat:read", (data: { conversation_id: string }) => {
    socket.to(`chat:${data.conversation_id}`).emit("chat:read", {
      user_id: userId,
      conversation_id: data.conversation_id,
      read_at: new Date().toISOString(),
    });
  });
}
