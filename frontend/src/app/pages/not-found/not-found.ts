import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-not-found',
  imports: [RouterLink],
  template: `
    <section class="mx-auto max-w-md px-5 py-24 text-center">
      <p class="font-(family-name:--font-mono) text-xs font-semibold uppercase tracking-[0.14em] text-(--color-teal)">
        404
      </p>
      <h1 class="mt-3 text-3xl font-bold tracking-tight text-(--color-ink)">Page not found</h1>
      <p class="mt-3 text-(--color-muted)">The page you're looking for doesn't exist.</p>
      <a
        routerLink="/"
        class="mt-6 inline-block rounded-full bg-(--color-teal) px-6 py-3 text-sm font-semibold text-(--color-surface) hover:opacity-90"
        >Back to home</a
      >
    </section>
  `,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class NotFound {}
