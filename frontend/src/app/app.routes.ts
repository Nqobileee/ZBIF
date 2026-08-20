import { Routes } from '@angular/router';
import { authGuard } from './core/auth/auth.guard';
import { GuestLayout } from './layouts/guest-layout/guest-layout';

export const routes: Routes = [
  {
    path: '',
    component: GuestLayout,
    children: [
      {
        path: '',
        loadComponent: () => import('./pages/home/home').then((m) => m.Home),
        title: 'ZBIF — Zimbabwe Business Innovation Forum',
      },
      {
        path: 'about',
        loadComponent: () => import('./pages/about/about').then((m) => m.About),
        title: 'About — ZBIF',
      },
      {
        path: 'sub-themes',
        loadComponent: () => import('./pages/sub-themes/sub-themes').then((m) => m.SubThemes),
        title: 'Sub-themes — ZBIF',
      },
      {
        path: 'sponsorship',
        loadComponent: () => import('./pages/sponsorship/sponsorship').then((m) => m.Sponsorship),
        title: 'Sponsorship — ZBIF',
      },
      {
        path: 'register',
        loadComponent: () => import('./pages/register/register').then((m) => m.Register),
        title: 'Register — ZBIF',
      },
      {
        path: 'login',
        loadComponent: () => import('./pages/login/login').then((m) => m.Login),
        title: 'Sign in — ZBIF',
      },
      {
        path: 'verify-email',
        loadComponent: () => import('./pages/verify-email/verify-email').then((m) => m.VerifyEmail),
        title: 'Verify your email — ZBIF',
      },
      {
        path: 'forgot-password',
        loadComponent: () =>
          import('./pages/forgot-password/forgot-password').then((m) => m.ForgotPassword),
        title: 'Forgot password — ZBIF',
      },
      {
        path: 'reset-password',
        loadComponent: () => import('./pages/reset-password/reset-password').then((m) => m.ResetPassword),
        title: 'Reset password — ZBIF',
      },
      {
        path: 'account',
        loadComponent: () => import('./pages/account/account').then((m) => m.Account),
        canActivate: [authGuard],
        title: 'My account — ZBIF',
      },
      {
        path: '**',
        loadComponent: () => import('./pages/not-found/not-found').then((m) => m.NotFound),
        title: 'Page not found — ZBIF',
      },
    ],
  },
];
