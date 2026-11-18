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
          <span class="step__code">Stage 01 · Feasibility</span>
          <div class="step__num">01</div>
        </div>
        <h3>Plot Survey &amp; Soil Testing</h3>
        <p>Our engineer visits your plot to test the soil, measure exact boundaries, and check SMC or Panchayat building rules before planning begins.</p>
        <ul class="step__deliverables">
          <li>Soil strength test report</li>
          <li>Land boundary &amp; level check</li>
          <li>Rough starting budget estimate</li>
        </ul>
      </div>

      <!-- Stage 02 -->
      <div class="step">
        <div class="step__header">
          <span class="step__code">Stage 02 · Design</span>
          <div class="step__num">02</div>
        </div>
        <h3>Architectural Plans &amp; 3D Elevations</h3>
        <p>Our architect visits the plot, measures, and prepares concept drawings and 2D/3D plans. Two rounds of revisions are included before we lock the design.</p>
        <ul class="step__deliverables">
          <li>Custom 2D room floor plans</li>
          <li>Realistic 3D front house views</li>
          <li>Pillar &amp; beam structural drawings</li>
        </ul>
      </div>

      <!-- Stage 03 -->
      <div class="step">
        <div class="step__header">
          <span class="step__code">Stage 03 · Approvals</span>
          <div class="step__num">03</div>
        </div>
        <h3>Sanctions &amp; Fixed-Price Contract</h3>
        <p>You receive a per-sqft quote with a complete bill of materials, a milestone payment schedule, and a guaranteed handover date. Your dedicated project manager is named in the contract.</p>
        <ul class="step__deliverables">
          <li>Complete SMC/Panchayat plan approval</li>
          <li>Fixed material list with brand names</li>
          <li>Guaranteed timeline &amp; payment stages</li>
        </ul>
      </div>

      <!-- Stage 04 -->
      <div class="step">
        <div class="step__header">
          <span class="step__code">Stage 04 · Construction</span>
          <div class="step__num">04</div>
        </div>
        <h3>Civil Construction &amp; RCC Framing</h3>
        <p>Daily site photos and weekly quality-check reports land in your dashboard. Material deliveries are photographed on arrival. Milestone payments release only after you sign off on each phase.</p>
        <ul class="step__deliverables">
          <li>Certified steel &amp; concrete strength tests</li>
          <li>Hidden inside-wall wiring &amp; pipework</li>
          <li>Weekly progress photos on your dashboard</li>
        </ul>
      </div>

      <!-- Stage 05 -->
      <div class="step">
        <div class="step__header">
          <span class="step__code">Stage 05 · Quality</span>
          <div class="step__num">05</div>
        </div>
        <h3>470-Point Engineering Audit</h3>
        <p>Before handover, an independent inspector runs 470+ quality checks covering structure, plumbing, wiring, waterproofing, and finishing. If anything isn't right, we fix it at our cost.</p>
        <ul class="step__deliverables">
          <li>470+ point quality inspection report</li>
          <li>Water pipe leak &amp; pressure testing</li>
          <li>Bathroom &amp; roof waterproofing checks</li>
        </ul>
      </div>

      <!-- Stage 06 -->
      <div class="step">
        <div class="step__header">
          <span class="step__code">Stage 06 · Handover</span>
          <div class="step__num">06</div>
        </div>
        <h3>Key Handover &amp; 5-Year Warranty</h3>
        <p>Keys, municipal completion documents, electrical circuit diagrams, plumbing maps, and a signed 5-year structural warranty certificate protecting your home.</p>
        <ul class="step__deliverables">
          <li>House keys &amp; completion certificate</li>
          <li>Final wiring &amp; plumbing layout maps</li>
          <li>Signed 5-year structural warranty</li>
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
