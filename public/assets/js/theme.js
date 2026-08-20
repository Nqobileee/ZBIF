/**
 * Semantic theme tokens for ZBIF (JS mirror of tokens.css).
 * Prefer CSS variables in markup. Use this only when JS needs brand values.
 * // TODO(design-review): reconcile greens with official logo sampling
 */
window.ZBIF_THEME = {
  color: {
    green: { 900: '#06271D', 800: '#0A3527', 700: '#0B3D2E', 600: '#0F4F3B', 500: '#14684E', 100: '#DCEAE4', 50: '#EEF4F1' },
    gold: { 600: '#B8941F', 500: '#C9A227', 100: '#F5EAC8' },
    ink: '#101915',
    muted: '#5B6B63',
    bg: '#FFFFFF',
    bgAlt: '#F7F9F8',
    bgDark: '#06271D',
    line: 'rgba(16,25,21,0.08)',
    success: '#1E7F51',
    warning: '#C9821F',
    danger: '#B23A3A',
    info: '#2A6F97',
  },
  space: [4, 8, 12, 16, 20, 24, 32, 40, 48, 64, 80, 96, 128],
  radius: { sm: 8, md: 12, lg: 16, xl: 24, pill: 999 },
  container: 1280,
  breakpoints: { sm: 640, md: 768, lg: 1024, xl: 1280, '2xl': 1536 },
  motion: { fast: 150, base: 220, slow: 400, ease: 'cubic-bezier(0.22, 1, 0.36, 1)' },
};
