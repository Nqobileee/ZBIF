import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-footer',
  imports: [RouterLink],
  templateUrl: './footer.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class Footer {
  // Static rather than new Date().getFullYear() — avoids relying on a
  // browser-only-consistent global during SSR hydration for one digit.
  protected readonly year = 2026;
}
