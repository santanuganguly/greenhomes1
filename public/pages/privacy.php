<?php
require_once __DIR__ . '/../../src/view.php';
$email = gh_env('GH_CONTACT_EMAIL', 'contact@thegreen.homes');
$phone = gh_env('GH_CONTACT_PHONE', '+91 9046665254');
$address = gh_env('GH_CONTACT_ADDRESS', '5th Floor, Beekay Centerio, Beside Payel Cinema Hall, Sevoke Road, Siliguri, 734001');

gh_header(['title' => 'Privacy Policy', 'active' => '']);
?>
<section class="gh-section">
  <div class="container" style="max-width: 800px;">
    <div class="gh-section__head" style="text-align: left;">
      <span class="gh-eyebrow">Legal</span>
      <h1>Privacy Policy</h1>
      <p>Last updated: <?php echo date('F j, Y'); ?></p>
    </div>

    <div class="prose" style="line-height: 1.6; color: var(--gh-ink);">
      <p>This Privacy Policy describes how <strong>Green Homes by Green Hill Group</strong> ("we", "us", or "our") collects, uses, and shares your personal information when you visit our website (thegreen.homes) or use our services.</p>

      <h3 style="margin-top: 32px; margin-bottom: 16px;">1. Information We Collect</h3>
      <p>We collect information you provide directly to us, such as when you request a quote, fill out a contact form, or communicate with us. This may include:</p>
      <ul style="margin-left: 24px; margin-bottom: 16px;">
        <li><strong>Personal Identifiers:</strong> Name, email address, phone number, and physical address.</li>
        <li><strong>Project Details:</strong> Information about your property, budget, and construction requirements.</li>
      </ul>
      <p>We also automatically collect certain information when you visit our website, including your IP address, browser type, device information, and browsing behavior through the use of cookies and similar tracking technologies (like the Meta/Facebook Pixel).</p>

      <h3 style="margin-top: 32px; margin-bottom: 16px;">2. How We Use Your Information</h3>
      <p>We use the information we collect for the following purposes:</p>
      <ul style="margin-left: 24px; margin-bottom: 16px;">
        <li>To provide, maintain, and improve our services (e.g., generating construction quotes).</li>
        <li>To communicate with you regarding your project, inquiries, or customer support.</li>
        <li>To send you marketing and promotional materials (you can opt out at any time).</li>
        <li>To analyze website traffic and optimize our advertising campaigns, including retargeting via platforms like Meta (Facebook/Instagram).</li>
      </ul>

      <h3 style="margin-top: 32px; margin-bottom: 16px;">3. Sharing of Information</h3>
      <p>We do not sell your personal information. We may share your information with:</p>
      <ul style="margin-left: 24px; margin-bottom: 16px;">
        <li><strong>Service Providers:</strong> Third-party vendors who assist us in operating our website, conducting our business, or servicing you (e.g., hosting providers, CRM platforms).</li>
        <li><strong>Advertising Partners:</strong> We may share data with platforms like Meta and Google to deliver relevant advertisements to you. This is done using cookies and tracking pixels.</li>
        <li><strong>Legal Obligations:</strong> When required by law, subpoena, or other legal processes, or to protect our rights and the safety of others.</li>
      </ul>

      <h3 style="margin-top: 32px; margin-bottom: 16px;">4. Cookies and Tracking Technologies</h3>
      <p>Our website uses cookies, pixels (including the Meta Pixel), and other tracking technologies to collect information about your browsing activities. This helps us understand how you interact with our site and allows us to serve targeted advertisements. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.</p>

      <h3 style="margin-top: 32px; margin-bottom: 16px;">5. Data Security and Retention</h3>
      <p>We implement reasonable security measures to protect your personal information from unauthorized access, alteration, disclosure, or destruction. We retain your personal data only for as long as necessary to fulfill the purposes for which it was collected, including legal or reporting requirements.</p>

      <h3 style="margin-top: 32px; margin-bottom: 16px;">6. Your Rights</h3>
      <p>Depending on your location, you may have the right to request access to the personal data we hold about you, request corrections to inaccurate data, or request deletion of your data. To exercise these rights, please contact us using the details below.</p>

      <h3 style="margin-top: 32px; margin-bottom: 16px;">7. Changes to This Privacy Policy</h3>
      <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date.</p>

      <h3 style="margin-top: 32px; margin-bottom: 16px;">8. Contact Us</h3>
      <p>If you have any questions about this Privacy Policy, please contact us at:</p>
      <ul style="margin-left: 24px;">
        <li><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a></li>
        <li><strong>Phone:</strong> <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $phone)) ?>"><?= htmlspecialchars($phone) ?></a></li>
        <li><strong>Address:</strong> <?= htmlspecialchars($address) ?></li>
      </ul>
    </div>
  </div>
</section>
<?php gh_footer(); ?>
