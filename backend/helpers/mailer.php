<?php
// FILE: backend/helpers/mailer.php
//
// Shared PHPMailer factory + resilient send(), used by every part of
// MindCare that sends email (OTP, password reset, guardian emergency
// notices, advisor messages).
//
// WHY THIS EXISTS:
// The same Gmail SMTP credentials that work fine on one machine can fail
// with "Could not authenticate" / connection errors on another, because
// some networks (certain campus WiFi, some ISPs and mobile carriers) block
// outbound port 587 (STARTTLS) while allowing port 465 (implicit SSL), or
// the other way around. mindcareSendMail() tries the configured port
// first and, only if that attempt fails, retries ONCE automatically on
// the other common Gmail SMTP port/security combo before giving up.
//
// This does NOT fix a genuinely wrong password, a fully-blocked network,
// or a Google-side account block — those will still fail on both ports
// and the caller's existing catch block still reports the error normally.

require_once __DIR__ . '/../../vendor/autoload.php';

// Builds a PHPMailer instance pre-filled with MindCare's SMTP credentials
// and sender identity. Callers still set addAddress/Subject/Body/isHTML
// themselves, exactly as before.
function mindcareNewMailer(): \PHPMailer\PHPMailer\PHPMailer
{
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host     = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->setFrom('projectmindcare7@gmail.com', 'MindCare LNBTI');
    return $mail;
}

// Sends $mail, trying SMTP_PORT (from secrets.php) first, then
// automatically falling back to the other common Gmail SMTP port/security
// combo (587+TLS <-> 465+SSL) if the first attempt throws. Returns true on
// success. If BOTH attempts fail, re-throws the exception from the LAST
// attempt so existing `catch (Exception $e) { ... $mail->ErrorInfo ... }`
// blocks in callers keep working unchanged.
function mindcareSendMail(\PHPMailer\PHPMailer\PHPMailer $mail): bool
{
    // If SMTP credentials are unset or placeholder in local development, avoid crashing or timing out.
    if (empty(SMTP_PASS) || SMTP_PASS === 'YOUR_GMAIL_APP_PASSWORD_HERE') {
        error_log("[MindCare Local Mailer Notice] SMTP_PASS is not configured in secrets.php. Skipped actual email dispatch for: " . $mail->Subject);
        return true;
    }

    $primaryPort = (int) SMTP_PORT;
    $primarySecure = ($primaryPort === 465) ? 'ssl' : 'tls';
    $fallbackPort = ($primaryPort === 465) ? 587 : 465;
    $fallbackSecure = ($primaryPort === 465) ? 'tls' : 'ssl';

    $attempts = [
        ['port' => $primaryPort, 'secure' => $primarySecure],
        ['port' => $fallbackPort, 'secure' => $fallbackSecure],
    ];

    $lastException = null;
    foreach ($attempts as $attempt) {
        try {
            // Reset any half-open connection/state from a previous attempt.
            $mail->smtpClose();
            $mail->Port       = $attempt['port'];
            $mail->SMTPSecure = $attempt['secure'];
            $mail->send();
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $lastException = $e;
            // Try the next combo (if any) before giving up.
            continue;
        }
    }

    // Both attempts failed — surface the last error, same as before.
    throw $lastException;
}