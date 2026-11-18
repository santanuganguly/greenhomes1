<?php
require_once __DIR__ . '/../../src/view.php';

$leadOk = false;
$leadError = null;
$leadName = $leadPhone = $leadCity = '';
$leadService = 'home';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'lead') {
    require_once dirname(__DIR__, 2) . '/src/csrf.php';
    csrf_verify($_POST['_csrf'] ?? null);
    $leadName = trim($_POST['name'] ?? '');
    $leadPhone = trim($_POST['phone'] ?? '');
    $leadCity = trim($_POST['city'] ?? '');
    $leadService = $_POST['service'] ?? 'home';
    $notes = trim($_POST['notes'] ?? '');
    if ($leadName === '' || $leadPhone === '' || $leadCity === '') {
        $leadError = 'Please fill in your name, phone, and city.';
    } else {
        require_once dirname(__DIR__, 2) . '/src/repo/quotes.php';
        require_once dirname(__DIR__, 2) . '/src/mail.php';
        $leadData = [
            'user_id' => null,
            'name' => $leadName,
            'email' => 'lead@webform.local',
            'phone' => $leadPhone,
            'city' => $leadCity,
            'service_type' => in_array($leadService, ['home','renovation','commercial'], true) ? $leadService : 'home',
            'plot_size' => null,
            'bhk_type' => null,
            'budget_band' => null,
            'package' => null,
            'estimate_low' => null,
            'estimate_high' => null,
            'notes' => $notes,
        ];
        $id = null;
        try {
            $id = quotes_create($leadData);
        } catch (PDOException $e) {
            error_log("Database connection failed for lead: " . $e->getMessage());
        }
        send_mail(
            (string) gh_env('GH_NOTIFY_TO', gh_env('GH_CONTACT_EMAIL', 'contact@thegreen.homes')),
            'New consultation request: ' . $leadName . ' (' . $leadCity . ')',
            '<p>New consultation request:</p><ul><li>Name: ' . htmlspecialchars($leadName) . '</li><li>Phone: ' . htmlspecialchars($leadPhone) . '</li><li>City: ' . htmlspecialchars($leadCity) . '</li><li>Service: ' . htmlspecialchars($leadService) . '</li><li>Notes: ' . nl2br(htmlspecialchars($notes)) . '</li></ul><p>Lead ID: ' . ($id ?: 'N/A') . '</p>',
            $leadPhone
        );
        require_once dirname(__DIR__, 2) . '/src/google_sheets.php';
        gh_sheets_log_lead_quote(array_merge($leadData, ['id' => $id]));
        $leadOk = true;
        $leadName = $leadPhone = $leadCity = '';
        $leadService = 'home';
    }
}

gh_header(['title' => 'Green Homes: Turnkey Home Construction in Siliguri', 'active' => 'home', 'css' => 'home', 'transparent' => true]);
?>

<!-- Hero Section -->
<section class="hero">
  <div class="container hero__grid">
    <div class="reveal reveal-left">
      <span class="gh-eyebrow">Siliguri · North Bengal</span>
      <h1>Trusted Home Builders in Siliguri</h1>
      <p class="lead">We handle everything from building plans and government approvals to final handover, with fixed pricing, regular quality checks, and no hidden costs.</p>
      
      <div class="hero__image-wrapper">
        <img src="/assets/img/hero-villa.jpg?v=4" alt="Modern luxury custom villa design and construction in Siliguri" />
      </div>
    </div>
    
    <div class="hero__form-card reveal reveal-right" style="transition-delay: 0.1s;">
      <h3>Request Site Consultation</h3>
      <p style="font-size: 13.5px; color: var(--gh-muted); margin-bottom: 20px;">Same-day feasibility review and preliminary cost estimate.</p>
      
      <form class="gh-form" method="post" action="/">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
        <input type="hidden" name="form" value="lead" />
        
        <?php if ($leadOk): ?>
          <div class="gh-form__ok" style="margin-bottom: 16px;">Request received. A senior project manager will call you back.</div>
        <?php elseif ($leadError): ?>
          <div class="gh-form__error" style="margin-bottom: 16px;"><?= htmlspecialchars($leadError) ?></div>
        <?php endif; ?>
        
        <div class="gh-field">
          <label for="hero-name">Full Name</label>
          <input id="hero-name" name="name" type="text" required autocomplete="name" enterkeyhint="next" value="<?= htmlspecialchars($leadName) ?>" placeholder="e.g. Ronit Sharma" />
        </div>
        
        <div class="gh-field" style="margin-top: 14px;">
          <label for="hero-phone">Phone Number</label>
          <input id="hero-phone" name="phone" type="tel" required autocomplete="tel" inputmode="tel" enterkeyhint="next" value="<?= htmlspecialchars($leadPhone) ?>" placeholder="+91 98765 43210" />
        </div>
        
        <div class="gh-field" style="margin-top: 14px;">
          <label for="hero-city">Plot Location (City / Town)</label>
          <input id="hero-city" name="city" type="text" required enterkeyhint="done" value="<?= htmlspecialchars($leadCity) ?>" placeholder="Siliguri, Jalpaiguri, Matigara..." />
        </div>
        
        <button class="gh-btn gh-btn--primary gh-btn--block gh-btn--lg" type="submit" style="margin-top: 24px; width: 100%;">Book Consultation</button>
        
        <p style="font-size: 12px; color: var(--gh-muted); margin-top: 16px; line-height: 1.4; text-align: center;">
          Direct communication with our senior engineering team. No sales cold calls.
        </p>
      </form>
    </div>
  </div>
</section>

<!-- Services Section -->
<section class="gh-section gh-section--soft">
  <div class="container">
    <div class="gh-section__head reveal reveal-up">
      <span class="gh-eyebrow">Services</span>
      <h2>Complete Plot-to-Home Construction</h2>
      <p>Home design-to-build, plus renovation and commercial work, all under one team.</p>
    </div>
    <div class="gh-grid gh-grid--3 reveal-stagger">
      <a href="/services#home" class="service-card">
        <h3>Home construction</h3>
        <p>Villas, duplexes, and independent bungalows priced per square foot with a dedicated project engineer from site survey to key handover.</p>
        <span class="service-card__cta">Explore home builds</span>
      </a>
      <a href="/services#renovation" class="service-card">
        <h3>Renovation</h3>
        <p>Upgrade your old house with extra rooms, stronger structures, modern layouts, and complete repairs, all managed smoothly by our team.</p>
        <span class="service-card__cta">Plan a renovation</span>
      </a>
      <a href="/services#commercial" class="service-card">
        <h3>Commercial construction</h3>
        <p>Retail stores, clinics, offices, and commercial buildings built on time so you can open for business without costly delays.</p>
        <span class="service-card__cta">Review commercial work</span>
      </a>
    </div>
  </div>
</section>

<!-- Architectural Pillars -->
<section class="gh-section">
  <div class="container">
    <div class="gh-section__head reveal reveal-up">
      <span class="gh-eyebrow">Specifications</span>
      <h2>Engineered for durability and clarity</h2>
      <p>Four structural guarantees built into every contract we sign.</p>
    </div>
    <div class="pillars reveal-stagger">
      <div class="pillar">
        <span class="pillar__code">Spec 01 / Cost</span>
        <h3>Transparent Bill of Quantities</h3>
        <p>Complete line-item material schedules and locked per-sqft rates before construction begins. Zero surprise change orders.</p>
      </div>
      <div class="pillar">
        <span class="pillar__code">Spec 02 / Quality</span>
        <h3>470+ Quality Checkpoints</h3>
        <p>Documented soil testing, concrete grade cube tests, steel binding inspections, and waterproofing sign-offs logged to your portal.</p>
      </div>
      <div class="pillar">
        <span class="pillar__code">Spec 03 / Handover</span>
        <h3>Structured Handover Schedule</h3>
        <p>Milestone schedule with daily site photographs. Late completion incurs contractual credits payable to the client.</p>
      </div>
      <div class="pillar">
        <span class="pillar__code">Spec 04 / Protection</span>
        <h3>5-Year Structural Warranty</h3>
        <p>Written warranty covering structure and workmanship for your home.</p>
      </div>
    </div>
  </div>
</section>

<!-- FAQ Section -->
<section class="gh-section">
  <div class="container">
    <div class="gh-section__head reveal reveal-up">
      <span class="gh-eyebrow">FAQ</span>
      <h2>Common questions, answered</h2>
    </div>
    <div class="faq reveal reveal-up" style="transition-delay: 0.1s;">
      <details open>
        <summary>What is included in a per-sqft quote?</summary>
        <p>Everything: design, materials, labor, plumbing &amp; electrical, doors &amp; windows, finishes, and project management. We send a line-item breakdown with your contract so you can see exactly what you are paying for.</p>
      </details>
      <details>
        <summary>How are payments structured?</summary>
        <p>Milestone-based: a small booking amount, then payments tied to foundation, plinth, lintel, slab, and handover. We do not ask for large advances.</p>
      </details>
      <details>
        <summary>Do you handle permits and approvals?</summary>
        <p>Yes, building permits, municipal drawings, and structural-engineer certification. We work with your local body in Siliguri, Jalpaiguri, and Darjeeling district.</p>
      </details>
      <details>
        <summary>What about quality checks?</summary>
        <p>470+ checks per floor: concrete grade, steel binding, plumbing pressure, electrical continuity, waterproofing, and finish. Every check is photographed and uploaded to your dashboard.</p>
      </details>
      <details>
        <summary>How long does a typical home take?</summary>
        <p>A 2,000 sqft home in our Classic package typically takes 7 to 9 months from breaking ground, weather permitting. Your contract states a guaranteed handover date with delay credits.</p>
      </details>
      <details>
        <summary>Can I supply my own materials?</summary>
        <p>Yes, with our quality team signing off on each batch. We will adjust your contract accordingly. Most clients prefer our supply chain, as it is usually 8 to 12% cheaper than buying retail.</p>
      </details>
      <details>
        <summary>What warranty do you offer?</summary>
        <p>A 5-year warranty on structure and workmanship, along with manufacturer warranties on hardware and fittings, all transferable.</p>
      </details>
      <details>
        <summary>Do you work outside Siliguri?</summary>
        <p>Yes, across North Bengal: Jalpaiguri, Malbazar, Alipurduar, Darjeeling, Kurseong, Kalimpong, and the Dooars. Travel is included in contracts above 1,800 sqft.</p>
      </details>
    </div>
  </div>
</section>

<!-- Lead Request Section -->
<section class="lead-band" id="lead">
  <div class="container lead-band__grid">
    <div class="reveal reveal-left">
      <span class="gh-eyebrow" style="color:var(--gh-green-200);">Consultation</span>
      <h2>Discuss your plot and construction goals with a project engineer.</h2>
      <p>Direct technical review. We evaluate your plot dimensions and provide preliminary feasibility estimates.</p>
      <ul style="margin-top:24px;list-style:none;display:flex;flex-direction:column;gap:10px;color:rgba(255,255,255,.9);font-size:14.5px;">
        <li>✓ Fixed-price proposal delivered within 7 days of site inspection</li>
        <li>✓ Complimentary initial plot consultation</li>
        <li>✓ Direct consultation with a licensed civil engineer</li>
      </ul>
    </div>
    <form class="gh-form gh-card reveal reveal-right" style="transition-delay: 0.1s;" method="post" action="#lead">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
      <input type="hidden" name="form" value="lead" />
      <?php if ($leadOk): ?>
        <div class="gh-form__ok">Consultation request logged. We will reach out shortly.</div>
      <?php elseif ($leadError): ?>
        <div class="gh-form__error"><?= htmlspecialchars($leadError) ?></div>
      <?php endif; ?>
      <div class="gh-form__row gh-form__row--2">
        <div class="gh-field">
          <label for="lh-name">Full Name</label>
          <input id="lh-name" name="name" type="text" required autocomplete="name" value="<?= htmlspecialchars($leadName) ?>" placeholder="Your name" />
        </div>
        <div class="gh-field">
          <label for="lh-phone">Phone Number</label>
          <input id="lh-phone" name="phone" type="tel" required autocomplete="tel" inputmode="tel" value="<?= htmlspecialchars($leadPhone) ?>" placeholder="+91 …" />
        </div>
      </div>
      <div class="gh-form__row gh-form__row--2">
        <div class="gh-field">
          <label for="lh-city">City / Area</label>
          <input id="lh-city" name="city" type="text" required value="<?= htmlspecialchars($leadCity) ?>" placeholder="Siliguri, Jalpaiguri…" />
        </div>
        <div class="gh-field">
          <label for="lh-service">Construction Type</label>
          <select id="lh-service" name="service">
            <option value="home" <?= $leadService === 'home' ? 'selected' : '' ?>>New Home Construction</option>
            <option value="renovation" <?= $leadService === 'renovation' ? 'selected' : '' ?>>Residential Renovation</option>
            <option value="commercial" <?= $leadService === 'commercial' ? 'selected' : '' ?>>Commercial Build</option>
          </select>
        </div>
      </div>
      <div class="gh-field">
        <label for="lh-notes">Project Details (Optional)</label>
        <textarea id="lh-notes" name="notes" placeholder="Plot area, intended BHK, approximate timeline..."></textarea>
      </div>
      <button class="gh-btn gh-btn--gold gh-btn--block gh-btn--lg" type="submit">Submit Request</button>
    </form>
  </div>
</section>

<?php gh_footer(); ?>
