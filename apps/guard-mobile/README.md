# Guard51 Guard Mobile App

NativeScript + Angular mobile app for security guards.

## Features
- **Login**: JWT auth with biometric support
- **Home Screen**: Current shift, clock in/out, quick actions
- **Schedule**: Upcoming shifts, confirm/claim, open shifts
- **Clock In/Out**: GPS-validated with optional selfie, geofence check
- **Post Orders**: View standing instructions for current site
- **Passdown Logs**: Create handover notes with attachments
- **Site Tours**: NFC/QR/Virtual checkpoint scanning, progress tracking
- **Panic Button**: One-tap emergency with GPS + voice recording
- **Offline Mode**: Cache schedule, queue clock events, sync when online
- **Background GPS**: Continuous tracking when clocked in

## Tech Stack
- NativeScript 8.7 + Angular 19
- nativescript-geolocation (GPS)
- nativescript-camera (selfie)
- nativescript-biometrics (fingerprint/face)
- nativescript-nfc (checkpoint scan)
- nativescript-barcodescanner (QR scan)
- nativescript-websockets (real-time GPS)

## Build & Run
```bash
cd apps/guard-mobile
ns run android  # Android
ns run ios      # iOS
```

## Distribution
APK built and uploaded via Guard51 App Distribution Platform.
Guards download from their company's /apps page.
