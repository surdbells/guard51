# Guard51 Web (Angular 21)

This directory will contain the Angular 21 frontend application.

## Setup (Phase 0H)

```bash
# Generate Angular app (run from monorepo root)
npx @angular/cli@21 new web --directory=apps/web --style=scss --routing --strict --standalone

# Install dependencies
cd apps/web
npm install @angular/material@21 @angular/cdk@21
npm install socket.io-client dexie @ngx-translate/core @ngx-translate/http-loader
```

The Angular frontend will be scaffolded in Phase 0H after the backend API is functional.
