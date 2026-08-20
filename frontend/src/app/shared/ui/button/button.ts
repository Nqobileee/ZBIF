import { ChangeDetectionStrategy, Component, input, output } from '@angular/core';
import { NgTemplateOutlet } from '@angular/common';
import { Params, RouterLink } from '@angular/router';

export type ButtonVariant = 'primary' | 'secondary' | 'outline' | 'ghost' | 'glass';
export type ButtonSize = 'sm' | 'md' | 'lg';

/**
 * One button for both navigation and actions — renders an `<a
 * routerLink>` when `routerLink` is set, a `<button>` otherwise, so a CTA
 * and a form-submit button stay visually identical without two components.
 * Radius matches ZB-TFS's `zb-button` (`rounded-sm`) rather than the pill
 * shape used earlier — deliberate, not a default.
 *
 * The projected label is captured once in `labelContent` and reused via
 * `ngTemplateOutlet` in both branches — putting a second, separate
 * `<ng-content>` in the `<a>` branch looked reasonable but silently renders
 * empty: Angular only projects into the *last* `<ng-content>` with a given
 * selector when more than one appears in the template, and `@if`/`@else`
 * compiles to two of them. Caught by button.spec.ts, not by eye.
 */
@Component({
  selector: 'app-button',
  imports: [RouterLink, NgTemplateOutlet],
  host: {
    '[class.w-full]': 'fullWidth()',
    class: 'inline-flex',
  },
  template: `
    <ng-template #labelContent>
      @if (loading()) {
        <span class="spinner" aria-hidden="true"></span>
      }
      <ng-content />
    </ng-template>

    @if (routerLink() !== null) {
      <a
        [routerLink]="routerLink()!"
        [queryParams]="queryParams()"
        [class]="buttonClasses"
        [attr.aria-disabled]="disabled() || loading() ? 'true' : null"
        (click)="clicked.emit($event)"
      >
        <ng-container [ngTemplateOutlet]="labelContent"></ng-container>
      </a>
    } @else {
      <button
        [type]="type()"
        [disabled]="disabled() || loading()"
        [class]="buttonClasses"
        (click)="clicked.emit($event)"
      >
        <ng-container [ngTemplateOutlet]="labelContent"></ng-container>
      </button>
    }
  `,
  styles: [
    `
      :host {
        display: inline-flex;
      }
      .spinner {
        width: 0.9em;
        height: 0.9em;
        border-radius: 50%;
        border: 2px solid currentColor;
        border-top-color: transparent;
        animation: app-button-spin 0.6s linear infinite;
      }
      @keyframes app-button-spin {
        to {
          transform: rotate(360deg);
        }
      }
      @media (prefers-reduced-motion: reduce) {
        .spinner {
          animation-duration: 1.5s;
        }
      }
    `,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class Button {
  readonly variant = input<ButtonVariant>('primary');
  readonly size = input<ButtonSize>('md');
  readonly type = input<'button' | 'submit' | 'reset'>('button');
  readonly disabled = input(false);
  readonly loading = input(false);
  readonly fullWidth = input(false);
  readonly routerLink = input<string | unknown[] | null>(null);
  readonly queryParams = input<Params | null>(null);
  readonly clicked = output<MouseEvent>();

  protected get buttonClasses(): string {
    const base =
      'inline-flex items-center justify-center gap-2 rounded-sm font-bold transition ' +
      'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 ' +
      'disabled:opacity-50 disabled:cursor-not-allowed aria-disabled:opacity-50 aria-disabled:pointer-events-none';

    const sizes: Record<ButtonSize, string> = {
      sm: 'px-3.5 py-1.5 text-xs',
      md: 'px-6 py-3 text-sm',
      lg: 'px-8 py-3.5 text-base',
    };

    const variants: Record<ButtonVariant, string> = {
      primary: 'bg-(--color-brass) text-white hover:opacity-90 focus-visible:ring-(--color-brass)',
      secondary: 'bg-(--color-teal) text-white hover:opacity-90 focus-visible:ring-(--color-teal)',
      outline:
        'border border-(--color-rule) text-(--color-ink) hover:border-(--color-teal) hover:text-(--color-teal) focus-visible:ring-(--color-teal)',
      ghost: 'text-(--color-teal) hover:bg-(--color-surface-sunk) focus-visible:ring-(--color-teal)',
      // For CTAs sitting over a photo (the hero) — frosted panel, not a solid fill.
      glass: 'glass-dark text-white hover:bg-white/15 focus-visible:ring-white',
    };

    const width = this.fullWidth() ? 'w-full' : '';

    return `${base} ${sizes[this.size()]} ${variants[this.variant()]} ${width}`;
  }
}
