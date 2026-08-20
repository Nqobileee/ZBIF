import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';
import { Button } from '../../shared/ui/button/button';

@Component({
  selector: 'app-account',
  imports: [Button],
  templateUrl: './account.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class Account {
  protected readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  protected signOut(): void {
    this.authService.logout().subscribe({
      complete: () => void this.router.navigate(['/']),
    });
  }
}
