import { ChangeDetectionStrategy, Component, OnInit, inject, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import { AuthService } from '../../core/auth/auth.service';
import { ApiProblem } from '../../core/auth/auth.models';
import { Button } from '../../shared/ui/button/button';

type VerifyState = 'verifying' | 'success' | 'error';

@Component({
  selector: 'app-verify-email',
  imports: [RouterLink, Button],
  templateUrl: './verify-email.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class VerifyEmail implements OnInit {
  private readonly authService = inject(AuthService);
  private readonly route = inject(ActivatedRoute);

  protected readonly state = signal<VerifyState>('verifying');
  protected readonly errorMessage = signal<string | null>(null);

  ngOnInit(): void {
    const token = this.route.snapshot.queryParamMap.get('token');
    if (!token) {
      this.state.set('error');
      this.errorMessage.set('This link is missing its verification token.');
      return;
    }

    this.authService.verifyEmail(token).subscribe({
      next: () => this.state.set('success'),
      error: (error: HttpErrorResponse) => {
        const problem = error.error as ApiProblem | undefined;
        this.errorMessage.set(problem?.detail ?? 'This link is invalid or has expired.');
        this.state.set('error');
      },
    });
  }
}
