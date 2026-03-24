export interface GuardLocation {
  guard_id: string;
  tenant_id: string;
  lat: number;
  lng: number;
  accuracy: number;
  battery_level?: number;
  speed?: number;
  recorded_at: string;
}

export interface ChatMessage {
  tenant_id: string;
  conversation_id: string;
  sender_id: string;
  content: string;
  message_type: "text" | "image" | "video" | "voice" | "file" | "location";
  created_at: string;
}

export interface PanicAlert {
  tenant_id: string;
  guard_id: string;
  lat: number;
  lng: number;
  message?: string;
  status: "triggered" | "acknowledged" | "responding" | "resolved";
  triggered_at: string;
}

export interface DispatchUpdate {
  tenant_id: string;
  dispatch_id: string;
  guard_id: string;
  status: "assigned" | "acknowledged" | "en_route" | "on_scene" | "completed";
  lat?: number;
  lng?: number;
  timestamp: string;
}
