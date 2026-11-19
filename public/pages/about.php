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

.approach-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
  max-width: 960px;
  margin: 0 auto;
}

@media (max-width: 768px) {
  .approach-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}

.approach-card {
  background: #ffffff;
  border: 1px solid var(--gh-line);
  border-radius: var(--gh-radius);
  padding: 24px;
  transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease, border-color 0.2s ease;
}

.approach-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--gh-shadow-sm);
  border-color: var(--gh-green-200);
}

.approach-card__icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  color: var(--gh-green-700);
  margin-bottom: 14px;
}

.approach-card__icon svg {
  width: 28px;
  height: 28px;
  stroke: var(--gh-green-700);
  stroke-width: 2;
}

.approach-card h3 {
  font-family: var(--font-body);
  font-size: 17px;
  font-weight: 600;
  color: var(--gh-green-900);
  margin-bottom: 8px;
}

.approach-card p {
  font-size: 14px;
  line-height: 1.55;
  color: var(--gh-ink-soft);
  margin: 0;
}

.about-cta {
  position: relative;
  padding: 104px 0;
  text-align: center;
  color: #ffffff;
  background: linear-gradient(rgba(10, 30, 22, 0.82), rgba(10, 30, 22, 0.88)), url('/assets/img/about-home-evening.jpg?v=2') center 40% / cover no-repeat;
  overflow: hidden;
  border-top: 1px solid var(--gh-line);
}

.about-cta h2 {
  color: #ffffff;
  font-family: var(--font-display);
  font-size: clamp(30px, 4.5vw, 44px);
  line-height: 1.2;
  max-width: 720px;
  margin: 0 auto 18px;
  text-wrap: balance;
}

.about-cta p.about-cta__lead {
  color: rgba(255, 255, 255, 0.9);
  font-size: 16.5px;
  max-width: 680px;
  margin: 0 auto 20px;
  line-height: 1.65;
}

.about-cta__tagline {
  color: var(--gh-gold);
  font-size: 15px;
  font-weight: 500;
  margin: 0 auto 36px;
  display: block;
}

.about-cta__tagline a {
  color: inherit;
  text-decoration: underline;
  font-weight: 600;
}

.about-cta .gh-btn--ghost {
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.5);
  background: transparent;
}

.about-cta .gh-btn--ghost:hover {
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
  border-color: #ffffff;
}
</style>

<section class="about-hero">
  <div class="container">
    <div class="gh-grid gh-grid--2" style="align-items: center; gap: 48px;">
      <div class="reveal reveal-left">
        <span class="gh-eyebrow">Green Homes · Siliguri</span>
        <h1>From Land to Landmark<br />We Build It All</h1>
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

<!-- Our Approach Section -->
<section class="gh-section gh-section--soft">
  <div class="container">
    <div class="gh-section__head reveal reveal-up">
      <span class="gh-eyebrow">Our Approach</span>
      <h2>Built Around Your Needs</h2>
      <p>Every project starts with understanding your requirements and ends with a space built to match your vision.</p>
    </div>
    <div class="approach-grid reveal-stagger">
      <div class="approach-card">
        <div class="approach-card__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="5" r="2"/>
            <path d="m3.9 19.5 6.3-13"/>
            <path d="m20.1 19.5-6.3-13"/>
            <path d="M6.8 14h10.4"/>
          </svg>
        </div>
        <h3>Thoughtful Planning</h3>
        <p>Every detail is considered before construction begins.</p>
      </div>

      <div class="approach-card">
        <div class="approach-card__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <h3>Dedicated Management</h3>
        <p>We coordinate materials, teams and on-site work.</p>
      </div>

      <div class="approach-card">
        <div class="approach-card__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </div>
        <h3>Complete Transparency</h3>
        <p>Clear costing and regular project updates.</p>
      </div>

      <div class="approach-card">
        <div class="approach-card__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            <path d="m9 12 2 2 4-4"/>
          </svg>
        </div>
        <h3>Quality at Every Stage</h3>
        <p>Attention to workmanship and finishing throughout.</p>
      </div>
    </div>
  </div>
</section>

<!-- Closing CTA Section -->
<section class="about-cta">
  <div class="container reveal reveal-up">
    <h2>Build Your Dream Home, On Your Own Land</h2>
    <p class="about-cta__lead">
      Your land deserves more than just a structure: it deserves a home built with passion, precision, and purpose. With Green Homes, you bring the dream, and we bring the expertise to make it a reality.
    </p>
    <div class="about-cta__tagline">
      Green Homes by <a href="https://greenhillgroup.in" target="_blank" rel="noopener">Green Hill Group</a> · Building Dreams, One Home at a Time.
    </div>
    <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
      <a href="/estimator" class="gh-btn gh-btn--gold gh-btn--lg">Calculate cost</a>
      <a href="/contact" class="gh-btn gh-btn--ghost gh-btn--lg">Talk to us</a>
    </div>
  </div>
</section>

<?php gh_footer(); ?>
