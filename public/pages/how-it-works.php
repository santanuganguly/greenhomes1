<?php
require_once __DIR__ . '/../../src/view.php';
gh_header(['title' => 'How it Works: Construction Process', 'active' => 'how', 'css' => 'home']);
?>
<section class="gh-section">
  <div class="container">
    <div class="gh-section__head reveal reveal-up">
      <span class="gh-eyebrow">Construction Process</span>
      <h1>From plot survey to key handover</h1>
      <p>Six structured milestones with locked per-sqft pricing, contractual completion dates, and single-point engineering accountability.</p>
    </div>

    <div class="process reveal-stagger">
      <!-- Stage 01 -->
      <div class="step">
        <div class="step__header">
          <span class="step__code">Stage 01 · Requirement</span>
          <div class="step__num">01</div>
        </div>
        <h3>Share your requirement</h3>
        <p>20-minute call or a quick form. We listen first, no sales pitch. You tell us about your plot, your family, your must-haves, and your timeline. We tell you honestly if we're a fit.</p>
        <ul class="step__deliverables">
          <li>20-minute discovery call or form</li>
          <li>Plot, family &amp; timeline review</li>
          <li>Honest fit &amp; feasibility assessment</li>
        </ul>
      </div>

      <!-- Stage 02 -->
      <div class="step">
        <div class="step__header">
          <span class="step__code">Stage 02 · Design</span>
          <div class="step__num">02</div>
        </div>
        <h3>Site visit &amp; design</h3>
        <p>Our architect visits the plot, measures, and prepares concept drawings and 2D/3D plans. Two rounds of revisions are included before we lock the design.</p>
        <ul class="step__deliverables">
          <li>On-site plot inspection &amp; measurements</li>
          <li>Concept drawings &amp; 2D/3D floor plans</li>
          <li>Two revision rounds included</li>
        </ul>
      </div>

      <!-- Stage 03 -->
      <div class="step">
        <div class="step__header">
          <span class="step__code">Stage 03 · Contract</span>
          <div class="step__num">03</div>
        </div>
        <h3>Fixed-price contract</h3>
        <p>You receive a per-sqft quote with a complete bill of materials, a milestone payment schedule, and a guaranteed handover date. Your dedicated project manager is named in the contract.</p>
        <ul class="step__deliverables">
          <li>Per-sqft quote with complete bill of materials</li>
          <li>Milestone-based payment schedule</li>
          <li>Named project manager &amp; handover date</li>
        </ul>
      </div>

      <!-- Stage 04 -->
      <div class="step">
        <div class="step__header">
          <span class="step__code">Stage 04 · Construction</span>
          <div class="step__num">04</div>
        </div>
        <h3>Construction</h3>
        <p>Daily site photos and weekly quality-check reports land in your dashboard. Material deliveries are photographed on arrival. Milestone payments release only after you sign off on each phase.</p>
        <ul class="step__deliverables">
          <li>Daily site photos &amp; weekly quality reports</li>
          <li>Delivery verification photographs</li>
          <li>Payments release only after your sign-off</li>
        </ul>
      </div>

      <!-- Stage 05 -->
      <div class="step">
        <div class="step__header">
          <span class="step__code">Stage 05 · Audit</span>
          <div class="step__num">05</div>
        </div>
        <h3>Independent quality audit</h3>
        <p>Before handover, an independent auditor runs 470+ quality checks: concrete, steel, plumbing pressure, electrical, waterproofing, finishes. Snags are fixed at our cost, not yours.</p>
        <ul class="step__deliverables">
          <li>470+ independent quality check points</li>
          <li>Concrete, plumbing, electrical &amp; waterproofing</li>
          <li>All snags rectified at our cost</li>
        </ul>
      </div>

      <!-- Stage 06 -->
      <div class="step">
        <div class="step__header">
          <span class="step__code">Stage 06 · Handover</span>
          <div class="step__num">06</div>
        </div>
        <h3>Move in &amp; warranty</h3>
        <p>Keys, appliance manuals, finish schedules, and warranty cards. A comprehensive 5-year warranty, and a direct phone line to your PM for the first year.</p>
        <ul class="step__deliverables">
          <li>Keys, appliance manuals &amp; finish schedules</li>
          <li>Comprehensive 5-year warranty</li>
          <li>Direct phone line to your PM for year one</li>
        </ul>
      </div>
    </div>

    <!-- Actions -->
    <div class="gh-mt-8 gh-text-center reveal reveal-up" style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; margin-top: 48px;">
      <a href="/estimator" class="gh-btn gh-btn--primary gh-btn--lg">Estimate Project Cost</a>
      <a href="/contact?subject=Technical+Consultation" class="gh-btn gh-btn--ghost gh-btn--lg">Schedule Site Consultation</a>
    </div>
  </div>
</section>
<?php gh_footer(); ?>
