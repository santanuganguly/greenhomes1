<?php
require_once __DIR__ . '/../../src/view.php';

$ok = false;
$err = null;
$name = $email = $phone = $body = '';
$subject = trim($_GET['subject'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once dirname(__DIR__, 2) . '/src/csrf.php';
    require_once dirname(__DIR__, 2) . '/src/repo/messages.php';
    require_once dirname(__DIR__, 2) . '/src/mail.php';
    csrf_verify($_POST['_csrf'] ?? null);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $body === '') {
        $err = 'Please enter your name, a valid email, and a message.';
    } else {
        $id = messages_create($name, $email, $phone ?: null, $subject ?: null, $body);
        send_mail(
            (string) gh_env('GH_NOTIFY_TO', gh_env('GH_CONTACT_EMAIL', 'contact@thegreen.homes')),
            'Contact form: ' . ($subject ?: 'New message'),
            '<p>New contact form submission:</p><ul>' .
            '<li>Name: ' . htmlspecialchars($name) . '</li>' .
            '<li>Email: ' . htmlspecialchars($email) . '</li>' .
            '<li>Phone: ' . htmlspecialchars($phone ?: 'None') . '</li>' .
            '<li>Subject: ' . htmlspecialchars($subject ?: 'General Inquiry') . '</li>' .
            '<li>Message: ' . nl2br(htmlspecialchars($body)) . '</li>' .
            '</ul>',
            $email
        );
        $ok = true;
        $name = $email = $phone = $subject = $body = '';
    }
}

$companyPhone = (string) gh_env('GH_CONTACT_PHONE', '+91 9046665254');
$emailAddr = (string) gh_env('GH_CONTACT_EMAIL', 'contact@thegreen.homes');
$address  = (string) gh_env('GH_CONTACT_ADDRESS', '5th Floor, Beekay Centerio, Beside Payel Cinema Hall, Sevoke Road, Siliguri, 734001');
$cleanPhone = preg_replace('/[^\d+]/', '', $companyPhone);
$whatsappDigits = preg_replace('/\D/', '', $companyPhone);

gh_header(['title' => 'Contact', 'active' => 'contact', 'css' => 'contact']);
?>
<section class="gh-section contact-section">
  <div class="container">
    <div class="gh-section__head contact-head">
      <span class="gh-eyebrow">Contact</span>
      <h1>Talk to a Senior project manager</h1>
      <p>No sales reps, no scripts. The same person who'll run your project is the person who'll pick up.</p>
    </div>

    <!-- Quick action links for mobile & desktop touch -->
    <div class="contact-quick-bar" aria-label="Direct contact channels">
      <a class="contact-quick-btn" href="tel:<?= htmlspecialchars($cleanPhone) ?>" aria-label="Call <?= htmlspecialchars($companyPhone) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
        </svg>
        <span>Call Directly</span>
      </a>
      <a class="contact-quick-btn contact-quick-btn--whatsapp" href="https://wa.me/<?= htmlspecialchars($whatsappDigits) ?>?text=Hi%2C%20I%20would%20like%20to%20discuss%20a%20home%20construction%20project" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M20.52 3.48A11.93 11.93 0 0 0 12.06 0C5.46 0 .09 5.37.09 11.97c0 2.11.55 4.17 1.6 6L0 24l6.21-1.63a11.95 11.95 0 0 0 5.85 1.51h.01c6.6 0 11.97-5.37 11.97-11.97 0-3.2-.1.25-1.25-6.43-3.43zm-8.46 18.42h-.01a9.93 9.93 0 0 1-5.06-1.39l-.36-.22-3.76.99 1-3.66-.24-.38a9.92 9.92 0 0 1-1.52-5.27c0-5.49 4.47-9.96 9.96-9.96 2.66 0 5.16 1.04 7.04 2.92a9.92 9.92 0 0 1 2.92 7.04c0 5.49-4.47 9.96-9.97 9.96zm5.46-7.46c-.3-.15-1.77-.87-2.04-.97-.28-.1-.48-.15-.68.15-.2.3-.77.97-.95 1.17-.17.2-.35.23-.65.08-.3-.15-1.27-.47-2.42-1.49-.9-.8-1.5-1.78-1.67-2.08-.18-.3-.02-.46.13-.61.14-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.38-.03-.53-.07-.15-.68-1.64-.93-2.25-.24-.59-.49-.51-.68-.52h-.58c-.2 0-.52.08-.8.38-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.2 5.09 4.49.71.31 1.27.49 1.7.63.72.23 1.37.2 1.89.12.58-.09 1.77-.72 2.02-1.42.25-.7.25-1.3.17-1.42-.07-.13-.27-.2-.57-.35z"/>
        </svg>
        <span>Chat on WhatsApp</span>
      </a>
      <a class="contact-quick-btn" href="mailto:<?= htmlspecialchars($emailAddr) ?>" aria-label="Email <?= htmlspecialchars($emailAddr) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect width="20" height="16" x="2" y="4" rx="2"/>
          <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
        </svg>
        <span>Send Email</span>
      </a>
    </div>

    <div class="contact-grid">
      <form class="contact-form gh-form" method="post" action="/contact" novalidate>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
        
        <?php if ($ok): ?>
          <div class="gh-form__ok" role="alert" aria-live="polite">
            <strong>Inquiry logged.</strong> Our engineering team will review your project details and follow up.
          </div>
        <?php endif; ?>
        
        <?php if ($err): ?>
          <div class="gh-form__error" role="alert" aria-live="assertive">
            <?= htmlspecialchars($err) ?>
          </div>
        <?php endif; ?>

        <div class="contact-form__legend">Talk to a Senior Project Manager</div>

        <div class="gh-form__row gh-form__row--2">
          <div class="gh-field">
            <label for="c-name">Full Name <span class="gh-required" aria-hidden="true">*</span></label>
            <input 
              id="c-name" 
              name="name" 
              type="text" 
              required 
              autocomplete="name" 
              enterkeyhint="next"
              placeholder="e.g. Ronit Sharma"
              value="<?= htmlspecialchars($name) ?>" />
          </div>
          <div class="gh-field">
            <label for="c-email">Email Address <span class="gh-required" aria-hidden="true">*</span></label>
            <input 
              id="c-email" 
              name="email" 
              type="email" 
              required 
              autocomplete="email" 
              autocapitalize="none" 
              spellcheck="false" 
              enterkeyhint="next"
              placeholder="name@example.com"
              value="<?= htmlspecialchars($email) ?>" />
          </div>
        </div>

        <div class="gh-form__row gh-form__row--2">
          <div class="gh-field">
            <label for="c-phone">Phone Number</label>
            <input 
              id="c-phone" 
              name="phone" 
              type="tel" 
              autocomplete="tel" 
              inputmode="tel" 
              enterkeyhint="next"
              placeholder="+91 98765 43210" 
              value="<?= htmlspecialchars($phone) ?>" />
          </div>
          <div class="gh-field">
            <label for="c-subject">Subject</label>
            <input 
              id="c-subject" 
              name="subject" 
              type="text" 
              enterkeyhint="next"
              placeholder="e.g. 3 BHK construction in Siliguri" 
              value="<?= htmlspecialchars($subject) ?>" />
          </div>
        </div>

        <div class="gh-field">
          <label for="c-body">Project Details / Message <span class="gh-required" aria-hidden="true">*</span></label>
          <textarea 
            id="c-body" 
            name="body" 
            required 
            enterkeyhint="send"
            placeholder="Tell us about your plot location, expected size, timeline, or any questions you have..."><?= htmlspecialchars($body) ?></textarea>
        </div>

        <button class="gh-btn gh-btn--primary" type="submit">
          <span>Talk to a Senior Project Manager</span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="5" y1="12" x2="19" y2="12"></line>
            <polyline points="12 5 19 12 12 19"></polyline>
          </svg>
        </button>
      </form>

      <aside class="contact-aside">
        <div class="contact-card">
          <h3>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
            Direct Communication
          </h3>
          <p>
            <strong>Phone:</strong><br/>
            <a href="tel:<?= htmlspecialchars($cleanPhone) ?>"><?= htmlspecialchars($companyPhone) ?></a>
          </p>
          <p>
            <strong>Email:</strong><br/>
            <a href="mailto:<?= htmlspecialchars($emailAddr) ?>"><?= htmlspecialchars($emailAddr) ?></a>
          </p>
          <p style="font-size:13px;color:var(--gh-muted);margin-top:10px;">
            Expect a direct callback or WhatsApp reply within 1–2 hours during business hours.
          </p>
        </div>

        <div class="contact-card">
          <h3>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
              <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
            </svg>
            Office &amp; Hours
          </h3>
          <p><?= htmlspecialchars($address) ?></p>
          <p style="font-size:13px;color:var(--gh-muted);margin-top:8px;">
            <strong>Hours:</strong> Mon–Sat · 10:00 AM – 7:00 PM IST
          </p>
        </div>

        <div class="contact-card">
          <h3>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect width="20" height="20" x="2" y="2" rx="5" ry="5"/>
              <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
              <line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>
            </svg>
            Follow Our Projects
          </h3>
          <p style="font-size:13.5px;color:var(--gh-ink-soft);margin-bottom:12px;">See live site construction photos, material walkthroughs, and finished homes:</p>
          <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="https://www.facebook.com/profile.php?id=61593082011180" target="_blank" rel="noopener noreferrer" class="gh-btn gh-btn--sm" style="display:inline-flex;align-items:center;gap:6px;background:rgba(21,83,65,0.08);color:var(--gh-green-900);border:1px solid var(--gh-line);">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
              <span>Facebook</span>
            </a>
            <a href="https://www.instagram.com/greenhomessprojects/" target="_blank" rel="noopener noreferrer" class="gh-btn gh-btn--sm" style="display:inline-flex;align-items:center;gap:6px;background:rgba(21,83,65,0.08);color:var(--gh-green-900);border:1px solid var(--gh-line);">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
              <span>Instagram</span>
            </a>
          </div>
        </div>

        <div class="contact-card">
          <h3>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            Find Our Office
          </h3>
          <div class="contact-map-wrapper">
            <iframe
              title="Green Homes Siliguri Office Location"
              src="https://maps.google.com/maps?q=26.728583,88.430222&amp;hl=en&amp;z=15&amp;output=embed"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
          <a class="contact-map-link" href="https://www.google.com/maps/search/?api=1&query=26.728583,88.430222" rel="noopener" target="_blank">
            <span>Open in Google Maps</span>
            <span aria-hidden="true">→</span>
          </a>
        </div>
      </aside>
    </div>
  </div>
</section>
<?php gh_footer(); ?>
