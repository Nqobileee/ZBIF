import {
  ChangeDetectionStrategy,
  Component,
  OnDestroy,
  PLATFORM_ID,
  inject,
  signal,
} from '@angular/core';
import { isPlatformBrowser } from '@angular/common';
import { RouterLink } from '@angular/router';
import { Button } from '../../shared/ui/button/button';

interface ProcessStep {
  n: string;
  title: string;
  body: string;
}

interface SubThemeChip {
  name: string;
}

interface Partner {
  name: string;
}

interface Countdown {
  days: number;
  hours: number;
  minutes: number;
  seconds: number;
}

// 19–22 October 2026, Bulawayo — CAT (UTC+2).
const FORUM_START = new Date('2026-10-19T00:00:00+02:00');

@Component({
  selector: 'app-home',
  imports: [RouterLink, Button],
  templateUrl: './home.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class Home implements OnDestroy {
  private readonly isBrowser = isPlatformBrowser(inject(PLATFORM_ID));
  private intervalId?: ReturnType<typeof setInterval>;

  protected readonly countdown = signal<Countdown>(this.computeCountdown());

  constructor() {
    if (this.isBrowser) {
      this.intervalId = setInterval(() => this.countdown.set(this.computeCountdown()), 1000);
    }
  }

  ngOnDestroy(): void {
    if (this.intervalId !== undefined) clearInterval(this.intervalId);
  }

  private computeCountdown(): Countdown {
    const diffMs = Math.max(0, FORUM_START.getTime() - Date.now());
    const totalSeconds = Math.floor(diffMs / 1000);
    return {
      days: Math.floor(totalSeconds / 86400),
      hours: Math.floor((totalSeconds % 86400) / 3600),
      minutes: Math.floor((totalSeconds % 3600) / 60),
      seconds: totalSeconds % 60,
    };
  }

  protected readonly process: ProcessStep[] = [
    {
      n: '01',
      title: 'Companies submit challenges',
      body: 'Industry players share real operational problems they need solved — software, hardware, operations, training, or process redesign — with context and, where possible, a pilot budget.',
    },
    {
      n: '02',
      title: 'Innovators get matched',
      body: 'Startups, universities and researchers are screened and allocated to the challenges that fit their sector and capability.',
    },
    {
      n: '03',
      title: 'Solutions get built',
      body: 'Matched teams develop their answer with mentor and industry-owner feedback, working toward a demonstrable pitch.',
    },
    {
      n: '04',
      title: 'Deals close at the forum',
      body: 'Teams pitch live, then move into private Deal Rooms with the companies, investors and procurement teams who can actually say yes.',
    },
  ];

  protected readonly subThemes: SubThemeChip[] = [
    { name: 'AI & Digital Transformation' },
    { name: 'Manufacturing & Industrial Modernisation' },
    { name: 'Agritech & Food Security' },
    { name: 'Financial Services & Digital Inclusion' },
    { name: 'Smart Cities & Urban Innovation' },
    { name: 'Green Energy & Sustainability' },
    { name: 'Mining & Resource Efficiency' },
    { name: 'Education & Future Workforce' },
  ];

  protected readonly partners: Partner[] = [
    { name: 'ZB Financial Holdings' },
    { name: 'Steward Bank' },
    { name: 'CBZ Bank' },
    { name: 'Ministry of Industry & Commerce' },
    { name: 'World Vision Zimbabwe' },
    { name: 'UZ Innovation Hub' },
    { name: 'DHL Zimbabwe' },
  ];
}
