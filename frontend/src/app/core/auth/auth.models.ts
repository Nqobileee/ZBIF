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

export type OrganisationStage = 'IDEA' | 'PROTOTYPE' | 'PILOT' | 'EARLY_REVENUE' | 'SCALING' | 'ESTABLISHED';

export interface OrganisationStageOption {
  value: OrganisationStage;
  label: string;
}

export const ORGANISATION_STAGE_OPTIONS: OrganisationStageOption[] = [
  { value: 'IDEA', label: 'Idea' },
  { value: 'PROTOTYPE', label: 'Prototype' },
  { value: 'PILOT', label: 'Pilot' },
  { value: 'EARLY_REVENUE', label: 'Early revenue' },
  { value: 'SCALING', label: 'Scaling' },
  { value: 'ESTABLISHED', label: 'Established' },
];

export type TeamSize = 'SOLO' | 'TWO_TO_FIVE' | 'SIX_TO_FIFTEEN' | 'SIXTEEN_TO_FIFTY' | 'FIFTY_PLUS';

export interface TeamSizeOption {
  value: TeamSize;
  label: string;
}

export const TEAM_SIZE_OPTIONS: TeamSizeOption[] = [
  { value: 'SOLO', label: 'Just me' },
  { value: 'TWO_TO_FIVE', label: '2–5 people' },
  { value: 'SIX_TO_FIFTEEN', label: '6–15 people' },
  { value: 'SIXTEEN_TO_FIFTY', label: '16–50 people' },
  { value: 'FIFTY_PLUS', label: '50+ people' },
];

export type FocusArea =
  | 'SOFTWARE_SAAS'
  | 'HARDWARE_IOT'
  | 'AI_MACHINE_LEARNING'
  | 'PROCESS_IMPROVEMENT'
  | 'TRAINING_CAPACITY_BUILDING'
  | 'OPERATIONS_LOGISTICS'
  | 'BUSINESS_MODEL_DESIGN'
  | 'AGRITECH'
  | 'FINTECH'
  | 'HEALTHTECH'
  | 'LOGISTICS'
  | 'CLEAN_ENERGY'
  | 'MINING'
  | 'RESEARCH_POLICY';

export interface FocusAreaOption {
  value: FocusArea;
  label: string;
}

export const FOCUS_AREA_OPTIONS: FocusAreaOption[] = [
  { value: 'SOFTWARE_SAAS', label: 'Software / SaaS' },
  { value: 'HARDWARE_IOT', label: 'Hardware / IoT' },
  { value: 'AI_MACHINE_LEARNING', label: 'AI / Machine learning' },
  { value: 'PROCESS_IMPROVEMENT', label: 'Process improvement' },
  { value: 'TRAINING_CAPACITY_BUILDING', label: 'Training & capacity building' },
  { value: 'OPERATIONS_LOGISTICS', label: 'Operations & logistics' },
  { value: 'BUSINESS_MODEL_DESIGN', label: 'Business model design' },
  { value: 'AGRITECH', label: 'Agritech' },
  { value: 'FINTECH', label: 'Fintech' },
  { value: 'HEALTHTECH', label: 'Healthtech' },
  { value: 'LOGISTICS', label: 'Logistics' },
  { value: 'CLEAN_ENERGY', label: 'Clean energy' },
  { value: 'MINING', label: 'Mining' },
  { value: 'RESEARCH_POLICY', label: 'Research & policy' },
];

export interface OrganisationSummary {
  id: string;
  name: string;
  type: OrganisationType;
  verified: boolean;
  description: string | null;
  stage: OrganisationStage | null;
  teamSize: TeamSize | null;
  focusAreas: FocusArea[];
  logoUrl: string | null;
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
  description?: string;
  focusAreas?: FocusArea[];
  stage?: OrganisationStage;
  teamSize?: TeamSize;
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
