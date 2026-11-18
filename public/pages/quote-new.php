<?php
require_once __DIR__ . '/../../src/auth.php';
require_once dirname(__DIR__, 2) . '/src/repo/quotes.php';

$user = require_login();
$err = null;
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once dirname(__DIR__, 2) . '/src/csrf.php';
    require_once dirname(__DIR__, 2) . '/src/mail.php';
    csrf_verify($_POST['_csrf'] ?? null);
    $service = $_POST['service_type'] ?? 'home';
    $allowed = ['home', 'renovation', 'commercial'];
    if (!in_array($service, $allowed, true)) $service = 'home';
    $data = [
        'user_id'       => (int) $user['id'],
        'name'          => $user['name'],
        'email'         => $user['email'],
        'phone'         => trim($_POST['phone'] ?? ''),
        'city'          => trim($_POST['city'] ?? 'Siliguri'),
        'service_type'  => $service,
        'plot_size'     => (int) ($_POST['plot_size'] ?? 0) ?: null,
        'bhk_type'      => in_array($_POST['bhk_type'] ?? '', ['1BHK','2BHK','3BHK','4BHK+','villa','custom'], true) ? $_POST['bhk_type'] : null,
        'budget_band'   => in_array($_POST['budget_band'] ?? '', ['<25L','25-50L','50-75L','75L-1Cr','1Cr+'], true) ? $_POST['budget_band'] : null,
        'package'       => in_array($_POST['package'] ?? '', ['essential','comfort','elegance','signature'], true) ? $_POST['package'] : null,
        'estimate_low'  => (int) ($_POST['estimate_low'] ?? 0) ?: null,
        'estimate_high' => (int) ($_POST['estimate_high'] ?? 0) ?: null,
        'notes'         => trim($_POST['notes'] ?? '') ?: null,
    ];
    if ($data['phone'] === '' || $data['city'] === '') {
        $err = 'Please provide your phone number and city.';
    } else {
        $id = null;
        try {
            $id = quotes_create($data);
        } catch (PDOException $e) {
            error_log("Database connection failed for quote: " . $e->getMessage());
        }
        send_mail(
            (string) gh_env('GH_NOTIFY_TO', gh_env('GH_CONTACT_EMAIL', 'contact@thegreen.homes')),
            'New quote request ' . ($id ? '#' . $id : '(Local DB Down)') . ' — ' . $user['name'],
            '<p>New quote request from <strong>' . htmlspecialchars($user['name']) . '</strong>:</p>' .
            '<ul>' .
            '<li>Email: ' . htmlspecialchars($user['email']) . '</li>' .
            '<li>Phone: ' . htmlspecialchars($data['phone']) . '</li>' .
            '<li>Service: ' . htmlspecialchars($data['service_type']) . '</li>' .
            '<li>City: ' . htmlspecialchars($data['city']) . '</li>' .
            '<li>Plot size: ' . ($data['plot_size'] ? $data['plot_size'] . ' sqft' : '—') . '</li>' .
            '<li>BHK: ' . ($data['bhk_type'] ?? '—') . '</li>' .
            '<li>Package: ' . ($data['package'] ?? '—') . '</li>' .
            '<li>Budget: ' . ($data['budget_band'] ?? '—') . '</li>' .
            '<li>Estimate: ' . ($data['estimate_low'] ? '₹' . number_format($data['estimate_low']) . '–' . number_format($data['estimate_high']) : '—') . '</li>' .
            '<li>Notes: ' . nl2br(htmlspecialchars($data['notes'] ?? '')) . '</li>' .
            '</ul><p>View in admin: ' . gh_app_url() . '/admin</p>',
            $user['email']
        );
        require_once dirname(__DIR__, 2) . '/src/google_sheets.php';
        gh_sheets_log_lead_quote(array_merge($data, ['id' => $id]));
        $ok = true;
    }
}

require_once __DIR__ . '/../../src/view.php';
gh_header(['title' => 'Request a quote', 'active' => 'quote', 'css' => 'dashboard', 'js' => 'quote']);
?>
<section class="qdash">
  <div class="container" style="max-width:760px;">
    <div class="qcard">
      <h1 style="font-size:24px;margin-bottom:6px;">Request a quote</h1>
      <p style="color:var(--gh-ink-soft);margin-bottom:24px;">A senior project manager will get back to you the same day. Pre-filled from your estimator if you came from there.</p>

      <?php if ($ok): ?>
        <div class="gh-form__ok" style="margin-bottom:16px;">Thanks — your request was received. We'll be in touch within 24 hours. <a href="/dashboard">View in your dashboard →</a></div>
      <?php elseif ($err): ?>
        <div class="gh-form__error" style="margin-bottom:16px;"><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <form class="gh-form" method="post" action="/quote/new" id="quoteForm">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
        <input type="hidden" name="estimate_low" id="q-estLow" value="<?= htmlspecialchars($_GET['estimate_low'] ?? '') ?>" />
        <input type="hidden" name="estimate_high" id="q-estHigh" value="<?= htmlspecialchars($_GET['estimate_high'] ?? '') ?>" />

        <div class="gh-form__row gh-form__row--2">
          <div class="gh-field">
            <label for="q-service">Service type</label>
            <select id="q-service" name="service_type">
              <option value="home" <?= ($_GET['service_type'] ?? '') === 'home' ? 'selected' : '' ?>>Home construction</option>
              <option value="renovation" <?= ($_GET['service_type'] ?? '') === 'renovation' ? 'selected' : '' ?>>Renovation</option>

              <option value="commercial" <?= ($_GET['service_type'] ?? '') === 'commercial' ? 'selected' : '' ?>>Commercial</option>
            </select>
          </div>
          <div class="gh-field">
            <label for="q-bhk">BHK / type</label>
            <select id="q-bhk" name="bhk_type">
              <option value="">— select —</option>
              <?php foreach (['1BHK','2BHK','3BHK','4BHK+','villa','custom'] as $b): ?>
                <option value="<?= $b ?>" <?= ($_GET['bhk_type'] ?? '') === $b ? 'selected' : '' ?>><?= $b ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="gh-form__row gh-form__row--2">
          <div class="gh-field"><label for="q-city">City</label>
            <select id="q-city" name="city">
              <?php foreach (['Siliguri','Matigara','Bagdogra','Jalpaiguri','Malbazar','Alipurduar','Darjeeling','Kurseong','Kalimpong','Other'] as $c): ?>
                <option value="<?= $c ?>" <?= ($_GET['city'] ?? 'Siliguri') === $c ? 'selected' : '' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="gh-field"><label for="q-sqft">Plot / built-up area (sqft)</label>
            <input type="number" id="q-sqft" name="plot_size" min="0" max="20000" value="<?= htmlspecialchars($_GET['plot_size'] ?? '') ?>" />
          </div>
        </div>
        <div class="gh-form__row gh-form__row--2">
          <div class="gh-field"><label for="q-pkg">Package (optional)</label>
            <select id="q-pkg" name="package">
              <option value="">— not sure —</option>
              <?php foreach (['essential','comfort','elegance','signature'] as $p): ?>
                <option value="<?= $p ?>" <?= ($_GET['package'] ?? '') === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="gh-field"><label for="q-budget">Budget (optional)</label>
            <select id="q-budget" name="budget_band">
              <option value="">— select —</option>
              <?php foreach (['<25L','25-50L','50-75L','75L-1Cr','1Cr+'] as $b): ?>
                <option value="<?= $b ?>"><?= $b ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="gh-field"><label for="q-phone">Phone</label>
          <input type="tel" id="q-phone" name="phone" required value="<?= htmlspecialchars($user['phone'] ?? '') ?>" />
        </div>
        <div class="gh-field"><label for="q-notes">Tell us about the project</label>
          <textarea id="q-notes" name="notes" placeholder="Plot location, design ideas, timeline, anything else…"><?= htmlspecialchars($_GET['notes'] ?? '') ?></textarea>
        </div>
        <button class="gh-btn gh-btn--primary gh-btn--block gh-btn--lg" type="submit">Submit quote request</button>
      </form>
    </div>
  </div>
</section>
<?php gh_footer(); ?>
