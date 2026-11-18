<?php
require_once __DIR__ . '/../../src/view.php';
gh_header(['title' => 'Construction Services: Green Homes Siliguri', 'active' => 'services', 'css' => 'services']);
?>
<section class="gh-section">
  <div class="container">
    <div class="gh-section__head">
      <span class="gh-eyebrow">Services</span>
      <h1>Turnkey residential and commercial construction services</h1>
      <p>House designs, government plan approvals, complete construction, and a 5-year warranty, all backed by one clear agreement.</p>
    </div>
  </div>
</section>

<section class="svc" id="home">
  <div class="container svc__grid">
    <div>
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
        <a href="/contact?subject=New+Home+Construction" class="gh-btn gh-btn--ghost">Contact Us</a>
      </div>
    </div>
    <div class="svc__art">
      <img src="/assets/img/service-home-construction.jpg?v=2" alt="Multi-story residential home construction in North Bengal" loading="lazy" style="object-position: center 20%;" />
    </div>
  </div>
</section>

<section class="svc" id="renovation">
  <div class="container svc__grid">
    <div>
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
      <a href="/contact?subject=Home+Renovation" class="gh-btn gh-btn--primary">Plan a Renovation</a>
    </div>
    <div class="svc__art">
      <img src="/assets/img/service-renovation.jpg?v=2" alt="Modern residential kitchen renovation and remodeling" loading="lazy" />
    </div>
  </div>
</section>

<section class="svc" id="commercial">
  <div class="container svc__grid">
    <div>
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
      <a href="/contact?subject=Commercial+Construction" class="gh-btn gh-btn--primary">Request Commercial Proposal</a>
    </div>
    <div class="svc__art">
      <img src="/assets/img/service-commercial.jpg?v=2" alt="Modern corporate office interior construction and fit-out" loading="lazy" />
    </div>
  </div>
</section>

<?php gh_footer(); ?>
