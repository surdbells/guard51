import { Observable, ObservableArray } from '@nativescript/core';
import { ApiService } from '../../services/api.service';
import { SecureStorage } from '../../services/secure-storage.service';
export class ChatViewModel extends Observable {
      messages = new ObservableArray<any>([]);
  messageText = '';
  private conversationId = '';
  private userId = '';
  async init(): Promise<void> {
    const user = SecureStorage.get('user');
    if (user) this.userId = JSON.parse(user).id;
    try {
      const res = await ApiService.get('/chat/conversations');
      const convs = res.data?.conversations || [];
      if (convs.length) {
        this.conversationId = convs[0].id;
        const msgRes = await ApiService.get(`/chat/conversations/${this.conversationId}/messages`);
        const msgs = (msgRes.data?.messages || []).map((m: any) => ({
          ...m, senderName: m.sender_name || 'Unknown', sentAt: m.created_at?.slice(11, 16) || '',
          isMine: m.sender_id === this.userId,
        }));
        this.messages.splice(0, this.messages.length, ...msgs);
      }
    } catch (e) { console.error(e); }
  }
  async onSend(): Promise<void> {
    const text = this.get('messageText');
    if (!text?.trim()) return;
    try {
      await ApiService.post(`/chat/conversations/${this.conversationId}/messages`, { content: text });
      this.messages.push({ content: text, senderName: 'You', sentAt: new Date().toLocaleTimeString().slice(0, 5), isMine: true });
      this.set('messageText', '');
    } catch (e) { console.error(e); }
  }
}
