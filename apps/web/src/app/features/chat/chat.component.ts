import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, DatePipe } from '@angular/common';
import { LucideAngularModule, MessageSquare, Plus, Send, Image, MapPin, Mic, Users, Hash } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-chat',
  standalone: true,
  imports: [FormsModule, NgClass, DatePipe, LucideAngularModule, PageHeaderComponent, ModalComponent],
  template: `
    <g51-page-header title="Messenger" subtitle="Team communication and site channels">
      <button (click)="showCreate.set(true)" class="btn-primary flex items-center gap-2">
        <lucide-icon [img]="PlusIcon" [size]="16" /> New Chat
      </button>
    </g51-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4" style="min-height: 500px;">
      <!-- Conversation list -->
      <div class="card p-0 overflow-hidden">
        <div class="p-3 border-b" [style.borderColor]="'var(--border-default)'">
          <input type="text" placeholder="Search conversations..." class="input-base w-full text-sm py-1.5" />
        </div>
        <div class="overflow-y-auto" style="max-height: 480px;">
          @for (conv of conversations(); track conv.id) {
            <div class="px-3 py-3 cursor-pointer transition-colors border-b"
              [style.borderColor]="'var(--border-default)'"
              [style.background]="selectedConv()?.id === conv.id ? 'var(--surface-muted)' : 'transparent'"
              (click)="selectConversation(conv)">
              <div class="flex items-center gap-2 mb-0.5">
                @if (conv.type === 'site_channel') {
                  <lucide-icon [img]="HashIcon" [size]="14" [style.color]="'var(--color-brand-500)'" />
                } @else if (conv.type === 'group') {
                  <lucide-icon [img]="UsersIcon" [size]="14" [style.color]="'var(--text-tertiary)'" />
                } @else {
                  <lucide-icon [img]="MessageSquareIcon" [size]="14" [style.color]="'var(--text-tertiary)'" />
                }
                <span class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ conv.name || 'Direct Message' }}</span>
              </div>
              <p class="text-[10px] ml-5" [style.color]="'var(--text-tertiary)'">{{ conv.last_message_at | date:'shortTime' }}</p>
              @if (conv.unread_count > 0) {
                <span class="ml-5 inline-flex items-center justify-center h-4 min-w-[16px] px-1 rounded-full text-[9px] font-bold text-white"
                  [style.background]="'var(--color-brand-500)'">{{ conv.unread_count }}</span>
              }
            </div>
          } @empty {
            <p class="text-sm py-8 text-center" [style.color]="'var(--text-tertiary)'">No conversations yet</p>
          }
        </div>
      </div>

      <!-- Message thread -->
      <div class="lg:col-span-2 card p-0 overflow-hidden flex flex-col">
        @if (selectedConv(); as conv) {
          <!-- Header -->
          <div class="px-4 py-3 border-b flex items-center justify-between" [style.borderColor]="'var(--border-default)'">
            <div class="flex items-center gap-2">
              @if (conv.type === 'site_channel') {
                <lucide-icon [img]="HashIcon" [size]="16" [style.color]="'var(--color-brand-500)'" />
              }
              <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ conv.name || 'Direct Message' }}</h3>
              <span class="badge text-[9px] bg-[var(--surface-muted)]">{{ conv.type_label }}</span>
            </div>
          </div>

          <!-- Messages -->
          <div class="flex-1 overflow-y-auto p-4 space-y-3" style="min-height: 350px;">
            @for (msg of messages(); track msg.id) {
              <div class="flex gap-2" [class.flex-row-reverse]="msg.sender_id === currentUserId">
                <div class="max-w-[70%] rounded-lg px-3 py-2"
                  [style.background]="msg.sender_id === currentUserId ? 'var(--color-brand-500)' : 'var(--surface-muted)'"
                  [style.color]="msg.sender_id === currentUserId ? 'white' : 'var(--text-primary)'">
                  <p class="text-[10px] font-medium mb-0.5" [style.opacity]="0.7">{{ msg.sender_id?.substring(0,8) }}</p>
                  @if (msg.message_type === 'image') {
                    <div class="w-48 h-32 bg-black/10 rounded mb-1 flex items-center justify-center">
                      <lucide-icon [img]="ImageIcon" [size]="20" />
                    </div>
                  }
                  @if (msg.message_type === 'location') {
                    <div class="flex items-center gap-1 text-xs mb-1">
                      <lucide-icon [img]="MapPinIcon" [size]="12" /> {{ msg.lat?.toFixed(4) }}, {{ msg.lng?.toFixed(4) }}
                    </div>
                  }
                  <p class="text-sm">{{ msg.content }}</p>
                  <p class="text-[9px] mt-1" [style.opacity]="0.5">{{ msg.created_at | date:'shortTime' }}</p>
                </div>
              </div>
            } @empty {
              <p class="text-sm text-center py-12" [style.color]="'var(--text-tertiary)'">No messages yet. Start the conversation!</p>
            }
          </div>

          <!-- Compose -->
          <div class="p-3 border-t" [style.borderColor]="'var(--border-default)'">
            <div class="flex items-center gap-2">
              <button class="p-2 rounded-lg hover:bg-[var(--surface-muted)]" title="Attach image">
                <lucide-icon [img]="ImageIcon" [size]="16" [style.color]="'var(--text-tertiary)'" />
              </button>
              <button class="p-2 rounded-lg hover:bg-[var(--surface-muted)]" title="Share location">
                <lucide-icon [img]="MapPinIcon" [size]="16" [style.color]="'var(--text-tertiary)'" />
              </button>
              <button class="p-2 rounded-lg hover:bg-[var(--surface-muted)]" title="Voice message">
                <lucide-icon [img]="MicIcon" [size]="16" [style.color]="'var(--text-tertiary)'" />
              </button>
              <input type="text" [(ngModel)]="msgContent" class="input-base flex-1 text-sm" placeholder="Type a message..."
                (keyup.enter)="sendMessage()" />
              <button (click)="sendMessage()" class="btn-primary p-2 rounded-lg" [disabled]="!msgContent.trim()">
                <lucide-icon [img]="SendIcon" [size]="16" />
              </button>
            </div>
          </div>
        } @else {
          <div class="flex-1 flex items-center justify-center">
            <div class="text-center">
              <lucide-icon [img]="MessageSquareIcon" [size]="40" [style.color]="'var(--text-tertiary)'" />
              <p class="text-sm mt-3" [style.color]="'var(--text-tertiary)'">Select a conversation to start messaging</p>
            </div>
          </div>
        }
      </div>
    </div>

    <!-- New chat modal -->
    <g51-modal [open]="showCreate()" title="New Conversation" maxWidth="420px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Type</label>
          <select [(ngModel)]="chatForm.type" class="input-base w-full">
            <option value="direct">Direct Message</option><option value="group">Group Chat</option>
            <option value="site_channel">Site Channel</option></select></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Name</label>
          <input type="text" [(ngModel)]="chatForm.name" class="input-base w-full" placeholder="Chat name (for groups/channels)" /></div>
      </div>
      <div modal-footer>
        <button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onCreate()" class="btn-primary">Create</button>
      </div>
    </g51-modal>
  `,
})
export class ChatComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly MessageSquareIcon = MessageSquare; readonly PlusIcon = Plus; readonly SendIcon = Send;
  readonly ImageIcon = Image; readonly MapPinIcon = MapPin; readonly MicIcon = Mic;
  readonly UsersIcon = Users; readonly HashIcon = Hash;

  readonly showCreate = signal(false);
  readonly conversations = signal<any[]>([]);
  readonly selectedConv = signal<any>(null);
  readonly messages = signal<any[]>([]);
  msgContent = '';
  currentUserId = 'current-user'; // populated from auth
  chatForm = { type: 'direct', name: '' };

  ngOnInit(): void {
    this.api.get<any>('/chat/conversations').subscribe({
      next: res => { if (res.data) this.conversations.set(res.data.conversations || []); },
    });
  }

  selectConversation(conv: any): void {
    this.selectedConv.set(conv);
    this.api.get<any>(`/chat/conversations/${conv.id}/messages`).subscribe({
      next: res => { if (res.data) this.messages.set((res.data.messages || []).reverse()); },
    });
    this.api.post(`/chat/conversations/${conv.id}/read`, {}).subscribe();
  }

  sendMessage(): void {
    if (!this.msgContent.trim() || !this.selectedConv()) return;
    this.api.post(`/chat/conversations/${this.selectedConv().id}/messages`, { content: this.msgContent }).subscribe({
      next: (res: any) => {
        this.messages.update(msgs => [...msgs, res.data]);
        this.msgContent = '';
      },
    });
  }

  onCreate(): void {
    this.api.post('/chat/conversations', this.chatForm).subscribe({
      next: () => { this.showCreate.set(false); this.toast.success('Conversation created'); this.ngOnInit(); },
    });
  }
}
