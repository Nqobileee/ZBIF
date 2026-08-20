import { ChangeDetectionStrategy, Component, OnInit, inject, signal } from '@angular/core';
import { AbstractControl, FormBuilder, ReactiveFormsModule, ValidationErrors, Validators } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import { AuthService } from '../../core/auth/auth.service';
import {
  ApiProblem,
  FOCUS_AREA_OPTIONS,
  FocusArea,
  ORGANISATION_STAGE_OPTIONS,
  ORGANISATION_TYPE_OPTIONS,
  TEAM_SIZE_OPTIONS,
} from '../../core/auth/auth.models';
import { Button } from '../../shared/ui/button/button';

const MAX_LOGO_BYTES = 2 * 1024 * 1024;
const ACCEPTED_LOGO_TYPES = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'];

function passwordsMatchValidator(control: AbstractControl): ValidationErrors | null {
  const password = control.get('password')?.value;
  const confirmPassword = control.get('confirmPassword')?.value;
  return password && confirmPassword && password !== confirmPassword ? { passwordMismatch: true } : null;
}

@Component({
  selector: 'app-register',
  imports: [ReactiveFormsModule, RouterLink, Button],
  templateUrl: './register.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class Register implements OnInit {
  private readonly authService = inject(AuthService);
  private readonly formBuilder = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);

  protected readonly organisationTypeOptions = ORGANISATION_TYPE_OPTIONS;
  protected readonly focusAreaOptions = FOCUS_AREA_OPTIONS;
  protected readonly organisationStageOptions = ORGANISATION_STAGE_OPTIONS;
  protected readonly teamSizeOptions = TEAM_SIZE_OPTIONS;

  protected readonly registeringOrganisation = signal(false);
  protected readonly step = signal<1 | 2>(1);
  protected readonly submitting = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly registeredEmail = signal<string | null>(null);
  protected readonly logoFile = signal<File | null>(null);
  protected readonly logoPreviewUrl = signal<string | null>(null);
  protected readonly logoError = signal<string | null>(null);

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
    confirmPassword: ['', [Validators.required]],
    organisationName: [''],
    organisationType: [''],
    description: ['', [Validators.maxLength(2000)]],
    focusAreas: this.formBuilder.nonNullable.control<FocusArea[]>([]),
    stage: [''],
    teamSize: [''],
  }, { validators: passwordsMatchValidator });

  protected toggleOrganisation(checked: boolean): void {
    this.registeringOrganisation.set(checked);
    this.step.set(1);
    if (!checked) {
      this.form.patchValue({
        organisationName: '',
        organisationType: '',
        description: '',
        stage: '',
        teamSize: '',
      });
      this.form.controls.focusAreas.setValue([]);
      this.clearLogo();
    }
  }

  protected toggleFocusArea(value: FocusArea, checked: boolean): void {
    const current = this.form.controls.focusAreas.value;
    this.form.controls.focusAreas.setValue(
      checked ? [...current, value] : current.filter((area) => area !== value),
    );
  }

  protected isFocusAreaSelected(value: FocusArea): boolean {
    return this.form.controls.focusAreas.value.includes(value);
  }

  protected onLogoSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    if (!file) {
      this.clearLogo();
      return;
    }
    if (!ACCEPTED_LOGO_TYPES.includes(file.type)) {
      this.logoError.set('Logo must be a PNG, JPEG, WEBP or SVG image.');
      input.value = '';
      return;
    }
    if (file.size > MAX_LOGO_BYTES) {
      this.logoError.set('Logo must be 2MB or smaller.');
      input.value = '';
      return;
    }
    this.logoError.set(null);
    this.logoFile.set(file);
    this.logoPreviewUrl.set(URL.createObjectURL(file));
  }

  protected clearLogo(): void {
    const existing = this.logoPreviewUrl();
    if (existing) {
      URL.revokeObjectURL(existing);
    }
    this.logoFile.set(null);
    this.logoPreviewUrl.set(null);
    this.logoError.set(null);
  }

  private readonly step1ControlNames = [
    'fullName',
    'email',
    'password',
    'confirmPassword',
    'organisationName',
    'organisationType',
  ] as const;

  protected continueToCapabilities(): void {
    const step1Invalid = this.step1ControlNames.some((name) => this.form.controls[name].invalid);
    if (step1Invalid || this.form.hasError('passwordMismatch')) {
      this.step1ControlNames.forEach((name) => this.form.controls[name].markAsTouched());
      return;
    }
    this.step.set(2);
  }

  protected backToDetails(): void {
    this.step.set(1);
  }

  protected submit(): void {
    if (this.registeringOrganisation() && this.step() === 1) {
      // Enter key inside a step-1 field submits the form natively; treat that as "Continue".
      this.continueToCapabilities();
      return;
    }
    if (this.form.invalid || this.submitting()) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting.set(true);
    this.errorMessage.set(null);

    const value = this.form.getRawValue();
    const registeringOrg = this.registeringOrganisation();
    this.authService
      .register(
        {
          fullName: value.fullName,
          email: value.email,
          password: value.password,
          organisationName: registeringOrg ? value.organisationName || undefined : undefined,
          organisationType: registeringOrg ? (value.organisationType as never) || undefined : undefined,
          description: registeringOrg ? value.description || undefined : undefined,
          focusAreas: registeringOrg && value.focusAreas.length ? value.focusAreas : undefined,
          stage: registeringOrg ? (value.stage as never) || undefined : undefined,
          teamSize: registeringOrg ? (value.teamSize as never) || undefined : undefined,
        },
        registeringOrg ? this.logoFile() : null,
      )
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
