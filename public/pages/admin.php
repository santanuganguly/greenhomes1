<?php
require_once __DIR__ . '/dashboard-layout.php';
require_once dirname(__DIR__, 2) . '/src/repo/quotes.php';
require_once dirname(__DIR__, 2) . '/src/repo/messages.php';

$admin = require_admin();

$tab = $_GET['tab'] ?? 'quotes';
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once dirname(__DIR__, 2) . '/src/csrf.php';
    csrf_verify($_POST['_csrf'] ?? null);
    if (isset($_POST['update_status'])) {
        $ok = quotes_update_status((int) $_POST['id'], (string) $_POST['status']);
        $flash = $ok ? 'Status updated.' : 'Could not update status.';
    }
}

$dbError = false;
$stats = ['total' => 0, 'week' => 0, 'won' => 0, 'conversion' => 0.0];
$quotes = [];
$messages = [];
try {
    $stats = quotes_stats();
    $quotes = quotes_all();
    $messages = messages_all();
} catch (PDOException $e) {
    error_log("Admin page DB failed: " . $e->getMessage());
    $dbError = true;
}

gh_dashboard_header(['title' => 'Admin Dashboard', 'active' => '', 'tab' => 'admin-dashboard']);
?>
<div class="d-card">
    <div class="admin__head">
      <div>
        <span class="gh-eyebrow">Admin</span>
        <h1>Operations dashboard</h1>
        <p>Manage incoming leads, update quote statuses, and respond to contact-form messages.</p>
      </div>
    </div>

    <?php if ($dbError): ?>
      <div class="empty-state" style="border-color: #fca5a5; background: #fff5f5; margin-bottom: 24px; text-align: left; padding: 24px;">
        <h3 style="color: #991b1b; margin-top: 0;">Database Connection Issue</h3>
        <p style="color: #7f1d1d; margin-bottom: 0;">The local MySQL database is offline. New leads, quotes, and contact submissions are safely recorded in your Google Sheet, but local dashboard statistics and lists cannot be loaded until the database is running.</p>
      </div>
    <?php endif; ?>

    <?php if ($flash): ?><div class="gh-form__ok" style="margin-bottom:16px;"><?= htmlspecialchars($flash) ?></div><?php endif; ?>

    <div class="stats">
      <div class="stat"><div class="stat__label">Total leads</div><div class="stat__num"><?= number_format($stats['total']) ?></div></div>
      <div class="stat"><div class="stat__label">New this week</div><div class="stat__num"><?= number_format($stats['week']) ?></div></div>
      <div class="stat"><div class="stat__label">Won</div><div class="stat__num"><?= number_format($stats['won']) ?></div></div>
      <div class="stat"><div class="stat__label">Conversion</div><div class="stat__num"><?= number_format($stats['conversion'], 1) ?>%</div></div>
    </div>

    <div class="admin-tabs">
      <a href="/admin?tab=quotes" class="<?= $tab === 'quotes' ? 'is-active' : '' ?>">Quote requests (<?= count($quotes) ?>)</a>
      <a href="/admin?tab=messages" class="<?= $tab === 'messages' ? 'is-active' : '' ?>">Contact messages (<?= count($messages) ?>)</a>
    </div>

    <?php if ($tab === 'quotes'): ?>
      <?php if (empty($quotes)): ?>
        <div class="empty-state"><h3>No quote requests yet</h3><p>Submissions from the website will appear here.</p></div>
      <?php else: ?>
        <table class="qtable">
          <thead>
            <tr><th>#</th><th>Submitted</th><th>Name</th><th>Service</th><th>City</th><th>Size</th><th>Estimate</th><th>Status</th><th>Notes</th></tr>
          </thead>
          <tbody>
          <?php foreach ($quotes as $q): ?>
            <tr>
              <td>#<?= (int) $q['id'] ?></td>
              <td><?= htmlspecialchars(date('d M Y', strtotime($q['created_at']))) ?></td>
              <td><strong><?= htmlspecialchars($q['name']) ?></strong><br><span class="gh-muted" style="font-size:12px;"><?= htmlspecialchars($q['email']) ?><br><?= htmlspecialchars($q['phone']) ?></span></td>
              <td><?= htmlspecialchars(ucfirst($q['service_type'])) ?><?php if ($q['package']): ?><br><span class="gh-pill" style="font-size:10px;"><?= htmlspecialchars(ucfirst($q['package'])) ?></span><?php endif; ?></td>
              <td><?= htmlspecialchars($q['city']) ?></td>
              <td><?= $q['plot_size'] ? number_format($q['plot_size']) . ' sqft' : '—' ?></td>
              <td>
                <?php if ($q['estimate_low'] && $q['estimate_high']): ?>
                  ₹<?= number_format((int)$q['estimate_low']) ?>–<?= number_format((int)$q['estimate_high']) ?>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td>
                <form method="post" style="display:inline-flex;gap:6px;align-items:center;">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
                  <input type="hidden" name="id" value="<?= (int) $q['id'] ?>" />
                  <input type="hidden" name="update_status" value="1" />
                  <select name="status" onchange="this.form.submit()">
                    <?php foreach (['new','contacted','won','lost'] as $s): ?>
                      <option value="<?= $s ?>" <?= $q['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td style="font-size:13px;max-width:240px;"><?= $q['notes'] ? htmlspecialchars(mb_strimwidth($q['notes'], 0, 100, '…')) : '—' ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    <?php else: ?>
      <?php if (empty($messages)): ?>
        <div class="empty-state"><h3>No contact messages</h3><p>Submissions from the contact form will appear here.</p></div>
      <?php else: ?>
        <div class="gh-grid" style="grid-template-columns:1fr;gap:16px;">
          <?php foreach ($messages as $m): ?>
            <div class="contact-msg">
              <div class="contact-msg__head">
                <div><strong><?= htmlspecialchars($m['name']) ?></strong> &middot; <a href="mailto:<?= htmlspecialchars($m['email']) ?>"><?= htmlspecialchars($m['email']) ?></a><?php if ($m['phone']): ?> &middot; <a href="tel:<?= htmlspecialchars($m['phone']) ?>"><?= htmlspecialchars($m['phone']) ?></a><?php endif; ?></div>
                <div class="meta"><?= htmlspecialchars(date('d M Y H:i', strtotime($m['created_at']))) ?></div>
              </div>
              <?php if (!empty($m['subject'])): ?><div class="contact-msg__subject"><?= htmlspecialchars($m['subject']) ?></div><?php endif; ?>
              <div class="contact-msg__body"><?= htmlspecialchars($m['body']) ?></div>
              <div class="contact-msg__actions">
                <a href="mailto:<?= htmlspecialchars($m['email']) ?>?subject=Re: <?= rawurlencode($m['subject'] ?: 'Your message to Green Homes') ?>">Reply by email →</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php gh_dashboard_footer(); ?>
