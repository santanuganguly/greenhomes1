---
applyTo: '**/*.php, **/*.css, **/*.html'
description: 'Anti-slop design and typography standards derived from Leonxlnx/taste-skill for high-taste architectural and construction web interfaces.'
---

# Anti-Slop Frontend & Architectural Taste Guidelines

When writing or modifying frontend code (HTML, CSS, PHP views):

## 1. Materiality & Visual Restraint
- **No Floating AI Badges**: Do not create floating frosted glass badges with emojis/checkmarks ("Premium Quality Assured", "15+ Years of Excellence"). Use clean, frame-anchored spec tags with small dot indicators.
- **No Radial Blur Blobs**: Do not use `radial-gradient(circle...)` blurred background decorations behind images.
- **Strict Corner-Radius System**:
  - Cards, modals, containers: `--gh-radius: 12px`
  - Small inputs, tags, badges: `--gh-radius-sm: 6px`
  - Primary/secondary buttons: `border-radius: 999px`
- **Restrained Elevation**: Use clean, multi-stop low-opacity shadows (`rgba(0, 0, 0, 0.06)`).

## 2. Micro-Interactions
- Linked cards lift $2\text{px}$ on hover (`transform: translateY(-2px)`).
- Images scale gently inside containers on hover ($1.025\times$).
- Buttons step on hover (`translateY(-1px)`) and press on click (`:active { transform: scale(0.98); }`).

## 3. Copywriting & Voice
- Never use AI fluff words: "delve", "tapestry", "seamless", "elevate", "unlock", "passionate", "transforming your vision", "building dreams".
- Use tangible contractor details: IS 1893 seismic compliance, Fe550 steel, SMC sanction filings, 470 inspection checkpoints.
- Limit em-dash (`—`) usage to natural breaks (maximum 1–2 per document).
- Never end body copy or headings with exclamation marks.
- Format all action buttons as **verb + object** pairs.
