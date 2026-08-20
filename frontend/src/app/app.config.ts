import {
  ApplicationConfig,
  inject,
  provideAppInitializer,
  provideBrowserGlobalErrorListeners,
} from '@angular/core';
import { provideRouter } from '@angular/router';
import { provideHttpClient, withFetch, withInterceptors } from '@angular/common/http';
import { catchError, firstValueFrom, of } from 'rxjs';

import { routes } from './app.routes';
import { provideClientHydration, withEventReplay } from '@angular/platform-browser';
import { authInterceptor } from './core/auth/auth.interceptor';
import { AuthService } from './core/auth/auth.service';

export const appConfig: ApplicationConfig = {
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideRouter(routes),
    provideClientHydration(withEventReplay()),
    provideHttpClient(withFetch(), withInterceptors([authInterceptor])),
    // Hydrates the current-user signal from a stored token on app start, so
    // the header/account state is correct after a full page reload.
    provideAppInitializer(() => {
      const authService = inject(AuthService);
      if (!authService.hasAccessToken()) {
        return Promise.resolve();
      }
      return firstValueFrom(authService.fetchCurrentUser().pipe(catchError(() => of(null))));
    }),
  ],
};
