<?php
/**
 * Quote form handler for firstduefiretruckparty.com
 * Emails each request to the business, then sends the visitor to /thank-you/.
 * Settings: change TO_EMAIL / FROM_EMAIL below if needed.
 */
declare(strict_types=1);

const TO_EMAIL   = 'inquiry@firstduefiretruckparty.com';
const FROM_EMAIL = 'website@firstduefiretruckparty.com'; // must be an address on this domain for good delivery
const SITE_NAME  = '1st Due Fire Truck Party and Events';

// Folder the site runs from: '' on the live site, '/staging' on the staging copy.
$BASE = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$IS_STAGING = $BASE !== '';

function back_with_error(): void {
    global $BASE;
    $ref = $_SERVER['HTTP_REFERER'] ?? ($BASE . '/contact/');
    $path = parse_url($ref, PHP_URL_PATH) ?: ($BASE . '/contact/');
    header('Location: ' . $path . '?error=1#quote', true, 303);
    exit;
}
function clean(string $key, int $max = 300): string {
    $v = trim((string)($_POST[$key] ?? ''));
    $v = str_replace(["\r", "\n", "%0a", "%0d"], ' ', $v); // stop header injection
    return mb_substr(strip_tags($v), 0, $max);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . $BASE . '/contact/', true, 303);
    exit;
}

// Spam checks: hidden honeypot must be empty; form must take at least 3 seconds to fill.
if (!empty($_POST['company_website'])) { header('Location: ' . $BASE . '/thank-you/', true, 303); exit; }
$ts = (int)($_POST['ts'] ?? 0);
if ($ts > 0 && (time() - $ts) < 3) { header('Location: ' . $BASE . '/thank-you/', true, 303); exit; }

$name     = clean('name', 120);
$phone    = clean('phone', 40);
$email    = filter_var(clean('email', 160), FILTER_VALIDATE_EMAIL) ?: '';
$service  = clean('service', 80);
$date     = clean('event_date', 20);
$location = clean('location', 120);
$guests   = clean('guests', 40);
$page     = clean('page', 200);
$message  = trim(strip_tags((string)($_POST['message'] ?? '')));
$message  = mb_substr($message, 0, 2000);

if ($name === '' || $phone === '' || $email === '' || $service === '' || $date === '' || $location === '') {
    back_with_error();
}
if (preg_match('~https?://~i', $message) && substr_count(strtolower($message), 'http') > 2) {
    header('Location: ' . $BASE . '/thank-you/', true, 303); exit; // link-spam
}

$subject = ($IS_STAGING ? '[STAGING TEST] ' : '') . 'New fire truck quote request: ' . $service . ' on ' . $date;
$body  = "New quote request from the website\n";
$body .= "==================================\n\n";
$body .= "Name:        $name\n";
$body .= "Phone:       $phone\n";
$body .= "Email:       $email\n";
$body .= "Event type:  $service\n";
$body .= "Event date:  $date\n";
$body .= "City / ZIP:  $location\n";
$body .= "Guests:      $guests\n\n";
$body .= "Message:\n" . ($message !== '' ? $message : '(none)') . "\n\n";
$body .= "Sent from page: $page\n";
$body .= "Time: " . date('Y-m-d H:i:s T') . "\n";
$body .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n";

$headers = [
    'From: ' . SITE_NAME . ' Website <' . FROM_EMAIL . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . phpversion(),
];

$ok = @mail(TO_EMAIL, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers), '-f' . FROM_EMAIL);

if (!$ok) { back_with_error(); }

header('Location: ' . $BASE . '/thank-you/', true, 303);
exit;
