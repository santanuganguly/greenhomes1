---
description: 'Audit and refactor a webpage to eliminate AI-generated slop, restore architectural taste, and clean copy tells.'
agent: agent
argument-hint: '[file-path]'
---

# De-slopify Web Page

## Mission
Analyze `${selection}` or the specified file against `Leonxlnx/taste-skill` standards and refactor it into clean, dignified, architectural code.

## Checklist
1. **Visual Elements**:
   - Strip all decorative radial blur circles (`radial-gradient(circle...)`).
   - Remove floating badges/pills with checkmark emojis.
   - Ground images with realistic, frame-anchored spec tags.
2. **Tokens & Radii**:
   - Replace arbitrary `border-radius` with `--gh-radius: 12px` and `--gh-radius-sm: 6px`.
   - Ensure buttons use clean pills (`border-radius: 999px`) with `:active { transform: scale(0.98); }`.
3. **Copy & Voice**:
   - Remove banned AI words: "tapestry", "seamless", "elevate", "unlock", "passionate", "transforming your vision".
   - Replace empty circular claims with concrete numbers, IS codes, and North Bengal geographical details.
   - Replace em-dashes (`—`) with clean commas or colons.
   - Format action buttons as **verb + object** pairs.
