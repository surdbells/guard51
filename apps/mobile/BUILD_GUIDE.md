# Guard51 Mobile App — Build Guide

## Prerequisites

```bash
# Install NativeScript CLI globally
npm install -g nativescript

# Verify
ns --version
ns doctor  # checks environment
```

### Android
- Java JDK 17 (not 21)
- Android SDK (API 34)
- Android Build Tools 34.0.0
- Set ANDROID_HOME and JAVA_HOME environment variables

### iOS (macOS only)
- Xcode 15+
- CocoaPods: `sudo gem install cocoapods`
- Apple Developer account

## Setup

```bash
cd apps/mobile
npm install
```

## Development

```bash
# Run on connected Android device/emulator
ns run android

# Run on iOS simulator (macOS only)
ns run ios
```

## Build APK (Android)

### Debug APK
```bash
ns build android
# Output: platforms/android/app/build/outputs/apk/debug/app-debug.apk
```

### Release APK (signed)
```bash
# Generate keystore (first time only)
cd keystore && bash generate-keystore.sh && cd ..

# Build signed APK
ns build android --release \
  --key-store-path ./keystore/guard51.jks \
  --key-store-password Guard51@2026 \
  --key-store-alias guard51 \
  --key-store-alias-password Guard51@2026

# Output: platforms/android/app/build/outputs/apk/release/app-release.apk
```

### AAB (Android App Bundle — for Play Store)
```bash
ns build android --release --aab \
  --key-store-path ./keystore/guard51.jks \
  --key-store-password Guard51@2026 \
  --key-store-alias guard51 \
  --key-store-alias-password Guard51@2026

# Output: platforms/android/app/build/outputs/bundle/release/app-release.aab
```

## Build IPA (iOS)

### Simulator build
```bash
ns build ios
```

### Device build (requires Apple Developer account)
```bash
ns build ios --release --for-device \
  --provision "Guard51 Distribution" \
  --team-id "YOUR_TEAM_ID"

# Output: platforms/ios/build/Release-iphoneos/guard51mobile.ipa
```

## App Store Submission

### Google Play Store
1. Build AAB (see above)
2. Go to [Google Play Console](https://play.google.com/console)
3. Create new app → "Guard51 — Security Guard App"
4. Upload AAB under Production track
5. Fill in store listing from `store-listing.md`
6. Add screenshots (phone + tablet)
7. Submit for review

### Apple App Store
1. Build IPA or use Xcode archive
2. Open Xcode → Product → Archive
3. Upload to App Store Connect
4. Fill in App Store listing
5. Submit for review

## Troubleshooting

```bash
# Clean build cache
ns clean

# Reset platforms
rm -rf platforms node_modules
npm install
ns platform add android
ns platform add ios

# Check environment
ns doctor

# Verbose build
ns build android --log trace
```
