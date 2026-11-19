<?php
require_once __DIR__ . '/../../src/view.php';

$leadOk = false;
$leadError = null;
$leadName = $leadPhone = $leadCity = '';
$leadService = $_GET['service'] ?? 'home';

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
            'notes' => $notes ?: 'Services page consultation request',
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
            '<p>New consultation request from Services page:</p><ul><li>Name: ' . htmlspecialchars($leadName) . '</li><li>Phone: ' . htmlspecialchars($leadPhone) . '</li><li>City: ' . htmlspecialchars($leadCity) . '</li><li>Service: ' . htmlspecialchars($leadService) . '</li><li>Notes: ' . nl2br(htmlspecialchars($notes)) . '</li></ul><p>Lead ID: ' . ($id ?: 'N/A') . '</p>',
            $leadPhone
        );
        require_once dirname(__DIR__, 2) . '/src/google_sheets.php';
        gh_sheets_log_lead_quote(array_merge($leadData, ['id' => $id]));
        $leadOk = true;
        $leadName = $leadPhone = $leadCity = '';
        $leadService = 'home';
    }
}

gh_header(['title' => 'Construction Services: Green Homes Siliguri', 'active' => 'services', 'css' => 'services']);
?>
<!-- Top Clean Header Section -->
<section class="gh-section">
  <div class="container">
    <div class="gh-section__head reveal reveal-up">
      <span class="gh-eyebrow">Services · North Bengal</span>
      <h1>Turnkey residential and commercial construction services</h1>
      <p>House designs, government plan approvals, complete construction, and a 5-year warranty, all backed by one clear agreement.</p>
    </div>
  </div>
</section>

<!-- 01. Residential Construction -->
<section class="svc" id="home">
  <div class="container svc__grid">
    <div class="reveal reveal-left">
      <span class="gh-eyebrow">Residential Construction</span>
      <h2>Design-to-build homes across North Bengal</h2>
      <p>Custom villas, duplexes, and independent residences built on your land with a locked per-sqft contract. A certified project engineer coordinates all trades on site from foundation to handover.</p>
      <ul class="svc__checklist">
        <li>2D architectural layouts &amp; 3D elevations</li>
        <li>IS 1893 seismic-compliant structural design</li>
        <li>SMC and regional municipal sanction drawings</li>
        <li>Tested Fe550 steel &amp; design-mix concrete</li>
        <li>Daily progress photos &amp; inspection logs</li>
        <li>470 documented quality checks per floor</li>
        <li>5-year written structural warranty</li>
        <li>Documented till we hand you the house</li>
      </ul>
      <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="/estimator" class="gh-btn gh-btn--primary">Calculate Home Estimate</a>
        <a href="#consultation" class="gh-btn gh-btn--ghost" onclick="var s=document.getElementById('svc-service'); if(s) s.value='home';">Book FREE Consultation</a>
      </div>
    </div>
    <div class="svc__art reveal reveal-right">
      <img src="/assets/img/service-home-construction.jpg?v=2" alt="Multi-story residential home construction in North Bengal" loading="lazy" style="object-position: center 20%;" />
    </div>
  </div>
</section>

<!-- 02. Renovation & Remodeling -->
<section class="svc" id="renovation">
  <div class="container svc__grid">
    <div class="reveal reveal-left">
      <span class="gh-eyebrow">Renovation &amp; Remodeling</span>
      <h2>Structural renovation and vertical home expansion</h2>
      <p>Structural retrofits, layout reconfigurations, and modern exterior upgrades for existing residential properties with minimal neighborhood disruption.</p>
      <ul class="svc__checklist">
        <li>Safety checks &amp; pillar strengthening before adding new floors</li>
        <li>Adding extra floors &amp; covered terraces to expand your living space</li>
        <li>Complete bathroom &amp; kitchen modernization</li>
        <li>Modern front makeovers &amp; complete waterproofing against damp walls</li>
        <li>Re-wiring, concealed plumbing &amp; drainage</li>
        <li>Heat &amp; noise protection to keep rooms cooler and quieter</li>
        <li>Restoring old family homes without losing their original charm</li>
        <li>Room-by-room work so your family can keep living inside comfortably</li>
      </ul>
      <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="#consultation" class="gh-btn gh-btn--primary" onclick="var s=document.getElementById('svc-service'); if(s) s.value='renovation';">Plan a Renovation</a>
        <a href="/contact?subject=Home+Renovation" class="gh-btn gh-btn--ghost">Contact Us</a>
      </div>
    </div>
    <div class="svc__art reveal reveal-right">
      <img src="/assets/img/service-renovation.jpg?v=2" alt="Modern residential kitchen renovation and remodeling" loading="lazy" />
    </div>
  </div>
</section>

<!-- 03. Commercial Construction -->
<section class="svc" id="commercial">
  <div class="container svc__grid">
    <div class="reveal reveal-left">
      <span class="gh-eyebrow">Commercial Construction</span>
      <h2>Offices, clinics, and retail commercial builds</h2>
      <p>Turnkey civil and interior delivery for corporate offices, healthcare clinics, retail stores, and commercial complexes adhering to West Bengal fire safety and building bylaws.</p>
      <ul class="svc__checklist">
        <li>Site survey &amp; commercial viability analysis</li>
        <li>Bylaw-compliant architectural design</li>
        <li>HVAC ducting &amp; 3-phase electrical installation</li>
        <li>Fire detection &amp; emergency exit compliance</li>
        <li>Commercial plumbing &amp; sanitary infrastructure</li>
        <li>Floor-by-floor handover so your business can open early</li>
        <li>SJDA and municipal statutory approvals</li>
        <li>Ongoing building maintenance even after we hand over the keys</li>
      </ul>
      <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="#consultation" class="gh-btn gh-btn--primary" onclick="var s=document.getElementById('svc-service'); if(s) s.value='commercial';">Request Commercial Proposal</a>
        <a href="/contact?subject=Commercial+Construction" class="gh-btn gh-btn--ghost">Contact Us</a>
      </div>
    </div>
    <div class="svc__art reveal reveal-right">
      <img src="/assets/img/service-commercial.jpg?v=2" alt="Modern corporate office interior construction and fit-out" loading="lazy" />
    </div>
  </div>
</section>

<!-- Bottom Consultation Section (Exact home-page hero card layout) -->
<section class="svc-consultation" id="consultation">
  <div class="container hero__grid">
    <div class="reveal reveal-left">
      <span class="gh-eyebrow">Consultation · Siliguri &amp; North Bengal</span>
      <h2 style="font-size: clamp(28px, 4vw, 42px); color: var(--gh-green-900); line-height: 1.2; margin-bottom: 16px;">
        Request a site consultation for your property
      </h2>
      <p class="lead" style="font-size: 16.5px; line-height: 1.6; color: var(--gh-ink-soft); max-width: 540px; margin-bottom: 20px;">
        Speak directly with our senior project engineers. We evaluate your plot feasibility, municipal setbacks, and deliver an itemized per-sqft cost estimate with zero hidden charges.
      </p>
      
      <div class="hero__image-wrapper">
        <img src="/assets/img/service-consultation.jpg?v=1" alt="Turnkey commercial and residential architecture in North Bengal" />
      </div>
    </div>
    
    <div class="hero__form-card reveal reveal-right" style="transition-delay: 0.1s;">
      <h3>Request Site Consultation</h3>
      <p style="font-size: 13.5px; color: var(--gh-muted); margin-bottom: 20px;">Same-day feasibility review and preliminary cost estimate.</p>
      
      <form class="gh-form" method="post" action="/services#consultation">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
        <input type="hidden" name="form" value="lead" />
        
        <?php if ($leadOk): ?>
          <div class="gh-form__ok" style="margin-bottom: 16px;">Request received. A senior project manager will call you back.</div>
        <?php elseif ($leadError): ?>
          <div class="gh-form__error" style="margin-bottom: 16px;"><?= htmlspecialchars($leadError) ?></div>
        <?php endif; ?>
        
        <div class="gh-field">
          <label for="svc-name">Full Name</label>
          <input id="svc-name" name="name" type="text" required autocomplete="name" enterkeyhint="next" value="<?= htmlspecialchars($leadName) ?>" placeholder="e.g. Ronit Sharma" />
        </div>
        
        <div class="gh-field" style="margin-top: 14px;">
          <label for="svc-phone">Phone Number</label>
          <input id="svc-phone" name="phone" type="tel" required autocomplete="tel" inputmode="tel" enterkeyhint="next" value="<?= htmlspecialchars($leadPhone) ?>" placeholder="+91 98765 43210" />
        </div>
        
        <div class="gh-field" style="margin-top: 14px;">
          <label for="svc-city">Plot Location (City / Town)</label>
          <input id="svc-city" name="city" type="text" required enterkeyhint="next" value="<?= htmlspecialchars($leadCity) ?>" placeholder="Siliguri, Jalpaiguri, Matigara..." />
        </div>
        
        <div class="gh-field" style="margin-top: 14px;">
          <label for="svc-service">Service Required</label>
          <select id="svc-service" name="service" style="width: 100%; height: 44px; padding: 0 12px; border-radius: var(--gh-radius-sm, 6px); border: 1px solid var(--gh-line); background: #ffffff; color: var(--gh-ink); font-size: 14px;">
            <option value="home" <?= $leadService === 'home' ? 'selected' : '' ?>>New Home Construction</option>
            <option value="renovation" <?= $leadService === 'renovation' ? 'selected' : '' ?>>Home Renovation &amp; Expansion</option>
            <option value="commercial" <?= $leadService === 'commercial' ? 'selected' : '' ?>>Commercial Construction</option>
          </select>
        </div>
        
        <button class="gh-btn gh-btn--primary gh-btn--block gh-btn--lg" type="submit" style="margin-top: 24px; width: 100%;">Book FREE Consultation</button>
        
        <p style="font-size: 12px; color: var(--gh-muted); margin-top: 16px; line-height: 1.4; text-align: center;">
          Direct communication with our senior engineering team. No sales cold calls.
        </p>
      </form>
    </div>
  </div>
</section>

<?php gh_footer(); ?>
