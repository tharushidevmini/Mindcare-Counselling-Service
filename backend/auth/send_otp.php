<?php
ob_start();
error_reporting(E_ERROR | E_PARSE); // suppress notices/warnings that could corrupt JSON output
require_once '../config/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['error' => 'POST required']); exit;
}

$rawInput  = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true) ?? [];
$email = strtolower(trim($_POST['email'] ?? $jsonInput['email'] ?? ''));

if (!$email) {
    ob_end_clean();
    echo json_encode(['error' => 'Email is required.']); exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    ob_end_clean();
    echo json_encode(['status' => 'existing_user']); exit;
}

$domain = substr(strrchr($email, '@'), 1);
if ($domain !== 'edu.lnbti.lk') {
    ob_end_clean();
    echo json_encode(['error' => 'Only @edu.lnbti.lk campus emails are allowed.']); exit;
}

$otp     = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

$del = $pdo->prepare("DELETE FROM guardian_sessions WHERE guardian_phone = ?");
$del->execute([$email]);

$ins = $pdo->prepare("
    INSERT INTO guardian_sessions (guardian_phone, student_id, otp_code, otp_expires, is_verified)
    VALUES (?, NULL, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), 0)
");
$ins->execute([$email, $otp]);

require_once __DIR__ . '/../helpers/mailer.php';

try {
    $mail = mindcareNewMailer();
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'MindCare — Your Login Code';
    $mail->Body    = "
    <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;'>
      <div style='background:linear-gradient(135deg,#F5A846,#F5C846);padding:28px 32px;border-radius:16px 16px 0 0;'>
        <h1 style='color:#1C1410;margin:0;font-size:22px;'>MindCare</h1>
        <p style='color:#4A3728;margin:6px 0 0;font-size:13px;'>LNBTI Campus Mental Health Support</p>
      </div>
      <div style='background:#fff;padding:32px;border-radius:0 0 16px 16px;'>
        <h2 style='color:#1C1410;font-size:18px;margin:0 0 8px;'>Your Login Code</h2>
        <p style='color:#8C7060;font-size:14px;margin:0 0 24px;'>Enter this code to complete your MindCare registration.</p>
        <div style='background:#FEF3DF;border:2px solid #F5A846;border-radius:12px;padding:20px;text-align:center;margin-bottom:24px;'>
          <span style='font-size:42px;font-weight:800;color:#D4841A;letter-spacing:10px;'>$otp</span>
        </div>
        <p style='color:#8C7060;font-size:13px;'>Expires in <strong>15 minutes</strong>.</p>
        <p style='color:#8C7060;font-size:13px;'>If you did not request this, please ignore this email.</p>
      </div>
    </div>";

    mindcareSendMail($mail);
    ob_end_clean();
    $res = ['status' => 'new_user', 'message' => 'OTP sent to your campus email.'];
    if (empty(SMTP_PASS) || SMTP_PASS === 'YOUR_GMAIL_APP_PASSWORD_HERE') {
        $res['dev_otp'] = $otp;
    }
    echo json_encode($res);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['error' => 'Email send failed: ' . $e->getMessage()]);
}
?>