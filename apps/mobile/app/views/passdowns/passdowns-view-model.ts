import { Observable, ObservableArray } from '@nativescript/core';
import { ApiService } from '../../services/api.service';
export class PassdownsViewModel extends Observable {
  private api = new ApiService();
  passdowns = new ObservableArray<any>([]);
  async init(): Promise<void> {
    try {
      const res = await this.api.get('/passdowns?status=pending');
      const items = (res.data?.passdowns || []).map((p: any) => ({
        ...p, siteName: p.site_name || '', createdAt: p.created_at?.slice(0, 10) || '',
        onAcknowledge: async () => {
          try {
            await this.api.post(`/passdowns/${p.id}/acknowledge`, {});
            const idx = this.passdowns.indexOf(p);
            if (idx >= 0) this.passdowns.setItem(idx, { ...p, isAcknowledged: true });
          } catch (e) { console.error(e); }
        },
      }));
      this.passdowns.splice(0, this.passdowns.length, ...items);
    } catch (e) { console.error(e); }
  }
}
