import { Application, Device } from '@nativescript/core';
import { ApiService } from './api.service';
import { SecureStorage } from './secure-storage.service';

export class PushNotificationService {
    
  async register(): Promise<void> {
    if (Device.os === 'Android') {
      await this.registerAndroid();
    } else {
      await this.registerIOS();
    }
  }

  private async registerAndroid(): Promise<void> {
    try {
      // Firebase messaging for Android
      const firebase = require('@nativescript/firebase-messaging');
      const token = await firebase.messaging().getToken();
      if (token) {
        await this.sendTokenToServer(token, 'android');
        SecureStorage.set('fcm_token', token);
      }

      // Handle incoming messages
      firebase.messaging().onMessage((message: any) => {
        console.log('Push received:', message);
        // Show local notification
        this.showLocalNotification(message.title, message.body);
      });
    } catch (e) {
      console.error('FCM registration failed:', e);
    }
  }

  private async registerIOS(): Promise<void> {
    try {
      const firebase = require('@nativescript/firebase-messaging');
      await firebase.messaging().requestPermission();
      const token = await firebase.messaging().getToken();
      if (token) {
        await this.sendTokenToServer(token, 'ios');
        SecureStorage.set('apns_token', token);
      }
    } catch (e) {
      console.error('APNS registration failed:', e);
    }
  }

  private async sendTokenToServer(token: string, platform: string): Promise<void> {
    try {
      await ApiService.post('/devices/register', {
        token, platform, device_id: Device.uuid,
        app_version: '1.0.0', os_version: Device.osVersion,
      });
    } catch (e) { console.error('Token registration failed:', e); }
  }

  private showLocalNotification(title: string, body: string): void {
    try {
      const LocalNotifications = require('@nativescript/local-notifications');
      LocalNotifications.schedule([{ id: Date.now(), title, body, badge: 1 }]);
    } catch (e) { console.error(e); }
  }
}
