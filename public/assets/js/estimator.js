// Cost estimator — multi-step form, posts to /api/estimate.
(function () {
  'use strict';
  const form = document.getElementById('estForm');
  if (!form) return;

  const stepEls = form.querySelectorAll('.estimator__step');
  const dots = document.querySelectorAll('#estSteps .estimator__step-dot');
  const prevBtn = document.getElementById('estPrev');
  const nextBtn = document.getElementById('estNext');
  const TOTAL = stepEls.length - 1; // last step is the result
  let current = 0;

  const stepTitle = document.getElementById('estStepTitle');
  const stepTitles = [
    'Step 1 of 4: What are you building?',
    'Step 2 of 4: What\'s the built-up area?',
    'Step 3 of 4: Which package fits?',
    'Step 4 of 4: Where is the project?',
    'Your Construction Estimate'
  ];

  function showStep(n) {
    stepEls.forEach((el) => el.classList.toggle('is-active', Number(el.dataset.step) === n));
    dots.forEach((d, i) => {
      d.classList.toggle('is-active', i === n);
      d.classList.toggle('is-done', i < n);
    });
    prevBtn.style.visibility = n === 0 ? 'hidden' : 'visible';
    if (n < TOTAL) {
      nextBtn.textContent = n === TOTAL - 1 ? 'See estimate →' : 'Next →';
      nextBtn.style.display = '';
    } else {
      nextBtn.style.display = 'none';
    }
    if (stepTitle && stepTitles[n]) {
      stepTitle.textContent = stepTitles[n];
    }
  }

  // Range slider synchronization
  const numInput = document.getElementById('est-sqft');
  const sliderInput = document.getElementById('est-sqft-slider');
  if (numInput && sliderInput) {
    sliderInput.value = numInput.value;

    numInput.addEventListener('input', () => {
      let val = Number(numInput.value);
      if (val < 500) val = 500;
      if (val > 10000) val = 10000;
      sliderInput.value = val;
    });

    sliderInput.addEventListener('input', () => {
      numInput.value = sliderInput.value;
    });
  }

  // Auto-advance on BHK and Package selection
  form.querySelectorAll('input[name="bhk_type"], input[name="package"]').forEach((radio) => {
    radio.addEventListener('change', () => {
      setTimeout(() => {
        const stepNum = Number(radio.closest('.estimator__step').dataset.step);
        if (current === stepNum && current < TOTAL - 1) {
          current++;
          showStep(current);
        }
      }, 250);
    });
  });

  function gather() {
    const fd = new FormData(form);
    return {
      bhk_type: fd.get('bhk_type') || '3BHK',
      plot_size: Number(fd.get('plot_size') || 0),
      package: fd.get('package') || 'comfort',
      city: fd.get('city') || 'Siliguri',
    };
  }

  async function fetchEstimate(data) {
    const r = await fetch('/api/estimate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(data),
    });
    return r.json();
  }

  async function compute() {
    const data = gather();
    const resultEl = document.getElementById('estResult');
    const actionsEl = document.getElementById('estActions');
    const errorEl = document.getElementById('estError');
    errorEl.style.display = 'none';
    try {
      const r = await fetchEstimate(data);
      if (!r.ok) { throw new Error(r.error || 'Could not calculate'); }
      const lo = r.estimate_low, hi = r.estimate_high;
      const inr = (n) => '₹' + Math.round(n).toLocaleString('en-IN');
      document.getElementById('estRange').textContent = inr(lo) + ' – ' + inr(hi);
      document.getElementById('estSub').textContent = 'Approximately ' + inr((lo + hi) / 2) + ' mid-point';
      document.getElementById('estPkg').textContent = r.package_label;
      document.getElementById('estSqft').textContent = data.plot_size.toLocaleString('en-IN') + ' sqft';
      document.getElementById('estCityOut').textContent = data.city;
      document.getElementById('estBhk').textContent = data.bhk_type;
      resultEl.style.display = '';
      actionsEl.style.display = '';

      // Hydrate the "request a quote" link with the same context
      const quoteBtn = document.getElementById('estQuoteBtn');
      if (quoteBtn) {
        const u = new URL(quoteBtn.href, location.origin);
        ['bhk_type', 'plot_size', 'package', 'city'].forEach((k) => u.searchParams.set(k, data[k]));
        u.searchParams.set('estimate_low', lo);
        u.searchParams.set('estimate_high', hi);
        quoteBtn.href = u.pathname + '?' + u.searchParams.toString();
      }
    } catch (e) {
      errorEl.textContent = e.message;
      errorEl.style.display = '';
    }
  }

  prevBtn.addEventListener('click', () => { if (current > 0) { current--; showStep(current); } });
  nextBtn.addEventListener('click', async () => {
    if (current < TOTAL - 1) { current++; showStep(current); return; }
    current = TOTAL;
    showStep(current);
    await compute();
  });

  // Quick-pick sqft buttons
  document.querySelectorAll('[data-sqft]').forEach((b) => {
    b.addEventListener('click', () => {
      const i = document.getElementById('est-sqft');
      const s = document.getElementById('est-sqft-slider');
      if (i) i.value = b.dataset.sqft;
      if (s) s.value = b.dataset.sqft;
    });
  });

  document.getElementById('estRestart')?.addEventListener('click', () => {
    form.reset();
    document.getElementById('estResult').style.display = 'none';
    document.getElementById('estActions').style.display = 'none';
    current = 0;
    showStep(current);
  });

  showStep(0);
})();
