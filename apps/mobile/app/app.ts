import { Application } from '@nativescript/core';

// Start with login page — after auth, navigates to app-root with bottom tabs
Application.run({ moduleName: 'app/views/login/login-page' });
