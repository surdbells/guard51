# Guard51 Desktop App

Electron wrapper for the Guard51 Angular web application.

## Features
- Native desktop experience (Windows, macOS, Linux)
- System tray integration with minimize-to-tray
- Auto-update from App Distribution Platform
- Native notifications
- Startup launch option
- Persistent login session

## Build
```bash
# Windows (.exe)
npm run build:win

# macOS (.dmg)
npm run build:mac

# Linux (.AppImage)
npm run build:linux
```

## Distribution
Upload built binaries to the Guard51 App Distribution Platform for tenant download.
