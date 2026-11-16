<?php
/**
 * Quote form handler for firstduefiretruckparty.com
 * Emails each request to the business, copies it to the Google Sheet lead log
 * (when 'sheet_url' is set in mail-config.php), then sends the visitor to /thank-you/.
 * Sends through Mailgun SMTP when ../mail-config.php exists (see mail-config.sample.php
 * in the source folder); otherwise falls back to PHP mail().
 * TO_EMAIL / FROM_EMAIL below are defaults; mail-config.php can override them.
 */
declare(strict_types=1);
date_default_timezone_set('America/New_York');

const TO_EMAIL   = 'inquiry@firstduefiretruckparty.com, dhruvjha72@gmail.com'; // all recipients, comma-separated
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

if ($name === '' || $phone === '' || $service === '' || $date === '') {
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
$body .= "Email:       " . ($email !== '' ? $email : '(not given)') . "\n";
$body .= "Event type:  $service\n";
$body .= "Event date:  $date\n";
$body .= "City / ZIP:  " . ($location !== '' ? $location : '(not given)') . "\n";
$body .= "Guests:      $guests\n\n";
$body .= "Message:\n" . ($message !== '' ? $message : '(none)') . "\n\n";
$body .= "Sent from page: $page\n";
$body .= "Time: " . date('Y-m-d H:i:s T') . "\n";
$body .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n";

// SMTP settings (Mailgun) live in mail-config.php one folder ABOVE public_html,
// so the password is never in the repo or reachable on the web.
$PRIVATE_DIR = dirname(rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/'));
$CONFIG_FILE = $PRIVATE_DIR . '/mail-config.php';
$cfg = is_readable($CONFIG_FILE) ? (array)(include $CONFIG_FILE) : [];

function log_mail_error(string $msg): void {
    global $PRIVATE_DIR;
    @file_put_contents($PRIVATE_DIR . '/mail-error.log', date('c') . '  ' . $msg . "\n", FILE_APPEND);
    error_log('send-quote: ' . $msg);
}

$recipients = array_filter(array_map('trim', explode(',', $cfg['to'] ?? TO_EMAIL)));
$fromEmail  = $cfg['from_email'] ?? FROM_EMAIL;
$fromName   = $cfg['from_name']  ?? (SITE_NAME . ' Website');
$ok = false;

if (!empty($cfg['smtp_host']) && !empty($cfg['smtp_user']) && !empty($cfg['smtp_pass'])) {
    require __DIR__ . '/lib/phpmailer/Exception.php';
    require __DIR__ . '/lib/phpmailer/PHPMailer.php';
    require __DIR__ . '/lib/phpmailer/SMTP.php';
    $port = (int)($cfg['smtp_port'] ?? 587);
    try {
        $m = new PHPMailer\PHPMailer\PHPMailer(true);
        $m->isSMTP();
        $m->Host       = $cfg['smtp_host'];
        $m->Port       = $port;
        $m->SMTPAuth   = true;
        $m->Username   = $cfg['smtp_user'];
        $m->Password   = $cfg['smtp_pass'];
        $m->SMTPSecure = $port === 465 ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                                       : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $m->Timeout    = 15;
        $m->CharSet    = 'UTF-8';
        $m->setFrom($fromEmail, $fromName);
        foreach ($recipients as $r) { $m->addAddress($r); }
        if ($email !== '') { $m->addReplyTo($email, $name); }
        $m->Subject = $subject;
        $m->Body    = $body;
        $ok = $m->send();
    } catch (\Throwable $ex) {
        log_mail_error('SMTP failed: ' . (isset($m) ? $m->ErrorInfo : '') . ' / ' . $ex->getMessage());
        $ok = false;
    }
} else {
    // Fallback if mail-config.php is missing: the server's own mail() (less reliable).
    $headers = [
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . ($email !== '' ? $name . ' <' . $email . '>' : $fromEmail),
        'Content-Type: text/plain; charset=UTF-8',
    ];
    $ok = @mail(implode(', ', $recipients), '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers), '-f' . $fromEmail);
    if (!$ok) { log_mail_error('mail() returned false (no SMTP config found at ' . $CONFIG_FILE . ')'); }
}

// Copy the lead to the Google Sheet (Apps Script web app; URL lives in mail-config.php as 'sheet_url').
$sheetOk = false;
$sheetUrl = (string)($cfg['sheet_url'] ?? '');
if ($sheetUrl !== '' && function_exists('curl_init')) {
    $payload = json_encode([
        'name' => $name, 'phone' => $phone, 'email' => $email, 'service' => $service,
        'event_date' => $date, 'location' => $location, 'guests' => $guests, 'message' => $message,
        'page' => $page, 'site' => $IS_STAGING ? 'staging (test)' : 'live',
        'email_sent' => $ok, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
    $ch = curl_init($sheetUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_FOLLOWLOCATION => true, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    $sheetOk = is_string($resp) && trim($resp) === 'ok';
    if (!$sheetOk) { log_mail_error('Sheet log failed: ' . (is_string($resp) ? substr($resp, 0, 200) : curl_error($ch))); }
    curl_close($ch);
}

// Show the error (and ask them to call) only if the lead reached neither the inbox nor the sheet.
if (!$ok && !$sheetOk) { back_with_error(); }

header('Location: ' . $BASE . '/thank-you/', true, 303);
exit;
