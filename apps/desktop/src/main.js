const { app, BrowserWindow, Tray, Menu, Notification } = require('electron');
const { autoUpdater } = require('electron-updater');
const path = require('path');

let mainWindow;
let tray;

const API_URL = process.env.GUARD51_API_URL || 'https://app.guard51.com';

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1400,
    height: 900,
    minWidth: 1024,
    minHeight: 700,
    title: 'Guard51',
    icon: path.join(__dirname, '../assets/icon.png'),
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
    },
    autoHideMenuBar: true,
  });

  mainWindow.loadURL(API_URL);

  mainWindow.on('close', (event) => {
    // Minimize to tray instead of closing
    if (!app.isQuitting) {
      event.preventDefault();
      mainWindow.hide();
    }
  });

  mainWindow.on('closed', () => { mainWindow = null; });
}

function createTray() {
  tray = new Tray(path.join(__dirname, '../assets/icon.png'));
  const contextMenu = Menu.buildFromTemplate([
    { label: 'Show Guard51', click: () => { mainWindow.show(); } },
    { label: 'Check for Updates', click: () => { autoUpdater.checkForUpdates(); } },
    { type: 'separator' },
    { label: 'Quit', click: () => { app.isQuitting = true; app.quit(); } },
  ]);
  tray.setToolTip('Guard51 — Security Workforce Management');
  tray.setContextMenu(contextMenu);
  tray.on('double-click', () => { mainWindow.show(); });
}

function showNotification(title, body) {
  new Notification({ title, body }).show();
}

// Auto-update from App Distribution Platform
autoUpdater.on('update-available', () => {
  showNotification('Guard51 Update', 'A new version is available. Downloading...');
});
autoUpdater.on('update-downloaded', () => {
  showNotification('Guard51 Update', 'Update ready. Restart to apply.');
});

app.whenReady().then(() => {
  createWindow();
  createTray();

  // Check for updates on startup
  if (!process.env.DEV) {
    autoUpdater.checkForUpdatesAndNotify();
  }

  // Auto-launch on startup (optional)
  app.setLoginItemSettings({
    openAtLogin: false, // User can enable in settings
    path: app.getPath('exe'),
  });
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') app.quit();
});

app.on('activate', () => {
  if (mainWindow === null) createWindow();
  else mainWindow.show();
});

app.on('before-quit', () => { app.isQuitting = true; });
