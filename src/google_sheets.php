<?php
/**
 * Google Sheets API Integration helper.
 * Posts logged leads, quotes, contact messages, and user activity to a Google Sheet.
 *
 * Google Apps Script POST flow:
 *   1. POST to script.google.com → 302 redirect to script.googleusercontent.com
 *   2. The redirect URL is unique per-request and served via GET
 *   3. We follow that redirect (GET) to retrieve the JSON response
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function gh_sheets_post(string $type, array $payload): void
{
    $url = gh_env('GH_GOOGLE_SHEETS_API_URL');
    if (!$url || filter_var($url, FILTER_VALIDATE_URL) === false) {
        return;
    }

    $data     = ['type' => $type, 'payload' => $payload];
    $jsonData = (string) json_encode($data);

    $ch = curl_init();
    if ($ch === false) {
        return;
    }

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,

        // Step 1 — POST the JSON body
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $jsonData,
        CURLOPT_HTTPHEADER     => ['Content-Type: text/plain'],

        // Step 2 — Follow the 302 redirect (GET, standard browser behaviour)
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 10,
        // Do NOT set CURLOPT_POSTREDIR — let cURL convert POST→GET on redirect
        // (that is exactly what Google expects)

        // Timeouts
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT        => 30,

        // TLS — skip cert verification (Windows CA bundle may be missing/outdated)
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,

        // Protocol
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_USERAGENT      => 'GreenHomes/1.0 PHP/' . PHP_VERSION,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        error_log(
            "Google Sheets API post failed for type '{$type}'."
            . " HTTP: {$httpCode}. cURL: {$curlErr}."
            . " Response: " . substr((string) $response, 0, 200)
        );
    }
}

function gh_sheets_log_lead_quote(array $data): void
{
    gh_sheets_post('lead_quote', $data);
}

function gh_sheets_log_contact(array $data): void
{
    gh_sheets_post('contact', $data);
}

function gh_sheets_log_career(array $data): void
{
    gh_sheets_post('career', $data);
}

function gh_sheets_log_user_activity(array $data): void
{
    gh_sheets_post('user_activity', $data);
}
