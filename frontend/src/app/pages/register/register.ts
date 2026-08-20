import { ChangeDetectionStrategy, Component, OnInit, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import { AuthService } from '../../core/auth/auth.service';
import { ApiProblem, ORGANISATION_TYPE_OPTIONS } from '../../core/auth/auth.models';

@Component({
  selector: 'app-register',
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './register.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class Register implements OnInit {
  private readonly authService = inject(AuthService);
  private readonly formBuilder = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);

  protected readonly organisationTypeOptions = ORGANISATION_TYPE_OPTIONS;
  protected readonly registeringOrganisation = signal(false);
  protected readonly submitting = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly registeredEmail = signal<string | null>(null);

  ngOnInit(): void {
    // Landing CTAs link here with ?type=innovator|organisation to pre-select
    // the right path instead of making the visitor choose again.
    if (this.route.snapshot.queryParamMap.get('type') === 'organisation') {
      this.registeringOrganisation.set(true);
    }
  }

  protected readonly form = this.formBuilder.nonNullable.group({
    fullName: ['', [Validators.required, Validators.maxLength(200)]],
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required, Validators.minLength(10)]],
    organisationName: [''],
    organisationType: [''],
  });

  protected toggleOrganisation(checked: boolean): void {
    this.registeringOrganisation.set(checked);
    if (!checked) {
      this.form.patchValue({ organisationName: '', organisationType: '' });
    }
  }

  protected submit(): void {
    if (this.form.invalid || this.submitting()) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting.set(true);
    this.errorMessage.set(null);

    const value = this.form.getRawValue();
    this.authService
      .register({
        fullName: value.fullName,
        email: value.email,
        password: value.password,
        organisationName: this.registeringOrganisation() ? value.organisationName || undefined : undefined,
        organisationType: this.registeringOrganisation()
          ? (value.organisationType as never) || undefined
          : undefined,
      })
      .subscribe({
        next: () => {
          this.submitting.set(false);
          this.registeredEmail.set(value.email);
        },
        error: (error: HttpErrorResponse) => {
          this.submitting.set(false);
          const problem = error.error as ApiProblem | undefined;
          this.errorMessage.set(problem?.detail ?? 'Registration failed. Please try again.');
        },
      });
  }
}
