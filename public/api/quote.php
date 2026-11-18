<?php
/**
 * POST /api/quote  (logged-in users)
 * Same effect as submitting /quote/new — exposed as JSON for AJAX.
 */

declare(strict_types=1);

header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/src/config.php';
gh_load_env(dirname(__DIR__, 2));
require_once dirname(__DIR__, 2) . '/src/auth.php';
require_once dirname(__DIR__, 2) . '/src/csrf.php';
require_once dirname(__DIR__, 2) . '/src/repo/quotes.php';
require_once dirname(__DIR__, 2) . '/src/mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}
csrf_verify($_POST['_csrf'] ?? null);
$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Please log in to submit a quote request.']);
    exit;
}

$service = $_POST['service_type'] ?? 'home';
if (!in_array($service, ['home','renovation','commercial'], true)) $service = 'home';

$phone = trim($_POST['phone'] ?? '');
$city  = trim($_POST['city'] ?? '');
if ($phone === '' || $city === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Phone and city are required.']);
    exit;
}

$quoteData = [
    'user_id'       => (int) $user['id'],
    'name'          => $user['name'],
    'email'         => $user['email'],
    'phone'         => $phone,
    'city'          => $city,
    'service_type'  => $service,
    'plot_size'     => (int) ($_POST['plot_size'] ?? 0) ?: null,
    'bhk_type'      => in_array($_POST['bhk_type'] ?? '', ['1BHK','2BHK','3BHK','4BHK+','villa','custom'], true) ? $_POST['bhk_type'] : null,
    'budget_band'   => in_array($_POST['budget_band'] ?? '', ['<25L','25-50L','50-75L','75L-1Cr','1Cr+'], true) ? $_POST['budget_band'] : null,
    'package'       => in_array($_POST['package'] ?? '', ['essential','comfort','elegance','signature'], true) ? $_POST['package'] : null,
    'estimate_low'  => (int) ($_POST['estimate_low'] ?? 0) ?: null,
    'estimate_high' => (int) ($_POST['estimate_high'] ?? 0) ?: null,
    'notes'         => trim($_POST['notes'] ?? '') ?: null,
];
$id = null;
try {
    $id = quotes_create($quoteData);
} catch (PDOException $e) {
    error_log("Database connection failed for API quote: " . $e->getMessage());
}
require_once dirname(__DIR__, 2) . '/src/google_sheets.php';
gh_sheets_log_lead_quote(array_merge($quoteData, ['id' => $id]));

send_mail(
    (string) gh_env('GH_NOTIFY_TO', gh_env('GH_CONTACT_EMAIL', 'contact@thegreen.homes')),
    'New quote request ' . ($id ? '#' . $id : '(Local DB Down)') . ' — ' . $user['name'],
    '<p>New quote request from <strong>' . htmlspecialchars($user['name']) . '</strong>:</p>' .
    '<ul>' .
    '<li>Email: ' . htmlspecialchars($user['email']) . '</li>' .
    '<li>Phone: ' . htmlspecialchars($phone) . '</li>' .
    '<li>Service: ' . htmlspecialchars($service) . '</li>' .
    '<li>City: ' . htmlspecialchars($city) . '</li>' .
    '<li>Plot size: ' . ($quoteData['plot_size'] ? $quoteData['plot_size'] . ' sqft' : '—') . '</li>' .
    '<li>BHK: ' . ($quoteData['bhk_type'] ?? '—') . '</li>' .
    '<li>Package: ' . ($quoteData['package'] ?? '—') . '</li>' .
    '<li>Budget: ' . ($quoteData['budget_band'] ?? '—') . '</li>' .
    '<li>Estimate: ' . ($quoteData['estimate_low'] ? '₹' . number_format($quoteData['estimate_low']) . '–' . number_format($quoteData['estimate_high']) : '—') . '</li>' .
    '<li>Notes: ' . nl2br(htmlspecialchars($quoteData['notes'] ?? '')) . '</li>' .
    '</ul><p>View in admin: ' . gh_app_url() . '/admin</p>',
    $user['email']
);

echo json_encode(['ok' => true, 'id' => $id]);
