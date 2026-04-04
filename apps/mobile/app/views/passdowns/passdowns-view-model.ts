import { Observable, ObservableArray } from '@nativescript/core';
import { ApiService } from '../../services/api.service';
export class PassdownsViewModel extends Observable {
    passdowns = new ObservableArray<any>([]);
  async init(): Promise<void> {
    try {
      const res = await ApiService.get('/passdowns?status=pending');
      const items = (res.data?.passdowns || []).map((p: any) => ({
        ...p, siteName: p.site_name || '', createdAt: p.created_at?.slice(0, 10) || '',
        onAcknowledge: async () => {
          try {
            await ApiService.post(`/passdowns/${p.id}/acknowledge`, {});
            const idx = this.passdowns.indexOf(p);
            if (idx >= 0) this.passdowns.setItem(idx, { ...p, isAcknowledged: true });
          } catch (e) { console.error(e); }
        },
      }));
      this.passdowns.splice(0, this.passdowns.length, ...items);
    } catch (e) { console.error(e); }
  }
}
