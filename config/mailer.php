<?php
// ================================================================
// EduStar — Email System using Gmail SMTP (cURL)
// config/mailer.php
// InfinityFree blocks PHP mail() so we use Gmail SMTP via cURL
// ================================================================

define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'edustarai.info@gmail.com');
define('SMTP_PASS',     'autgjvnhupmdlqon');
define('MAIL_FROM',     'edustarai.info@gmail.com');
define('MAIL_FROM_NAME','EduStar AI');
define('MAIL_ADMIN',    'edustarai.info@gmail.com');
define('MAIL_SUPPORT',  'edustarsupport@gmail.com');

/**
 * Send email via Gmail SMTP using cURL (works on InfinityFree)
 */
function sendEmail(string $to, string $subject, string $htmlBody): bool {
    $boundary = md5(uniqid(rand(), true));
    $fromName = MAIL_FROM_NAME;
    $from     = MAIL_FROM;

    $rawEmail =
        "From: {$fromName} <{$from}>\r\n" .
        "To: {$to}\r\n" .
        "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n" .
        "MIME-Version: 1.0\r\n" .
        "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n" .
        "\r\n" .
        "--{$boundary}\r\n" .
        "Content-Type: text/plain; charset=UTF-8\r\n" .
        "Content-Transfer-Encoding: base64\r\n\r\n" .
        chunk_split(base64_encode(strip_tags($htmlBody))) .
        "--{$boundary}\r\n" .
        "Content-Type: text/html; charset=UTF-8\r\n" .
        "Content-Transfer-Encoding: base64\r\n\r\n" .
        chunk_split(base64_encode($htmlBody)) .
        "--{$boundary}--\r\n";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'smtp://' . SMTP_HOST . ':' . SMTP_PORT,
        CURLOPT_USE_SSL        => CURLUSESSL_ALL,
        CURLOPT_USERNAME       => SMTP_USER,
        CURLOPT_PASSWORD       => SMTP_PASS,
        CURLOPT_MAIL_FROM      => '<' . MAIL_FROM . '>',
        CURLOPT_MAIL_RCPT      => ['<' . $to . '>'],
        CURLOPT_READDATA       => fopen('data://text/plain,' . urlencode($rawEmail), 'r'),
        CURLOPT_UPLOAD         => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5, // Short timeout — InfinityFree SMTP can be slow
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $result = curl_exec($ch);
    $error  = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("EduStar mailer error: " . $error);
        return false;
    }
    return true;
}

function emailTemplate(string $title, string $body): string {
    return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body{font-family:Arial,sans-serif;background:#0D0D1A;margin:0;padding:20px}
  .wrap{max-width:560px;margin:0 auto;background:#1A1A2E;border-radius:16px;overflow:hidden}
  .header{background:linear-gradient(135deg,#FF6B2B,#F5A623);padding:32px 32px 24px;text-align:center}
  .header h1{color:#fff;margin:0;font-size:26px;font-weight:800;letter-spacing:-0.5px}
  .header p{color:rgba(255,255,255,0.85);margin:6px 0 0;font-size:13px}
  .content{padding:32px;color:#F0EDE8}
  .content h2{font-size:20px;margin:0 0 12px;color:#FF6B2B}
  .content p{font-size:15px;line-height:1.7;color:#C0BDB8;margin:0 0 16px}
  .highlight{background:rgba(255,107,43,0.1);border:1px solid rgba(255,107,43,0.25);border-radius:10px;padding:14px 18px;margin:16px 0}
  .highlight p{margin:0;color:#F0EDE8;font-size:14px}
  .btn{display:inline-block;background:linear-gradient(135deg,#FF6B2B,#F5A623);color:#fff;text-decoration:none;padding:12px 28px;border-radius:50px;font-weight:700;font-size:15px;margin:8px 0}
  .footer{background:rgba(0,0,0,0.3);padding:20px 32px;text-align:center;color:#666;font-size:12px}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>EduStar AI</h1>
    <p>Smart Learning for Every African Student</p>
  </div>
  <div class="content">' . $body . '</div>
  <div class="footer">
    <p>&copy; 2026 EduStar AI &middot; <a href="https://edustar.my-board.org" style="color:#FF6B2B">edustar.my-board.org</a></p>
    <p style="margin-top:6px">This is an automated message. Please do not reply.</p>
  </div>
</div>
</body>
</html>';
}

// ── WELCOME EMAIL ─────────────────────────────────────────────────
function sendWelcomeEmail(string $to, string $name, string $country, string $grade): void {
    $flag    = countryFlag($country);
    $subject = "Welcome to EduStar AI, {$name}!";
    $body    = emailTemplate('Welcome', "
        <h2>Welcome aboard, {$name}!</h2>
        <p>Your EduStar AI account has been created. You're now part of a growing community of students learning across Africa.</p>
        <div class='highlight'>
          <p>{$flag} <strong>Country:</strong> {$country}<br>
             <strong>Grade:</strong> {$grade}<br>
             <strong>Email:</strong> {$to}</p>
        </div>
        <p>
          <strong>What you can do:</strong><br>
          Browse curriculum-aligned lessons, take adaptive quizzes, chat with your AI tutor, and download school books.
        </p>
        <p style='text-align:center;margin-top:24px'>
          <a href='https://edustar.my-board.org/dashboard.html' class='btn'>Go to Dashboard</a>
        </p>
        <p style='font-size:13px;color:#888;margin-top:16px'>
          If you did not create this account, please ignore this email.
        </p>
    ");
    @sendEmail($to, $subject, $body);

    // Notify admin
    $adminBody = emailTemplate('New Registration', "
        <h2>New Student Registered</h2>
        <div class='highlight'>
          <p><strong>Name:</strong> {$name}<br>
             <strong>Email:</strong> {$to}<br>
             {$flag} <strong>Country:</strong> {$country}<br>
             <strong>Grade:</strong> {$grade}<br>
             <strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
        </div>
        <p style='text-align:center'>
          <a href='https://edustar.my-board.org/admin/' class='btn'>View Admin Panel</a>
        </p>
    ");
    @sendEmail(MAIL_ADMIN, "New EduStar Registration: {$name}", $adminBody);
}

// ── LOGIN ALERT — New Device Only (NO IP address shown) ──────────
function sendLoginAlert(string $to, string $name, string $deviceInfo, bool $isNewDevice): void {
    if (!$isNewDevice) return; // Only email on truly new device

    $time    = date('Y-m-d H:i:s');
    $subject = "EduStar — New Device Login Detected";
    $body    = emailTemplate('New Device Login', "
        <h2>New Device Login Detected</h2>
        <p>Hi {$name}, your EduStar account was just accessed from a <strong>new device or browser</strong>.</p>
        <div class='highlight'>
          <p><strong>Time:</strong> {$time}<br>
             <strong>Device:</strong> {$deviceInfo}<br>
             <strong>Account:</strong> {$to}</p>
        </div>
        <p>If this was you, no action is needed — your new device has been saved.</p>
        <p>If you did <strong>not</strong> log in, please change your password immediately.</p>
        <p style='text-align:center;margin-top:24px'>
          <a href='https://edustar.my-board.org/settings.html' class='btn'>Secure My Account</a>
        </p>
    ");
    @sendEmail($to, $subject, $body);
}

// ── TICKET CONFIRMATION ───────────────────────────────────────────
function sendTicketConfirmation(string $to, string $name, int $ticketId, string $subject): void {
    $body = emailTemplate('Support Ticket', "
        <h2>Support Ticket Received</h2>
        <p>Hi {$name}, we've received your support request and will get back to you as soon as possible.</p>
        <div class='highlight'>
          <p><strong>Ticket #:</strong> {$ticketId}<br>
             <strong>Subject:</strong> {$subject}<br>
             <strong>Status:</strong> Open</p>
        </div>
        <p>You can also reach our support team directly at <strong>" . MAIL_SUPPORT . "</strong>.</p>
        <p style='text-align:center;margin-top:24px'>
          <a href='https://edustar.my-board.org/support.html' class='btn'>View Ticket Status</a>
        </p>
    ");
    @sendEmail($to, "EduStar Support Ticket #{$ticketId} Received", $body);

    // Notify support team of new ticket
    $supportBody = emailTemplate('New Support Ticket', "
        <h2>New Support Ticket Submitted</h2>
        <div class='highlight'>
          <p><strong>Ticket #:</strong> {$ticketId}<br>
             <strong>From:</strong> {$name} ({$to})<br>
             <strong>Subject:</strong> {$subject}<br>
             <strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
        </div>
        <p style='text-align:center'>
          <a href='https://edustar.my-board.org/admin/' class='btn'>View in Admin Panel</a>
        </p>
    ");
    @sendEmail(MAIL_SUPPORT, "New Support Ticket #{$ticketId}: {$subject}", $supportBody);
}

// ── TICKET REPLY NOTIFICATION ─────────────────────────────────────
function sendTicketReplyNotification(string $to, string $name, int $ticketId, string $subject): void {
    $body = emailTemplate('Ticket Update', "
        <h2>Your Support Ticket Has Been Updated</h2>
        <p>Hi {$name}, the EduStar support team has replied to your ticket.</p>
        <div class='highlight'>
          <p><strong>Ticket #:</strong> {$ticketId}<br>
             <strong>Subject:</strong> {$subject}</p>
        </div>
        <p style='text-align:center;margin-top:24px'>
          <a href='https://edustar.my-board.org/support.html' class='btn'>View Reply</a>
        </p>
    ");
    @sendEmail($to, "EduStar — Reply to Ticket #{$ticketId}", $body);
}

// ── COUNTRY FLAG HELPER ───────────────────────────────────────────
function countryFlag(string $code): string {
    $flags = [
        'KE'=>'KE','NG'=>'NG','ZA'=>'ZA','TZ'=>'TZ','UG'=>'UG',
        'GH'=>'GH','ZW'=>'ZW','ZM'=>'ZM','ET'=>'ET','RW'=>'RW',
        'MZ'=>'MZ','MW'=>'MW','BW'=>'BW','SN'=>'SN','CI'=>'CI',
    ];
    return '[' . ($flags[$code] ?? $code) . ']';
}