<?php
/**
 * POST /api/career  — public, no auth.
 */

declare(strict_types=1);

header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/src/config.php';
gh_load_env(dirname(__DIR__, 2));
require_once dirname(__DIR__, 2) . '/src/csrf.php';
require_once dirname(__DIR__, 2) . '/src/mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}
csrf_verify($_POST['_csrf'] ?? null);

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$position = trim($_POST['position'] ?? '');
$resume_url = trim($_POST['resume_url'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $position === '' || $resume_url === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Name, valid email, position, and resume link are required.']);
    exit;
}

require_once dirname(__DIR__, 2) . '/src/google_sheets.php';
gh_sheets_log_career([
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'position' => $position,
    'resume_url' => $resume_url,
    'message' => $message
]);

send_mail(
    (string) gh_env('GH_NOTIFY_TO', gh_env('GH_CONTACT_EMAIL', 'contact@thegreen.homes')),
    'New Job Application: ' . $position,
    '<p>New career application received:</p>' .
    '<ul>' .
    '<li>Name: ' . htmlspecialchars($name) . '</li>' .
    '<li>Email: ' . htmlspecialchars($email) . '</li>' .
    '<li>Phone: ' . htmlspecialchars($phone ?: '—') . '</li>' .
    '<li>Position: ' . htmlspecialchars($position) . '</li>' .
    '<li>Resume Link: <a href="' . htmlspecialchars($resume_url) . '">' . htmlspecialchars($resume_url) . '</a></li>' .
    '<li>Message: ' . nl2br(htmlspecialchars($message)) . '</li>' .
    '</ul>',
    $email
);

echo json_encode(['ok' => true]);
