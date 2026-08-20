import { Injectable, computed, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { TokenStorageService } from './token-storage.service';
import { AuthTokens, RegisterRequest, UserProfile } from './auth.models';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly tokenStorage = inject(TokenStorageService);
  private readonly baseUrl = `${environment.apiBaseUrl}/auth`;

  private readonly currentUserSignal = signal<UserProfile | null>(null);
  readonly currentUser = this.currentUserSignal.asReadonly();
  readonly isAuthenticated = computed(() => this.currentUserSignal() !== null);

  register(request: RegisterRequest): Observable<UserProfile> {
    return this.http.post<UserProfile>(`${this.baseUrl}/register`, request);
  }

  login(email: string, password: string): Observable<AuthTokens> {
    return this.http.post<AuthTokens>(`${this.baseUrl}/login`, { email, password }).pipe(
      tap((tokens) => this.tokenStorage.setTokens(tokens.accessToken, tokens.refreshToken)),
    );
  }

  logout(): Observable<void> {
    const refreshToken = this.tokenStorage.getRefreshToken();
    this.tokenStorage.clear();
    this.currentUserSignal.set(null);
    if (!refreshToken) {
      return new Observable((subscriber) => {
        subscriber.next();
        subscriber.complete();
      });
    }
    return this.http.post<void>(`${this.baseUrl}/logout`, { refreshToken });
  }

  refresh(): Observable<AuthTokens> {
    const refreshToken = this.tokenStorage.getRefreshToken();
    return this.http.post<AuthTokens>(`${this.baseUrl}/refresh`, { refreshToken }).pipe(
      tap((tokens) => this.tokenStorage.setTokens(tokens.accessToken, tokens.refreshToken)),
    );
  }

  fetchCurrentUser(): Observable<UserProfile> {
    return this.http
      .get<UserProfile>(`${this.baseUrl}/me`)
      .pipe(tap((user) => this.currentUserSignal.set(user)));
  }

  verifyEmail(token: string): Observable<{ message: string }> {
    return this.http.get<{ message: string }>(`${this.baseUrl}/verify-email`, { params: { token } });
  }

  forgotPassword(email: string): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.baseUrl}/forgot-password`, { email });
  }

  resetPassword(token: string, newPassword: string): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.baseUrl}/reset-password`, { token, newPassword });
  }

  hasAccessToken(): boolean {
    return !!this.tokenStorage.getAccessToken();
  }
}
