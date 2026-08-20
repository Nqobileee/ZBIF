import { Component } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { Button } from './button';

@Component({
  imports: [Button],
  template: `<app-button>Sign in</app-button>`,
})
class ButtonHost {}

@Component({
  imports: [Button],
  template: `<app-button routerLink="/register">Register</app-button>`,
})
class LinkButtonHost {}

describe('Button', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({ providers: [provideRouter([])] });
  });

  it('renders projected label text in button mode', () => {
    const fixture = TestBed.createComponent(ButtonHost);
    fixture.detectChanges();
    const el = fixture.nativeElement as HTMLElement;
    expect(el.querySelector('button')?.textContent?.trim()).toBe('Sign in');
  });

  it('renders projected label text in link (routerLink) mode', () => {
    const fixture = TestBed.createComponent(LinkButtonHost);
    fixture.detectChanges();
    const el = fixture.nativeElement as HTMLElement;
    expect(el.querySelector('a')?.textContent?.trim()).toBe('Register');
  });
});
