// Quote form: small UX polish — surface the estimate that came in from the estimator.
(function () {
  'use strict';
  const form = document.getElementById('quoteForm');
  if (!form) return;
  const lo = form.querySelector('#q-estLow')?.value;
  const hi = form.querySelector('#q-estHigh')?.value;
  if (!lo || !hi) return;

  const card = form.closest('.qcard');
  if (!card) return;
  const note = document.createElement('div');
  note.style.cssText = 'background:var(--gh-green-50);border:1px solid var(--gh-green-200);border-radius:var(--gh-radius-sm);padding:14px 16px;margin-bottom:20px;font-size:14px;';
  note.innerHTML = '<strong>From your estimator:</strong> Your estimated range is <strong>₹' +
    Number(lo).toLocaleString('en-IN') + ' – ₹' + Number(hi).toLocaleString('en-IN') + '</strong>. ' +
    'We’ve pre-filled the form below so you can request a firm quote.';
  card.insertBefore(note, form);
})();
