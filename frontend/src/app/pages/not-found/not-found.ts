import { ChangeDetectionStrategy, Component } from '@angular/core';
import { Button } from '../../shared/ui/button/button';

@Component({
  selector: 'app-not-found',
  imports: [Button],
  template: `
    <section class="mx-auto max-w-md px-5 py-24 text-center">
      <p class="font-(family-name:--font-mono) text-xs font-semibold uppercase tracking-[0.14em] text-(--color-teal)">
        404
      </p>
      <h1 class="mt-3 text-3xl font-bold tracking-tight text-(--color-ink)">Page not found</h1>
      <p class="mt-3 text-(--color-muted)">The page you're looking for doesn't exist.</p>
      <app-button routerLink="/" variant="secondary" class="mt-6">Back to home</app-button>
    </section>
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class NotFound {}
