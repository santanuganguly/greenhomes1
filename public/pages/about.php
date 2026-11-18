<?php
require_once __DIR__ . '/../../src/view.php';
gh_header(['title' => 'About Green Homes: Construction Division of Green Hill Group', 'active' => 'about']);
?>

<style>
.about-hero {
  padding: 80px 0 64px;
  background: var(--gh-bg-soft);
  border-bottom: 1px solid var(--gh-line);
}

.about-hero h1 {
  font-size: clamp(34px, 5vw, 50px);
  line-height: 1.15;
  margin-bottom: 20px;
  color: var(--gh-green-900);
  text-wrap: balance;
}

.about-hero p.lead {
  font-size: 18px;
  line-height: 1.6;
  color: var(--gh-ink-soft);
  text-wrap: pretty;
}

.about-img-box {
  border-radius: var(--gh-radius);
  overflow: hidden;
  border: 1px solid var(--gh-line);
  box-shadow: var(--gh-shadow-md);
  aspect-ratio: 4/3;
  position: relative;
}

.about-img-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.4s ease;
}

.about-img-box:hover img {
  transform: scale(1.025);
}

.about-cap-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

@media (max-width: 900px) {
  .about-cap-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 600px) {
  .about-cap-grid { grid-template-columns: 1fr; }
}

.about-cap-card {
  background: #ffffff;
  border: 1px solid var(--gh-line);
  border-radius: var(--gh-radius);
  padding: 24px;
  transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease, border-color 0.2s ease;
}

.about-cap-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--gh-shadow-sm);
  border-color: var(--gh-green-200);
}

.about-cap-card .code {
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--gh-green-700);
  margin-bottom: 10px;
  display: block;
}

.about-cap-card h3 {
  font-size: 17px;
  font-family: var(--font-body);
  font-weight: 600;
  color: var(--gh-green-900);
  margin-bottom: 8px;
}

.about-cap-card p {
  font-size: 14px;
  line-height: 1.55;
  color: var(--gh-ink-soft);
}

.about-cta {
  background: var(--gh-green-900);
  color: #ffffff;
  padding: 80px 0;
  text-align: center;
}

.about-cta h2 {
  color: #ffffff;
  font-size: clamp(28px, 4vw, 40px);
  margin-bottom: 16px;
}

.about-cta p {
  color: rgba(255, 255, 255, 0.82);
  font-size: 16.5px;
  max-width: 620px;
  margin: 0 auto 36px;
  line-height: 1.6;
}
</style>

<section class="about-hero">
  <div class="container">
    <div class="gh-grid gh-grid--2" style="align-items: center; gap: 48px;">
      <div class="reveal reveal-left">
        <span class="gh-eyebrow">Green Homes · Siliguri</span>
        <h1>We build your dream home in North Bengal, from start to finish.</h1>
        <p class="lead">
          As part of <a href="https://greenhillgroup.in" target="_blank" rel="noopener" style="font-weight:600; text-decoration: underline;">Green Hill Group</a>, we bring 15 years of trusted local building experience to your project. You bring the vision and the plot; we take care of the design, legal paperwork, and complete construction.
        </p>
      </div>
      <div class="reveal reveal-right">
        <div class="about-img-box">
          <img src="/assets/img/about-home-evening.jpg?v=1" alt="Turnkey custom villa architecture in Siliguri" style="object-position: center 65%;" />
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Heritage Section -->
<section class="gh-section">
  <div class="container">
    <div class="gh-grid gh-grid--2" style="gap: 48px; align-items: center;">
      <div class="about-img-box reveal reveal-left">
        <img src="/assets/img/about-home-daytime.jpg?v=1" alt="Modern residential home courtyard and exterior in North Bengal" loading="lazy" />
      </div>
      <div class="reveal reveal-right">
        <span class="gh-eyebrow">Development Track Record</span>
        <h2 style="margin-bottom: 18px; font-size: clamp(26px, 3.5vw, 36px);">15 Years of Trusted Building in North Bengal</h2>
        <p style="font-size: 15.5px; line-height: 1.65; color: var(--gh-ink-soft);">
          Since <strong style="color: var(--gh-green-700);">2010</strong>, Green Hill Group has built <strong style="color: var(--gh-green-700);">10 major residential communities</strong> across the region, including <strong style="color: var(--gh-green-700);">Green Escape</strong>, <strong style="color: var(--gh-green-700);">Landmark Residency</strong>, and <strong style="color: var(--gh-green-700);">Green Aura</strong>.
        </p>
        <p style="font-size: 15.5px; line-height: 1.65; color: var(--gh-ink-soft); margin-top: 14px;">
          With <strong style="color: var(--gh-green-700);">Green Homes</strong>, we bring that same experience to your private plot. You get big-project construction quality, lower material costs through our bulk buying power, and one team fully accountable for building your custom home or villa.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- Engineering Standards Grid -->
<section class="gh-section gh-section--soft">
  <div class="container">
    <div class="gh-section__head reveal reveal-up">
      <span class="gh-eyebrow">Standards</span>
      <h2>How We Build Safe &amp; Strong Homes</h2>
      <p>We follow strict safety rules and give you guarantees to protect your home.</p>
    </div>
    <div class="about-cap-grid reveal-stagger">
      <div class="about-cap-card">
        <span class="code">Standard 01 / Structural</span>
        <h3>Earthquake-Safe Design</h3>
        <p>We test the soil and build strong frames so your house stays safe during earthquakes.</p>
      </div>
      <div class="about-cap-card">
        <span class="code">Standard 02 / Sanctions</span>
        <h3>Government Approvals</h3>
        <p>We handle all the building permits, drawings, and legal paperwork with local offices for you.</p>
      </div>
      <div class="about-cap-card">
        <span class="code">Standard 03 / Procurement</span>
        <h3>Certified Raw Materials</h3>
        <p>We only use top-grade steel, cement, and factory-tested pipes that come with quality proof.</p>
      </div>
      <div class="about-cap-card">
        <span class="code">Standard 04 / Transparency</span>
        <h3>Daily Photo Updates</h3>
        <p>We send you daily photos of your house and material deliveries on your phone so you always know what's happening.</p>
      </div>
      <div class="about-cap-card">
        <span class="code">Standard 05 / Verification</span>
        <h3>470-Point Safety Check</h3>
        <p>Before handing over the keys, experts check 470 things, like water leaks, wiring, and wall strength.</p>
      </div>
      <div class="about-cap-card">
        <span class="code">Standard 06 / Warranty</span>
        <h3>5-Year Guarantee</h3>
        <p>You get a written 5-year warranty that covers the foundation, main structure, and walls against any building defects.</p>
      </div>
    </div>
  </div>
</section>

<!-- Clean Closing Section -->
<section class="about-cta">
  <div class="container">
    <h2>Ready to build on your land?</h2>
    <p>
      Share your plot location and spatial requirements. Our senior engineering team will provide a transparent per-square-foot cost breakdown and feasibility review.
    </p>
    <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
      <a href="/estimator" class="gh-btn gh-btn--gold gh-btn--lg">Calculate Cost Estimate</a>
      <a href="/contact" class="gh-btn gh-btn--ghost gh-btn--lg" style="color: #ffffff; border-color: rgba(255, 255, 255, 0.4);">Speak with an Engineer</a>
    </div>
  </div>
</section>

<?php gh_footer(); ?>
