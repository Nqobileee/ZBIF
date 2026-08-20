import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideHttpClient } from '@angular/common/http';
import { GuestLayout } from './guest-layout';

describe('GuestLayout', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [GuestLayout],
      providers: [provideRouter([]), provideHttpClient()],
    }).compileComponents();
  });

  it('should create', () => {
    const fixture = TestBed.createComponent(GuestLayout);
    expect(fixture.componentInstance).toBeTruthy();
  });

  it('should render the header logo and footer', async () => {
    const fixture = TestBed.createComponent(GuestLayout);
    await fixture.whenStable();
    const compiled = fixture.nativeElement as HTMLElement;
    expect(compiled.querySelector('header img[alt*="ZBIF"]')).toBeTruthy();
    expect(compiled.querySelector('footer')).toBeTruthy();
  });
});
