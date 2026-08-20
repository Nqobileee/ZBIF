# ZBIF design notes

Boardroom-grade calm for a marketplace site. Green carries the brand. Gold is the spark. Poppins is the only typeface.

## Stack adaptation

The original brief assumed Tailwind + shadcn/ui. This codebase uses raw PHP and vanilla CSS/JS on shared hosting. The design system is the same intent, delivered as:

- `public/assets/css/tokens.css` — CSS variables (color, type, space, radius, shadow, motion, z-index)
- `public/assets/css/components.css` — buttons, cards, forms, grids, shells
- `public/assets/css/site.css` — public chrome, hero, journey, deal band, carousel, command palette, Nova
- `public/assets/js/app.js` — mega-menu, drawer, Cmd/Ctrl-K search, countdown, reveal, count-up, carousel, Nova

Do not introduce npm, React, or Tailwind unless the hosting model changes.

## Brand anchors

- Primary: `--green-700` `#0B3D2E`
- Accent: `--gold-500` `#C9A227` (roughly 10% of any screen)
- Dark surfaces: `--bg-dark` for hero and footer
- No em-dashes in UI copy

## Layout conventions

1. Optional eyebrow, heading, short lead, then content
2. Container max 1280px; reading measure 72ch on text-heavy pages
3. Section vertical padding via `--section-y`
4. Cards: border + soft shadow, hover lift one shadow level
5. Signature sections (hero, How it works, Deal Rooms, open challenges) stay demo-quality on the home page

## Global chrome

- Utility bar: organiser, edition, search, log in
- Sticky mega-menu groups: Attend, Participate, Programme, Deals, Partners, About
- Standing CTAs: Submit a Challenge (outline), Register (gold)
- Mobile: full-height drawer with pinned CTAs
- Footer: dark multi-column + newsletter
- Nova: floating green pill with gold ring

## Adding a public page

1. Use `page-hero` + `section container` (or `section-wash`)
2. Prefer existing button and card classes; no raw hex
3. Wire through `PageController` and `routes/web.php`
4. Add the destination to the command-palette list in `app.js` if it is primary IA
5. Keep voice short and verb-led on buttons

## Motion

- Reveal: `.reveal` → `.is-in` on scroll
- Stats: `[data-count]` count-up once in view
- Always respect `prefers-reduced-motion` (see site.css)

## Style guide

Public route: `/style-guide`  
In-app reference: `/app/style-guide`

## TODO(design-review)

- Sample exact greens from the official ZBIF logo asset when available and reconcile the ramp
- Replace placeholder hero photography with licensed Zimbabwean industry imagery and AVIF/WebP srcsets
- Expand command search to live challenges/speakers/sessions via a lightweight JSON endpoint
- Optional dark theme: semantic tokens are ready; do not ship a toggle until requested
