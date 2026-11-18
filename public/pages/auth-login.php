<?php
require_once __DIR__ . '/../../src/view.php';

if (current_user()) {
    header('Location: /dashboard');
    exit;
}

$next = $_GET['next'] ?? '/dashboard';
$err = $_GET['err'] ?? '';
gh_header(['title' => 'Log in', 'active' => 'login', 'css' => 'auth']);
?>
<section class="auth-page">
  <div class="container">
    <div class="auth-card">
      <h1>Welcome back</h1>
      <p class="lead">Log in to view your quote requests and project updates.</p>
      <?php if ($err): ?><div class="gh-form__error" style="margin-bottom:16px;"><?= htmlspecialchars($err) ?></div><?php endif; ?>
      <form class="gh-form" method="post" action="/api/auth">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
        <input type="hidden" name="action" value="login" />
        <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>" />
        <div class="gh-field"><label for="login-email">Email</label><input id="login-email" name="email" type="email" required autocomplete="email" /></div>
        <div class="gh-field"><label for="login-pw">Password</label><input id="login-pw" name="password" type="password" required autocomplete="current-password" /></div>
        <button class="gh-btn gh-btn--primary gh-btn--block" type="submit">Log in</button>
      </form>
      <p class="auth-card__alt">No account? <a href="/signup?next=<?= urlencode($next) ?>">Create one →</a></p>
    </div>
  </div>
</section>
<?php gh_footer(); ?>
