import { ChangeDetectionStrategy, Component } from '@angular/core';
import { Button } from '../../shared/ui/button/button';

interface SponsorshipTier {
  name: string;
  contribution: string;
  benefits: string;
}

@Component({
  selector: 'app-sponsorship',
  imports: [Button],
  templateUrl: './sponsorship.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class Sponsorship {
  protected readonly tiers: SponsorshipTier[] = [
    { name: 'Platinum Sponsor', contribution: '$25,000', benefits: 'Naming rights, keynote slot, premium branding' },
    { name: 'Deal Room Sponsor', contribution: '$10,000', benefits: 'Exclusive branding of deal rooms' },
    { name: 'Gold Sponsor', contribution: '$15,000', benefits: 'Exhibition space, speaking opportunities' },
    { name: 'Silver Sponsor', contribution: '$7,500', benefits: 'Branding and networking access' },
    { name: 'Innovation Partner', contribution: '$5,000', benefits: 'Innovation showcase visibility' },
    { name: 'University Partner', contribution: '$3,000', benefits: 'Academic participation branding' },
  ];
}
