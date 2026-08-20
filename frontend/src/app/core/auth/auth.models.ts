export type OrganisationType =
  | 'CORPORATE'
  | 'SME'
  | 'UNIVERSITY'
  | 'RESEARCH_INSTITUTE'
  | 'INNOVATION_HUB'
  | 'STARTUP'
  | 'GOVERNMENT_AGENCY'
  | 'INVESTOR_FIRM'
  | 'OTHER';

export interface OrganisationTypeOption {
  value: OrganisationType;
  label: string;
}

export const ORGANISATION_TYPE_OPTIONS: OrganisationTypeOption[] = [
  { value: 'CORPORATE', label: 'Corporate' },
  { value: 'SME', label: 'SME' },
  { value: 'UNIVERSITY', label: 'University' },
  { value: 'RESEARCH_INSTITUTE', label: 'Research institute' },
  { value: 'INNOVATION_HUB', label: 'Innovation hub' },
  { value: 'STARTUP', label: 'Startup' },
  { value: 'GOVERNMENT_AGENCY', label: 'Government agency' },
  { value: 'INVESTOR_FIRM', label: 'Investor / VC / DFI' },
  { value: 'OTHER', label: 'Other' },
];

export interface OrganisationSummary {
  id: string;
  name: string;
  type: OrganisationType;
  verified: boolean;
}

export interface UserProfile {
  id: string;
  fullName: string;
  email: string;
  emailVerified: boolean;
  organisation: OrganisationSummary | null;
}

export interface RegisterRequest {
  fullName: string;
  email: string;
  password: string;
  organisationName?: string;
  organisationType?: OrganisationType;
}

export interface AuthTokens {
  accessToken: string;
  refreshToken: string;
  tokenType: string;
  expiresIn: number;
}

export interface ApiProblem {
  title?: string;
  detail?: string;
  status?: number;
  errors?: Record<string, string>;
}
