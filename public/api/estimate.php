<?php
/**
 * POST /api/estimate  → JSON { ok, estimate_low, estimate_high, package_label }
 *
 * Inputs (JSON body or form):
 *   bhk_type  (string)
 *   plot_size (int sqft)
 *   package   (basic|classic|premium|royale)
 *   city      (string)
 */

declare(strict_types=1);

header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/src/config.php';
gh_load_env(dirname(__DIR__, 2));

$body = json_decode(file_get_contents('php://input') ?: '', true) ?: $_POST;
$bhk  = (string) ($body['bhk_type'] ?? '3BHK');
$sqft = (int)    ($body['plot_size'] ?? 0);
$pkg  = (string) ($body['package']    ?? 'comfort');
$city = (string) ($body['city']       ?? 'Siliguri');

if ($sqft < 400 || $sqft > 20000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Plot size must be between 400 and 20,000 sqft.']);
    exit;
}

$rates = [
    'essential' => ['rate' => 1950, 'label' => 'Essential · ₹1,950/sqft'],
    'comfort'   => ['rate' => 2100, 'label' => 'Comfort · ₹2,100/sqft'],
    'elegance'  => ['rate' => 2250, 'label' => 'Elegance · ₹2,250/sqft'],
    'signature' => ['rate' => 2500, 'label' => 'Signature · ₹2,500/sqft'],
];
if (!isset($rates[$pkg])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown package.']);
    exit;
}

// City adjustments: Siliguri base; remote cities add 3-7%
$cityAdj = 1.0;
$cityL = strtolower($city);
if (in_array($cityL, ['darjeeling', 'kalimpong', 'kurseong'], true)) $cityAdj = 1.07;
elseif (in_array($cityL, ['malbazar', 'alipurduar'], true)) $cityAdj = 1.04;
elseif ($cityL === 'jalpaiguri') $cityAdj = 1.02;

// BHK/type adjustments (villa/duplex tend to be slightly higher per sqft)
$bhkAdj = 1.0;
if (in_array($bhk, ['villa', 'duplex'], true)) $bhkAdj = 1.05;

$base = $sqft * $rates[$pkg]['rate'] * $cityAdj * $bhkAdj;
$low  = (int) round($base * 0.92);
$high = (int) round($base * 1.10);

echo json_encode([
    'ok'            => true,
    'estimate_low'  => $low,
    'estimate_high' => $high,
    'package_label' => $rates[$pkg]['label'],
    'inputs'        => ['bhk_type' => $bhk, 'plot_size' => $sqft, 'package' => $pkg, 'city' => $city],
]);
