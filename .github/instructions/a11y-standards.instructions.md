---
applyTo: '**/*.php, **/*.html, **/*.js'
description: 'Web accessibility standards based on WCAG 2.2 AA covering form labeling, keyboard navigation, touch targets, and ARIA roles.'
---

# Web Accessibility Standards (WCAG 2.2 AA)

When writing or modifying UI components:

## 1. Touch Targets & Mobile Usability (WCAG 2.5.5 / 2.5.8)
- Every interactive element (button, link, form field) must have at least a $44\times44\text{px}$ touch target on mobile devices (`min-height: 48px; min-width: 48px`).
- Form inputs must have `font-size: 16px` on mobile screens to prevent automatic zoom behavior on iOS Safari.

## 2. Accessible Form Controls (WCAG 1.3.1 / 3.3.2)
- Every input, select, and textarea must have an associated `<label for="id">`:
  ```html
  <label for="contact-name">Full Name <span class="gh-required" aria-hidden="true">*</span></label>
  <input id="contact-name" name="name" type="text" required autocomplete="name" enterkeyhint="next" />
  ```
- Use `autocomplete` attributes on all personal data fields (`name`, `email`, `tel`).
- Use `inputmode="tel"` for phone numbers to invoke the numeric keypad.

## 3. Keyboard Navigation & Focus Visibility (WCAG 2.4.7)
- Never remove focus rings without an accessible replacement. Use:
  ```css
  :focus-visible {
    outline: 2px solid var(--gh-green-700);
    outline-offset: 2px;
  }
  ```
- Screen reader skip links (`.skip-link`) must be visually hidden using `clip: rect(0, 0, 0, 0)` when inactive and jump into view when focused.
