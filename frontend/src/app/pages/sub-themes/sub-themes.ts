import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

interface SubTheme {
  n: string;
  name: string;
  blurb: string;
}

@Component({
  selector: 'app-sub-themes',
  imports: [RouterLink],
  templateUrl: './sub-themes.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SubThemes {
  protected readonly subThemes: SubTheme[] = [
    {
      n: '01',
      name: 'AI and Digital Transformation in African Business',
      blurb: 'Applying AI and digital tools to real operational problems across sectors.',
    },
    {
      n: '02',
      name: 'Manufacturing Innovation and Industrial Modernisation',
      blurb: 'Efficiency, automation and modernisation for local manufacturers.',
    },
    {
      n: '03',
      name: 'Agritech and Food Security Solutions',
      blurb: 'Technology addressing productivity, supply chains and food security.',
    },
    {
      n: '04',
      name: 'Financial Services Innovation and Digital Inclusion',
      blurb: 'Fintech and digital inclusion for underserved individuals and businesses.',
    },
    {
      n: '05',
      name: 'Smart Cities and Urban Innovation',
      blurb: 'Infrastructure, mobility and services for growing urban centres.',
    },
    {
      n: '06',
      name: 'Green Energy and Sustainability',
      blurb: 'Renewable energy and sustainability solutions for industry and communities.',
    },
    {
      n: '07',
      name: 'Innovation in Mining and Resource Efficiency',
      blurb: 'Resource efficiency and modernisation across the mining value chain.',
    },
    {
      n: '08',
      name: 'Education, Skills, and Future Workforce Readiness',
      blurb: 'Preparing Zimbabwe’s workforce for an innovation-driven economy.',
    },
  ];
}
