<?php
// FILE: backend/auth/forgot_password.php
// Self-service "Forgot Password" — sends a 6-digit OTP to the user's own
// campus email, valid for 5 minutes. No admin involved at all.
ob_start();
error_reporting(E_ERROR | E_PARSE);
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['error' => 'POST required']); exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));
if (!$email) {
    ob_end_clean();
    echo json_encode(['error' => 'Email is required.']); exit;
}

$stmt = $pdo->prepare("SELECT id, is_active FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Always respond the same way whether or not the account exists —
// this prevents someone from using this form to discover which
// campus emails are registered on MindCare.
if (!$user || !$user['is_active']) {
    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'If that email has an account, a reset code has been sent.']); exit;
}

$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

$ins = $pdo->prepare("INSERT INTO password_resets (email, otp_code, otp_expires, is_used) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0)");
$ins->execute([$email, $otp]);

require_once __DIR__ . '/../helpers/mailer.php';

try {
    $mail = mindcareNewMailer();
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'MindCare — Password Reset Code';
    $mail->Body    = "
    <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;'>
      <div style='background:linear-gradient(135deg,#F5A846,#F5C846);padding:28px 32px;border-radius:16px 16px 0 0;'>
        <h1 style='color:#1C1410;margin:0;font-size:22px;'>MindCare</h1>
        <p style='color:#4A3728;margin:6px 0 0;font-size:13px;'>Password Reset Request</p>
      </div>
      <div style='background:#fff;padding:32px;border-radius:0 0 16px 16px;'>
        <p style='color:#8C7060;font-size:14px;margin:0 0 24px;'>Use this code to reset your MindCare password. If you didn't request this, you can safely ignore this email.</p>
        <div style='background:#FEF3DF;border:2px solid #F5A846;border-radius:12px;padding:20px;text-align:center;margin-bottom:24px;'>
          <span style='font-size:42px;font-weight:800;color:#D4841A;letter-spacing:10px;'>$otp</span>
        </div>
        <p style='color:#8C7060;font-size:13px;'>Expires in <strong>5 minutes</strong>.</p>
      </div>
    </div>";

    mindcareSendMail($mail);
    ob_end_clean();
    $res = ['success' => true, 'message' => 'If that email has an account, a reset code has been sent.'];
    if (empty(SMTP_PASS) || SMTP_PASS === 'YOUR_GMAIL_APP_PASSWORD_HERE') {
        $res['dev_otp'] = $otp;
    }
    echo json_encode($res);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['error' => 'Could not send reset code. Please try again.']);
}
?>