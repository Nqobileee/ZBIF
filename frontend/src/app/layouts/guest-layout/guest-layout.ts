import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { Header } from '../../shared/header/header';
import { Footer } from '../../shared/footer/footer';

/**
 * Chrome for every public-facing route — marketing pages and the auth flow.
 * A dedicated authenticated layout (sidebar/dashboard chrome) can sit
 * alongside this one later without touching how these routes render.
 */
@Component({
  selector: 'app-guest-layout',
  imports: [RouterOutlet, Header, Footer],
  templateUrl: './guest-layout.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class GuestLayout {}
