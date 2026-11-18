<?php
require_once __DIR__ . '/../../src/view.php';

if (current_user()) {
    header('Location: /dashboard');
    exit;
}

$next = $_GET['next'] ?? '/dashboard';
$err = $_GET['err'] ?? '';
gh_header(['title' => 'Create account', 'active' => 'signup', 'css' => 'auth']);
?>
<section class="auth-page">
  <div class="container">
    <div class="auth-card">
      <h1>Create your account</h1>
      <p class="lead">Save estimates, request quotes, and track your project.</p>
      <?php if ($err): ?><div class="gh-form__error" style="margin-bottom:16px;"><?= htmlspecialchars($err) ?></div><?php endif; ?>
      <form class="gh-form" method="post" action="/api/auth">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
        <input type="hidden" name="action" value="signup" />
        <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>" />
        <div class="gh-field"><label for="su-name">Full name</label><input id="su-name" name="name" type="text" required autocomplete="name" /></div>
        <div class="gh-field"><label for="su-email">Email</label><input id="su-email" name="email" type="email" required autocomplete="email" /></div>
        <div class="gh-field"><label for="su-phone">Phone (optional)</label><input id="su-phone" name="phone" type="tel" autocomplete="tel" /></div>
        <div class="gh-field"><label for="su-pw">Password (8+ characters)</label><input id="su-pw" name="password" type="password" minlength="8" required autocomplete="new-password" /></div>
        <button class="gh-btn gh-btn--primary gh-btn--block" type="submit">Create account</button>
      </form>
      <p class="auth-card__alt">Already have one? <a href="/login?next=<?= urlencode($next) ?>">Log in →</a></p>
    </div>
  </div>
</section>
<?php gh_footer(); ?>
