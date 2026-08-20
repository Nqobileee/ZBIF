import { RenderMode, ServerRoute } from '@angular/ssr';

// Server-render everything per-request rather than prerendering at build
// time — several routes (verify-email, reset-password, account) depend on
// query params or auth state that don't exist until a real request arrives.
export const serverRoutes: ServerRoute[] = [
  {
    path: '**',
    renderMode: RenderMode.Server,
  },
];
