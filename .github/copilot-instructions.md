# GitHub Copilot Instructions for Green Homes

## 1. Project Overview & Architecture
Green Homes by Green Hill Group is a design-to-build residential and commercial construction platform in Siliguri and North Bengal.

The workspace consists of three core applications:
1. **Public Web Portal (`public/`, `src/`)**:
   - Vanilla PHP 8.2 with custom lightweight MVC/front-controller (`public/index.php`, `public/router.php`).
   - Reusable template partials via `src/view.php` (`gh_header()`, `gh_footer()`).
   - Parameterized PDO database layer (`src/config.php`, `src/repo/`).
   - CSRF protection (`src/csrf.php`) and Google Sheets lead capture webhook (`src/google_sheets.php`).
2. **Admin CRM & WhatsApp Engine (`crm_clean/`)**:
   - Laravel 11, Livewire (Volt components), Tailwind CSS.
   - Design Aesthetic: Monolithic, brutalist "Neon Terminal Blue" hacker theme (`#0a0a0a` background, `#0077ff` borders/text, strictly monospace, zero rounded corners, strictly NO emojis).
   - Deployment: Hosted on Hostinger via FTP.
3. **Mobile Client App (`mobile-app/`)**:
   - Capacitor hybrid mobile wrapper (`mobile-app/www/`, `mobile-app/android/`).

---

## 2. Frontend Design & Architectural Taste Standards (Anti-Slop)
This project enforces the anti-slop guidelines from `Leonxlnx/taste-skill`:
- **No AI Clichés or Soft Blobs**: Do NOT use decorative blurred radial gradient circles (`radial-gradient(circle...)`) or floating glass pill badges with checkmark icons.
- **Strict Corner-Radius System**: Never mix arbitrary corner radii. Adhere to:
  - `--gh-radius: 12px` for cards, dialogs, and image frames.
  - `--gh-radius-sm: 6px` for small inputs, badges, and code tags.
  - Buttons use pills (`border-radius: 999px`) consistently across the public site.
- **Micro-Interactions**:
  - Cards lift $2\text{px}$ on hover (`transform: translateY(-2px); transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s;`).
  - Photos scale inside frames on hover ($1.025\times$).
  - Buttons press on click (`:active { transform: scale(0.98); }`).
- **Grounded Copy (Banned AI Words)**:
  - Never use fluff words: "delve", "tapestry", "seamless", "elevate", "unlock", "passionate", "transforming your vision", "building dreams".
  - Always state concrete construction specifications: per-sqft rates (₹2,150–₹2,650/sq.ft.), 470 documented inspection checkpoints per floor, IS 1893 seismic zone IV compliance, Fe550 steel, SMC/SJDA sanction filings.
- **Action Buttons**: Format labels as concise **verb + object** pairs (e.g., `Calculate Home Estimate`, `Plan a Renovation`, `Book Consultation`).

---

## 3. Mobile Responsiveness & Form Factor Adaptation
- **Touch Targets**: All interactive elements (inputs, select menus, buttons, action pills) MUST maintain a minimum $48\text{px}$ touch target height (`min-height: 48px`).
- **iOS Safari Auto-Zoom Prevention**: Form inputs (`input`, `select`, `textarea`) must be at least `16px` font size on mobile to prevent iOS Safari auto-zooming.
- **Virtual Keyboard Optimization**:
  - Phone inputs: `type="tel" autocomplete="tel" inputmode="tel"`
  - Email inputs: `type="email" autocomplete="email" autocapitalize="none" spellcheck="false"`
  - Navigation keys: Use `enterkeyhint="next"` on inputs and `enterkeyhint="send"` on message textareas.
- **Single-Column Stacking**: Multi-column grids must gracefully stack into 1 column on screens $\le 900\text{px}$ or $\le 600\text{px}$ with compact padding ($16\text{px}$–$20\text{px}$).

---

## 4. Accessibility & Web Standards (WCAG 2.2 AA)
- Semantic HTML tags (`<main>`, `<header>`, `<nav>`, `<aside>`, `<footer>`, `<form>`).
- Always pair `<label for="id">` with `<input id="id">`.
- Always provide visible focus states with `outline: 2px solid var(--gh-green-700); outline-offset: 2px;`.
- Skip link (`.skip-link`) must be visually hidden using CSS `clip` when not active and jump to `position: fixed` only when focused via keyboard `Tab`.
- Color contrast must meet at least 4.5:1 for body copy and 3:1 for large text.

---

## 5. Backend & Security Standards (PHP / OWASP)
- **Parameterized Queries**: Always use PDO prepared statements with bound parameters (`gh_db()->prepare(...)`). Never concatenate variables into SQL strings.
- **Output Encoding**: Wrap all dynamic user-provided output with `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`.
- **CSRF Protection**: All POST endpoints must include `<input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />` and call `csrf_verify($_POST['_csrf'] ?? null)`.
- **Sanitization**: Filter emails with `FILTER_VALIDATE_EMAIL` and trim all input strings.

---

## 6. Deployment & Hostinger FTP Protocol
- **CRM Deployment**: To upload modified files from `crm_clean/` to production:
  ```bash
  curl.exe -T relative\path\to\file.php ftp://ftp.thegreen.homes/relative/path/to/file.php --user u495244335.u495244335crm:GreenHomesCRM@2026!
  ```
- **Public Portal Testing**: The local development server runs with PHP 8.2 on port 8000:
  ```bash
  C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public public/router.php
  ```
