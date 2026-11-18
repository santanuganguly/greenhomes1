<?php
require_once __DIR__ . '/../../src/view.php';
gh_header(['title' => 'Site Map', 'active' => '']);
?>
<section class="gh-section">
  <div class="container" style="max-width: 800px;">
    <div class="gh-section__head" style="text-align: left;">
      <span class="gh-eyebrow">Navigation</span>
      <h1>Site Map</h1>
    </div>
    <div class="prose">
      <ul style="font-size: 1.1rem; line-height: 1.8;">
        <li><a href="/">Home</a></li>
        <li><a href="/services">Services</a></li>
        <li><a href="/estimator">Cost Estimator</a></li>
        
        <li><a href="/how-it-works">How It Works</a></li>
        <li><a href="/about">About Us</a></li>
        <li><a href="/contact">Contact</a></li>
        <li><a href="/careers">Careers</a></li>
        <li><a href="/privacy">Privacy Policy</a></li>
        <li><a href="https://app.thegreen.homes">My Dashboard</a></li>
      </ul>
    </div>
  </div>
</section>
<?php gh_footer(); ?>
