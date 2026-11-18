<?php
require_once __DIR__ . '/../../src/view.php';
$user = current_user();
gh_header(['title' => 'Cost Estimator', 'active' => 'estimator', 'css' => 'estimator', 'js' => 'estimator']);
?>
<section class="estimator">
  <div class="container">
    <div class="estimator__head">
      <span class="gh-eyebrow">Cost estimator</span>
      <h1>Get a realistic construction estimate in 60 seconds</h1>
      <p style="color:var(--gh-ink-soft);margin-top:12px;">Four quick questions. We use the same rates our contracts are priced at, based on 120+ projects in North Bengal.</p>
    </div>

    <div class="estimator__steps" id="estSteps">
      <div class="estimator__step-dot is-active" data-step="0"></div>
      <div class="estimator__step-dot" data-step="1"></div>
      <div class="estimator__step-dot" data-step="2"></div>
      <div class="estimator__step-dot" data-step="3"></div>
      <div class="estimator__step-dot" data-step="4"></div>
    </div>

    <div class="estimator__step-title" id="estStepTitle" style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--gh-green-700); margin-bottom: 20px; text-align: center;">Step 1 of 4: What are you building?</div>

    <form class="estimator__panel" id="estForm">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />

      <!-- Step 1: BHK / type -->
      <div class="estimator__step is-active" data-step="0">
        <h2>What are you building?</h2>
        <p class="help">Pick the option closest to your project. You can refine later.</p>
        <div class="option-grid option-grid--3">
          <div class="option">
            <input type="radio" name="bhk_type" id="bhk-2" value="2BHK" />
            <label for="bhk-2"><span class="option__title">2 BHK</span><span class="option__sub">~1,000–1,300 sqft</span></label>
          </div>
          <div class="option">
            <input type="radio" name="bhk_type" id="bhk-3" value="3BHK" checked />
            <label for="bhk-3"><span class="option__title">3 BHK</span><span class="option__sub">~1,500–2,000 sqft</span></label>
          </div>
          <div class="option">
            <input type="radio" name="bhk_type" id="bhk-4" value="4BHK+" />
            <label for="bhk-4"><span class="option__title">4 BHK +</span><span class="option__sub">~2,500+ sqft</span></label>
          </div>
          <div class="option">
            <input type="radio" name="bhk_type" id="bhk-villa" value="villa" />
            <label for="bhk-villa"><span class="option__title">Villa</span><span class="option__sub">3 floors, custom</span></label>
          </div>
          <div class="option">
            <input type="radio" name="bhk_type" id="bhk-duplex" value="duplex" />
            <label for="bhk-duplex"><span class="option__title">Duplex</span><span class="option__sub">2 units, 1 plot</span></label>
          </div>
          <div class="option">
            <input type="radio" name="bhk_type" id="bhk-custom" value="custom" />
            <label for="bhk-custom"><span class="option__title">Custom / other</span><span class="option__sub">Tell us more</span></label>
          </div>
        </div>
      </div>

      <!-- Step 2: Plot size -->
      <div class="estimator__step" data-step="1">
        <h2>What's the built-up area?</h2>
        <p class="help">Total square feet across all floors. Roughly: built-up = carpet area × 1.3.</p>
        <div class="gh-field">
          <label for="est-sqft">Built-up area (sqft)</label>
          <input type="number" id="est-sqft" name="plot_size" min="500" max="20000" step="100" value="1800" required style="font-size: 18px; font-weight: 700; color: var(--gh-green-900); text-align: center;" />
          <div style="margin-top: 16px; padding: 0 4px;">
            <input type="range" id="est-sqft-slider" min="500" max="10000" step="100" value="1800" style="width: 100%; height: 6px; background: var(--gh-line); border-radius: 3px; cursor: pointer; accent-color: var(--gh-green-700);" />
            <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--gh-muted); margin-top: 8px;">
              <span>500 sqft</span>
              <span>5,000 sqft</span>
              <span>10,000 sqft</span>
            </div>
          </div>
        </div>
        <div class="gh-mt-4" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
          <button type="button" class="gh-btn gh-btn--ghost gh-btn--sm" data-sqft="1000">1,000</button>
          <button type="button" class="gh-btn gh-btn--ghost gh-btn--sm" data-sqft="1500">1,500</button>
          <button type="button" class="gh-btn gh-btn--ghost gh-btn--sm" data-sqft="2000">2,000</button>
          <button type="button" class="gh-btn gh-btn--ghost gh-btn--sm" data-sqft="3000">3,000</button>
          <button type="button" class="gh-btn gh-btn--ghost gh-btn--sm" data-sqft="4500">4,500</button>
        </div>
      </div>

      <!-- Step 3: Package -->
      <div class="estimator__step" data-step="2">
        <h2>Which package fits?</h2>
        <p class="help">All packages include design, materials, labor, and project management. Higher tiers add finish quality and customisation.</p>
        <div class="option-grid option-grid--2" style="grid-template-columns:1fr 1fr;">
          <div class="option">
            <input type="radio" name="package" id="pk-essential" value="essential" />
            <label for="pk-essential" style="align-items: flex-start; text-align: left; padding: 20px;">
              <span class="option__title" style="font-size: 16px;">Essential Plan</span>
              <span class="option__sub" style="font-weight: 700; color: var(--gh-green-700); margin-top: 4px; font-size: 14px;">₹1,950 / sqft</span>
              <ul style="margin-top: 10px; padding-left: 16px; font-size: 11px; color: var(--gh-ink-soft); line-height: 1.4; list-style-type: disc;">
                <li>Affordable, Reliable &amp; Functional</li>
                <li>Standard brick, sand &amp; cement</li>
                <li>Inclusive of GST</li>
              </ul>
            </label>
          </div>
          <div class="option">
            <input type="radio" name="package" id="pk-comfort" value="comfort" checked />
            <label for="pk-comfort" style="align-items: flex-start; text-align: left; padding: 20px;">
              <span class="option__title" style="font-size: 16px;">Comfort Plan <span class="gh-pill gh-pill--gold" style="margin-left:6px;">Popular</span></span>
              <span class="option__sub" style="font-weight: 700; color: var(--gh-green-700); margin-top: 4px; font-size: 14px;">₹2,100 / sqft</span>
              <ul style="margin-top: 10px; padding-left: 16px; font-size: 11px; color: var(--gh-ink-soft); line-height: 1.4; list-style-type: disc;">
                <li>Balanced Design &amp; Quality</li>
                <li>Premium sand, cement &amp; steel</li>
                <li>Inclusive of GST</li>
              </ul>
            </label>
          </div>
          <div class="option">
            <input type="radio" name="package" id="pk-elegance" value="elegance" />
            <label for="pk-elegance" style="align-items: flex-start; text-align: left; padding: 20px;">
              <span class="option__title" style="font-size: 16px;">Elegance Plan</span>
              <span class="option__sub" style="font-weight: 700; color: var(--gh-green-700); margin-top: 4px; font-size: 14px;">₹2,250 / sqft</span>
              <ul style="margin-top: 10px; padding-left: 16px; font-size: 11px; color: var(--gh-ink-soft); line-height: 1.4; list-style-type: disc;">
                <li>Stylish, High Quality Finishes</li>
                <li>Full modular kitchen &amp; chimneys</li>
                <li>Inclusive of GST</li>
              </ul>
            </label>
          </div>
          <div class="option">
            <input type="radio" name="package" id="pk-signature" value="signature" />
            <label for="pk-signature" style="align-items: flex-start; text-align: left; padding: 20px;">
              <span class="option__title" style="font-size: 16px;">Signature Plan</span>
              <span class="option__sub" style="font-weight: 700; color: var(--gh-green-700); margin-top: 4px; font-size: 14px;">₹2,500 / sqft</span>
              <ul style="margin-top: 10px; padding-left: 16px; font-size: 11px; color: var(--gh-ink-soft); line-height: 1.4; list-style-type: disc;">
                <li>Premium Stylish &amp; High Quality Finishes</li>
                <li>Smart home automation &amp; luxury fittings</li>
                <li>Inclusive of GST</li>
              </ul>
            </label>
          </div>
        </div>
      </div>

      <!-- Step 4: City -->
      <div class="estimator__step" data-step="3">
        <h2>Where is the project?</h2>
        <p class="help">We currently build across North Bengal.</p>
        <div class="gh-field">
          <label for="est-city">City</label>
          <select id="est-city" name="city" required>
            <option value="Siliguri" selected>Siliguri</option>
            <option value="Matigara">Matigara</option>
            <option value="Bagdogra">Bagdogra</option>
            <option value="Jalpaiguri">Jalpaiguri</option>
            <option value="Malbazar">Malbazar</option>
            <option value="Alipurduar">Alipurduar</option>
            <option value="Darjeeling">Darjeeling</option>
            <option value="Kurseong">Kurseong</option>
            <option value="Kalimpong">Kalimpong</option>
            <option value="Other">Other (tell us)</option>
          </select>
        </div>
      </div>

      <!-- Step 5: Result -->
      <div class="estimator__step" data-step="4">
        <h2>Your estimate</h2>
        <p class="help">A preliminary range based on our standard rates. The final quote depends on site conditions, design, and finishes — we'll firm it up after a free site visit.</p>
        <div id="estResult" class="estimate-result" style="display:none;">
          <h3>Estimated construction cost</h3>
          <div class="estimate-result__range" id="estRange">—</div>
          <div class="estimate-result__sub" id="estSub">—</div>
          <div style="margin-top:24px;">
            <div class="estimate-result__row"><span>Package</span><span id="estPkg">—</span></div>
            <div class="estimate-result__row"><span>Built-up area</span><span id="estSqft">—</span></div>
            <div class="estimate-result__row"><span>City</span><span id="estCityOut">—</span></div>
            <div class="estimate-result__row"><span>Type</span><span id="estBhk">—</span></div>
          </div>
        </div>
        <div id="estError" style="display:none;" class="gh-form__error"></div>
        <div class="estimate-result__actions" id="estActions" style="display:none;">
          <?php if ($user): ?>
            <a href="/quote/new" class="gh-btn gh-btn--gold gh-btn--lg" id="estQuoteBtn">Save &amp; request a quote →</a>
          <?php else: ?>
            <a href="/login?next=/quote/new" class="gh-btn gh-btn--gold gh-btn--lg">Log in to save &amp; request a quote →</a>
          <?php endif; ?>
          <a href="/contact" class="gh-btn gh-btn--ghost gh-btn--lg" style="background:transparent;border-color:rgba(255,255,255,.6);color:#fff;">Talk to an expert</a>
          <button type="button" class="gh-btn gh-btn--ghost gh-btn--lg" id="estRestart" style="background:transparent;border-color:rgba(255,255,255,.6);color:#fff;">Start over</button>
        </div>
      </div>

      <div class="estimator__nav">
        <button type="button" class="gh-btn gh-btn--ghost" id="estPrev" style="visibility:hidden;">← Back</button>
        <button type="button" class="gh-btn gh-btn--primary" id="estNext">Next →</button>
      </div>
    </form>
  </div>
</section>
<?php gh_footer(); ?>
