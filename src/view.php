<?php
/**
 * Render helper. Each page does:
 *   $data = ['title' => 'Estimator', 'active' => 'estimator'];
 *   render('estimator', $data);
 *
 * The router in public/index.php is the only place that calls render().
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

function gh_header(array $data): void
{
    $title = $data['title'] ?? 'Green Homes';
    $active = $data['active'] ?? '';
    $description = $data['description'] ?? 'Green Homes by Green Hill Group: design-to-build home construction in Siliguri and North Bengal.';
    $transparent = $data['transparent'] ?? false;
    $user = current_user();
    $GLOBALS['gh_page_js'] = $data['js'] ?? null;
    ?>
<!doctype html>
<html lang="en">
<head>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-T8RKR9SR');</script>
<!-- End Google Tag Manager -->

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-5KNSV6G8DX"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-5KNSV6G8DX');
</script>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title><?= htmlspecialchars($title) ?> | Green Homes</title>
<meta name="description" content="<?= htmlspecialchars($description) ?>" />
<link rel="icon" type="image/png" href="/assets/img/favicon.png?v=3" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="/assets/css/theme.css?v=4" />
<?php if (!empty($data['css'])): ?>
<link rel="stylesheet" href="/assets/css/<?= htmlspecialchars($data['css']) ?>.css?v=4" />
<?php endif; ?>
<meta name="google-site-verification" content="mDPNFwpTTJaWW1rNSbBk4Tts_BFhB0si-rBSf5yeeNM" />
</head>
<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T8RKR9SR"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<a class="skip-link" href="#main">Skip to main content</a>
<header class="gh-header <?= $transparent ? 'gh-header--transparent' : '' ?>" id="ghHeader">
  <div class="container gh-header__row">
    <a class="gh-brand" href="/" aria-label="Green Homes home">
      <img src="/assets/img/logo-new.png?v=2" alt="Green Homes" style="height: 52px; width: auto; display: block;" />
    </a>
    <button class="gh-nav__toggle" type="button" aria-expanded="false" aria-controls="ghNav" aria-label="Open menu">
      <span></span><span></span><span></span>
    </button>
    <nav class="gh-nav" id="ghNav" aria-label="Main">
      <a href="/" class="<?= $active === 'home' ? 'is-active' : '' ?>">Home</a>
      <a href="/services" class="<?= $active === 'services' ? 'is-active' : '' ?>">Services</a>
      <a href="/estimator" class="<?= $active === 'estimator' ? 'is-active' : '' ?>">Cost Estimator</a>
      
      <a href="/how-it-works" class="<?= $active === 'how' ? 'is-active' : '' ?>">How it Works</a>
      <a href="/about" class="<?= $active === 'about' ? 'is-active' : '' ?>">About</a>
      <a href="/contact" class="<?= $active === 'contact' ? 'is-active' : '' ?>">Contact</a>
      <span class="gh-nav__divider" aria-hidden="true"></span>
      <?php if ($user): ?>
        <a href="/dashboard" class="gh-nav__user">Hi, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></a>
        <?php if (($user['role'] ?? 'user') === 'admin'): ?>
          <a href="/admin" class="<?= $active === 'admin' ? 'is-active' : '' ?>">Admin</a>
        <?php endif; ?>
        <a href="/logout" class="gh-btn gh-btn--ghost gh-btn--sm">Log out</a>
      <?php else: ?>
        <a href="https://app.thegreen.homes" class="gh-btn gh-btn--primary gh-btn--sm">My Dashboard</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main id="main" class="gh-main">
<?php
}

function gh_footer(): void
{
    $phone = gh_env('GH_CONTACT_PHONE', '+91 9046665254');
    $email = gh_env('GH_CONTACT_EMAIL', 'contact@thegreen.homes');
    $year = date('Y');
    ?>
</main>
<footer class="gh-footer">
  <div class="container gh-footer__grid">
    <div>
      <a class="gh-brand gh-brand--footer" href="/">
        <img src="/assets/img/logo-new.png?v=2" alt="Green Homes" style="height: 56px; width: auto; display: block;" />
      </a>
      <p class="gh-footer__about">Design-to-build home construction in Siliguri and North Bengal. Transparent pricing, quality checks at every stage, on-time delivery.</p>
    </div>
    <div>
      <h4>Services</h4>
      <ul class="gh-footer__list">
        <li><a href="/services#home">Home construction</a></li>
        <li><a href="/services#renovation">Renovation</a></li>
        <li><a href="/services#architecture">Architecture &amp; interior</a></li>
        <li><a href="/services#commercial">Commercial construction</a></li>
      </ul>
    </div>
    <div>
      <h4>Company</h4>
      <ul class="gh-footer__list">
        <li><a href="/about">About</a></li>
        
        <li><a href="/how-it-works">How it works</a></li>
        <li><a href="https://greenhillgroup.in" rel="noopener" target="_blank">Green Hill Group ↗</a></li>
        <li><a href="/careers">Careers</a></li>
      </ul>
    </div>
    <div>
      <h4>Get in touch</h4>
      <ul class="gh-footer__list">
        <li><a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $phone)) ?>"><?= htmlspecialchars($phone) ?></a></li>
        <li><a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a></li>
        <li><a href="/contact">Contact form</a></li>
        <li><a href="/estimator">Cost estimator</a></li>
      </ul>
    </div>
  </div>
  <div class="gh-footer__legal">
    <div class="container">
      <span>&copy; <?= $year ?> All rights reserved by DARSH INFRACOM LLP.</span>
      <span><a href="/sitemap">Site Map</a> · <a href="/privacy">Privacy Policy</a></span>
    </div>
  </div>
</footer>
<script src="/assets/js/animations.js" defer></script>
<script src="/assets/js/header.js" defer></script>
<?php if (!empty($GLOBALS['gh_page_js'])): ?>
<script src="/assets/js/<?= htmlspecialchars($GLOBALS['gh_page_js']) ?>.js" defer></script>
<?php endif; ?>
<a href="https://wa.me/919046665254" class="whatsapp-float" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp">
  <svg viewBox="0 0 448 512" style="width: 32px; height: 32px; fill: white;">
    <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 13.3 5.7 23.6 9.2 31.7 11.7 15.5 4.9 29.7 4.2 41 2.5 12.5-1.9 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
  </svg>
</a>
</body>
</html>
<?php
}

/**
 * Output a 404 page and stop.
 */
function gh_404(): void
{
    http_response_code(404);
    $GLOBALS['gh_page_js'] = null;
    gh_header(['title' => 'Page not found', 'active' => '']);
    ?>
<section class="gh-section">
  <div class="container" style="text-align:center;padding:80px 0;">
    <h1 style="font-family:var(--font-display);font-size:48px;color:var(--gh-green-700);">404</h1>
    <p style="color:var(--gh-ink-soft);margin:8px 0 24px;">The page you’re looking for doesn’t exist.</p>
    <a href="/" class="gh-btn gh-btn--primary">Back to home</a>
  </div>
</section>
<?php
    gh_footer();
}
