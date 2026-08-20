import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import { AuthService } from '../../core/auth/auth.service';
import { ApiProblem } from '../../core/auth/auth.models';
import { Button } from '../../shared/ui/button/button';

@Component({
  selector: 'app-login',
  imports: [ReactiveFormsModule, RouterLink, Button],
  templateUrl: './login.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class Login {
  private readonly authService = inject(AuthService);
  private readonly formBuilder = inject(FormBuilder);
  private readonly router = inject(Router);

  protected readonly submitting = signal(false);
  protected readonly errorMessage = signal<string | null>(null);

  protected readonly form = this.formBuilder.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
  });

  protected submit(): void {
    if (this.form.invalid || this.submitting()) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting.set(true);
    this.errorMessage.set(null);
    const { email, password } = this.form.getRawValue();

    this.authService.login(email, password).subscribe({
      next: () => {
        this.authService.fetchCurrentUser().subscribe();
        this.submitting.set(false);
        void this.router.navigate(['/account']);
      },
      error: (error: HttpErrorResponse) => {
        this.submitting.set(false);
        const problem = error.error as ApiProblem | undefined;
        this.errorMessage.set(problem?.detail ?? 'Sign in failed. Please try again.');
      },
    });
  }
}
