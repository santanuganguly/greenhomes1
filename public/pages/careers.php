<?php
require_once __DIR__ . '/../../src/view.php';
require_once __DIR__ . '/../../src/csrf.php';
gh_header(['title' => 'Careers', 'active' => '']);
?>
<section class="gh-section">
  <div class="container" style="max-width: 700px;">
    <div class="gh-section__head" style="text-align: center;">
      <span class="gh-eyebrow">Join the Team</span>
      <h1>Careers at Green Homes</h1>
      <p>Build the future of housing in North Bengal with us.</p>
    </div>
    
    <div class="prose" style="line-height: 1.6; color: var(--gh-ink); margin-bottom: 48px; text-align: center;">
      <p>We are always on the lookout for talented architects, site engineers, project managers, and construction professionals. Fill out the application form below to apply.</p>
    </div>

    <form id="careerForm" onsubmit="submitCareerForm(event)" class="gh-form qcard">
      <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
      
      <div id="careerAlert" style="display: none; padding: 16px; margin-bottom: 24px; border-radius: 4px; font-weight: 500;"></div>

      <div class="gh-form__row gh-form__row--2">
        <div class="gh-field">
          <label for="c-name">Full Name *</label>
          <input id="c-name" type="text" name="name" required placeholder="John Doe">
        </div>
        <div class="gh-field">
          <label for="c-email">Email Address *</label>
          <input id="c-email" type="email" name="email" required placeholder="john@example.com">
        </div>
      </div>

      <div class="gh-form__row gh-form__row--2">
        <div class="gh-field">
          <label for="c-phone">Phone Number *</label>
          <input id="c-phone" type="tel" name="phone" required placeholder="+91 ...">
        </div>
        <div class="gh-field">
          <label for="c-position">Position Applying For *</label>
          <input id="c-position" type="text" name="position" required placeholder="e.g. Site Engineer, Architect">
        </div>
      </div>

      <div class="gh-field">
        <label for="c-resume">Link to Resume / Portfolio *</label>
        <input id="c-resume" type="url" name="resume_url" required placeholder="Google Drive, Dropbox, LinkedIn, or Portfolio URL">
        <small style="display: block; margin-top: 6px; color: var(--gh-muted); font-size: 13px;">Please provide a link to your CV or portfolio.</small>
      </div>

      <div class="gh-field">
        <label for="c-message">Cover Letter / Message</label>
        <textarea id="c-message" name="message" rows="5" placeholder="Tell us about your experience and why you'd be a great fit..."></textarea>
      </div>

      <button class="gh-btn gh-btn--primary" type="submit" style="width: 100%;">Submit Application</button>
    </form>
  </div>
</section>

<script>
async function submitCareerForm(e) {
  e.preventDefault();
  const form = e.target;
  const btn = form.querySelector('button');
  const alert = document.getElementById('careerAlert');
  const formData = new FormData(form);

  btn.disabled = true;
  btn.textContent = 'Submitting...';
  alert.style.display = 'none';

  try {
    const res = await fetch('/api/career', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    
    if (data.ok) {
      alert.style.display = 'block';
      alert.style.backgroundColor = 'var(--gh-surface)';
      alert.style.color = 'var(--gh-primary)';
      alert.style.border = '1px solid var(--gh-primary)';
      alert.textContent = 'Application submitted successfully! We will get in touch if your profile matches our requirements.';
      form.reset();
    } else {
      alert.style.display = 'block';
      alert.style.backgroundColor = '#fff0f0';
      alert.style.color = '#c92a2a';
      alert.style.border = '1px solid #ffc9c9';
      alert.textContent = data.error || 'An error occurred. Please try again.';
    }
  } catch (err) {
    alert.style.display = 'block';
    alert.style.backgroundColor = '#fff0f0';
    alert.style.color = '#c92a2a';
    alert.style.border = '1px solid #ffc9c9';
    alert.textContent = 'Network error. Please try again.';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Submit Application';
  }
}
</script>

<?php gh_footer(); ?>
